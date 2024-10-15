<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

use Gadget\Io\JSON;

class ProcessShellOutput
{
    /**
     * @param self[] $outputs
     * @return self
     */
    public static function fromArray(array $outputs): self
    {
        return new self(
            fn(string $type, string $message): mixed => array_map(
                fn(self $o): mixed => $o->__invoke($type, $message),
                $outputs
            )
        );
    }


    /**
     * @param (callable(string $type, string $message): mixed)|null $output
     */
    public function __construct(private mixed $output = null)
    {
        $this->output ??= fn(string $type, string $message): mixed => 0;
    }


    /**
     * @param (callable(string $type, string $message): mixed)|null $output
     */
    public function setOutput(callable|null $output = null): void
    {
        $this->output = $output;
    }


    /**
     * @param string $type
     * @param string $message
     * @return mixed
     */
    public function __invoke(
        string $type,
        string $message
    ): mixed {
        return ($this->output ?? fn(string $type, string $message): mixed => 0)($type, $message);
    }


    /**
     * @param int $pid
     * @param ProcessShellArgs $args
     * @return void
     */
    public function start(
        int $pid,
        ProcessShellArgs $args
    ): void {
        $this->__invoke(
            ProcessShell::START,
            sprintf(
                "Process started: PID=>%s, Args=>%s",
                $pid,
                JSON::encode($args)
            )
        );
    }


    /**
     * @param int $pid
     * @param int $exitCode
     * @param float $startTime
     * @return void
     */
    public function terminate(
        int $pid,
        int $exitCode,
        float $startTime
    ): void {
        $stop = \DateTime::createFromFormat('U.u', (string) microtime(true));
        $start = \DateTime::createFromFormat('U.u', (string) $startTime);
        $this->__invoke(
            ProcessShell::TERMINATE,
            sprintf(
                "Process terminated: PID=>%s, Exit=>%s, Elapsed=>%s",
                $pid,
                $exitCode,
                ($start instanceof \DateTime && $stop instanceof \DateTime)
                ? substr($start->diff($stop)->format('%D:%H:%I:%S.%F'), 0, 15)
                : '00:00:00:00.000'
            )
        );
    }
}
