<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

class ProcessShellEnv
{
    /**
     * @param string[] $env
     * @param string|null $workDir
     */
    public function __construct(
        private array $env = [],
        private string|null $workDir = null
    ) {
    }


    /**
     * @return string[]
     */
    public function getEnv(): array
    {
        return $this->env;
    }


    /**
     * @return string
     */
    public function getWorkDir(): string|null
    {
        return $this->workDir;
    }
}
