<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

class ProcessShellEnv
{
    /** @var string $workDir */
    private string $workDir;

    /** @var string[] $env */
    private array $env;


    /**
     * @param string $workDir
     * @param mixed[] $env
     */
    public function __construct(
        string $workDir = '.',
        array $env = []
    ) {
        $this->setWorkDir($workDir);
        $this->setEnv($env);
    }


    /**
     * @return string[]
     */
    public function getEnv(): array
    {
        return $this->env;
    }


    /**
     * @param mixed[] $env,
     * @param bool $includeGlobal
     * @return static
     */
    public function setEnv(
        array $env,
        bool $includeGlobal = true
    ): static {
        $this->env = array_filter(array_map(
            fn(mixed $v) => is_scalar($v) || (is_object($v) && $v instanceof \Stringable) ? strval($v) : null,
            $includeGlobal ? array_merge($_ENV, $_SERVER, $env) : $env
        ));

        return $this;
    }


    /**
     * @return string
     */
    public function getWorkDir(): string
    {
        return $this->workDir;
    }


    /**
     * @param string $workDir
     * @return static
     */
    public function setWorkDir(string $workDir): static
    {
        $this->workDir = $workDir;
        return $this;
    }
}
