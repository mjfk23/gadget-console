<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

use Gadget\Io\JSON;
use Symfony\Component\Process\Process;

final class ProcessShell
{
    public const START = 'start';
    public const TERMINATE = 'terminate';
    public const OUT = 'out';
    public const ERR = 'err';


    /**
     * @param (callable(string $type, string $message): mixed) $output
     * @param string[] $env
     * @param string|null $workDir
     */
    public function __construct(
        private mixed $output,
        private array $env = [],
        private string|null $workDir = null
    ) {
    }


    /**
     * @param string[]|ProcessShellArgs $args
     * @param string|resource|\Traversable<string>|null $input
     * @return array{int,Process}
     */
    public function start(
        array|ProcessShellArgs $args,
        mixed $input = null
    ): array {
        $args = $args instanceof ProcessShellArgs ? $args->getArgs() : $args;
        $process = new Process($args, $this->workDir, null, null, null);
        $process
            ->setInput($input)
            ->start($this->output(...), $this->env);
        $pid = $process->getPid() ?? -1;
        $this->outputStart($pid, $args);
        return [$pid, $process];
    }


    /**
     * @param array<string[]|ProcessShellArgs> $args
     * @param array<string|resource|\Traversable<string>|null> $inputs
     * @param int $maxProcesses
     * @param float $waitInterval
     * @return int[]
     */
    public function startAll(
        array $args,
        array $inputs = [],
        int $maxProcesses = 4,
        float $waitInterval = 0.01
    ): array {
        /** @var array{int,Process}[] $processes */
        $processes = [];
        /** @var int[] $exitCodes */
        $exitCodes = [];

        $waitInterval = (int) floor(1000000 * $waitInterval);
        if ($waitInterval < 10000) {
            $waitInterval = 10000;
        }

        // While there's stuff to do
        while (count($args) > 0 || count($processes) > 0) {
            $updatedQueue = false;

            // Remove processes from queue
            foreach ($processes as $idx => list($pid, $process)) {
                if ($process->isTerminated()) {
                    $exitCodes[$idx] = $process->getExitCode() ?? 0;
                    $updatedQueue = true;
                    $this->outputTerminate(
                        $pid,
                        $exitCodes[$idx],
                        $process->getStartTime()
                    );
                    unset($processes[$idx]);
                }
            }

            // Add processes to queue
            while (count($args) > 0 && count($processes) < $maxProcesses) {
                $processes[] = $this->start(
                    array_shift($args),
                    array_shift($inputs)
                );
                $updatedQueue = true;
            }

            // Sleep if nothing was done
            if (!$updatedQueue) {
                usleep($waitInterval);
            }
        }

        return $exitCodes;
    }


    /**
     * @param array<string[]|ProcessShellArgs> $args
     * @param array<string|resource|\Traversable<string>|null> $inputs
     * @param bool $throwOnError
     * @return int[]
     */
    public function executeAll(
        array $args,
        array $inputs = [],
        bool $throwOnError = true
    ): array {
        $exitCodes = [];
        foreach ($args as $idx => $arg) {
            $exitCodes[] = $exitCode = $this->execute($arg, $inputs[$idx] ?? null);
            if ($throwOnError && $exitCode !== 0) {
                throw new ShellException(["Invalid exit code: %s", $exitCode]);
            }
        }
        return $exitCodes;
    }


    /**
     * @param string[]|ProcessShellArgs $args
     * @param string|resource|\Traversable<string>|null $input
     * @return int
     */
    public function execute(
        array|ProcessShellArgs $args,
        mixed $input = null
    ): int {
        list($pid, $process) = $this->start($args, $input);
        $exitCode = $process->wait();
        $this->outputTerminate($pid, $exitCode, $process->getStartTime());
        return $exitCode;
    }


    /**
     * @param string $type
     * @param string $data
     * @return void
     */
    private function output(
        string $type,
        string $data
    ): void {
        if (strlen(trim($data)) > 0) {
            ($this->output)($type, $data);
        }
    }


    /**
     * @param int $pid
     * @param string[]|ProcessShellArgs $args
     * @return void
     */
    private function outputStart(
        int $pid,
        array|ProcessShellArgs $args
    ): void {
        $this->output('start', sprintf(
            "Process started: PID=>%s, Args=>%s",
            $pid,
            JSON::encode($args)
        ));
    }


    /**
     * @param int $pid
     * @param int $exitCode
     * @return void
     */
    private function outputTerminate(
        int $pid,
        int $exitCode,
        float $startTime
    ): void {
        $stop = \DateTime::createFromFormat('U.u', (string) microtime(true));
        $start = \DateTime::createFromFormat('U.u', (string) $startTime);
        $this->output(self::TERMINATE, sprintf(
            "Process terminated: PID=>%s, Exit=>%s, Elapsed=>%s",
            $pid,
            $exitCode,
            ($start instanceof \DateTime && $stop instanceof \DateTime)
                ? substr($start->diff($stop)->format('%D:%H:%I:%S.%F'), 0, 15)
                : '00:00:00:00.000'
        ));
    }
}
