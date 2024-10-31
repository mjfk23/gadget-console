<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

class ProcessShellInput
{
    /**
     * @param string|resource|\Traversable<string>|null $input
     */
    public function __construct(private mixed $input = null)
    {
    }


    /**
     * @return string|resource|\Traversable<string>|null
     */
    public function getInput(): mixed
    {
        return $this->input;
    }


    /**
     * @param string|resource|\Traversable<string>|null $input
     * @return static
     */
    public function setInput(mixed $input): static
    {
        $this->input = $input;
        return $this;
    }
}
