<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

class ProcessShellArgs implements \Stringable
{
    /**
     * @param string[] $args
     * @param (callable(string $type, string $message): mixed)|null $output
     */
    public function __construct(
        private array $args = [],
        private mixed $output = null
    ) {
    }


    /**
     * @return string[]
     */
    public function getArgs(): array
    {
        return $this->args;
    }


    /**
     * @return (callable(string $type, string $message): mixed)
     */
    public function getOutput(): callable
    {
        return $this->output ?? fn(string $type, string $message): mixed => 0;
    }


    /** @inheritdoc */
    public function __toString(): string
    {
        return implode(" ", $this->getArgs());
    }
}
