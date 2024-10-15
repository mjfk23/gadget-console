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
     * @param string|resource|\Traversable<string>|null $input
     * @return void
     */
    public function setInput(mixed $input): void
    {
        $this->input = $input;
    }


    /**
     * @return string|resource|\Traversable<string>|null
     */
    public function getInput(): mixed
    {
        return $this->input;
    }
}
