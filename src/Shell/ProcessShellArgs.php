<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

class ProcessShellArgs implements \Stringable
{
    /**
     * @param string[] $args
     */
    public function __construct(private array $args = [])
    {
    }


    /**
     * @param string[] $args
     * @return void
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }


    /**
     * @return string[]
     */
    public function getArgs(): array
    {
        return $this->args;
    }


    /** @inheritdoc */
    public function __toString(): string
    {
        return implode(" ", $this->getArgs());
    }
}
