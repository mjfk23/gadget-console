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
}
