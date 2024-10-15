<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

use Gadget\Console\Exception\ShellException;
use Symfony\Component\Process\Process;

class ProcessShell
{
    public const START = 'start';
    public const TERMINATE = 'terminate';
    public const OUT = 'out';
    public const ERR = 'err';


    /**
     * @param ProcessShellEnv|null $env
     * @param ProcessShellInput|null $input
     * @param ProcessShellOutput|null $output
     */
    public function __construct(
        private ProcessShellEnv|null $env = null,
        private ProcessShellInput|null $input = null,
        private ProcessShellOutput|null $output = null
    ) {
    }


    /**
     * @param ProcessShellArgs $args
     * @param ProcessShellEnv|null $env
     * @param ProcessShellInput|null $input
     * @param ProcessShellOutput|null $output
     * @return array{ProcessShellArgs,ProcessShellEnv,ProcessShellInput,ProcessShellOutput}
     */
    protected function init(
        ProcessShellArgs $args,
        ProcessShellEnv|null $env = null,
        ProcessShellInput|null $input = null,
        ProcessShellOutput|null $output = null
    ): array {
        return [
            $args,
            $env ?? $this->env ?? new ProcessShellEnv(),
            $input ?? $this->input ?? new ProcessShellInput(),
            $output ?? $this->output ?? new ProcessShellOutput()
        ];
    }



    /**
     * @param ProcessShellArgs $args
     * @param ProcessShellEnv|null $env
     * @param ProcessShellInput|null $input
     * @param ProcessShellOutput|null $output
     * @return array{int,Process,ProcessShellArgs,ProcessShellEnv,ProcessShellInput,ProcessShellOutput}
     */
    public function start(
        ProcessShellArgs $args,
        ProcessShellEnv|null $env = null,
        ProcessShellInput|null $input = null,
        ProcessShellOutput|null $output = null
    ): array {
        list($args, $env, $input, $output) = $this->init($args, $env, $input, $output);

        $process = new Process(
            command: $args->getArgs(),
            cwd: $env->getWorkDir(),
            env: null,
            input: null,
            timeout: null
        );

        $process
            ->setInput($input->getInput())
            ->start($output, $env->getEnv());

        $pid = $process->getPid() ?? -1;
        $output->start($pid, $args);

        return [$pid, $process, $args, $env, $input, $output];
    }


    /**
     * @param ProcessShellArgs[] $args
     * @param ProcessShellEnv[] $envs
     * @param ProcessShellInput[] $inputs
     * @param ProcessShellOutput[] $outputs
     * @param int $maxProcesses
     * @param float $waitInterval
     * @return int[]
     */
    public function startAll(
        array $args,
        array $envs = [],
        array $inputs = [],
        array $outputs = [],
        int $maxProcesses = 4,
        float $waitInterval = 0.01
    ): array {
        /**
         * @var array{int,Process,ProcessShellArgs,ProcessShellEnv,ProcessShellInput,ProcessShellOutput}[] $processes
         */
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
            foreach ($processes as $idx => list($pid, $process,,,, $output)) {
                if ($process->isTerminated()) {
                    $exitCodes[$idx] = $process->getExitCode() ?? 0;
                    $updatedQueue = true;

                    $output->terminate(
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
                    array_shift($envs),
                    array_shift($inputs),
                    array_shift($outputs)
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
     * @param ProcessShellArgs $args
     * @param ProcessShellEnv|null $env
     * @param ProcessShellInput|null $input
     * @param ProcessShellOutput|null $output
     * @return int
     */
    public function execute(
        ProcessShellArgs $args,
        ProcessShellEnv|null $env = null,
        ProcessShellInput|null $input = null,
        ProcessShellOutput|null $output = null
    ): int {
        list($pid, $process,,,, $output) = $this->start($args, $env, $input, $output);
        $exitCode = $process->wait();
        $output->terminate($pid, $exitCode, $process->getStartTime());
        return $exitCode;
    }


    /**
     * @param ProcessShellArgs[] $args
     * @param ProcessShellEnv[] $envs
     * @param ProcessShellInput[] $inputs
     * @param ProcessShellOutput[] $outputs
     * @param bool $throwOnError
     * @return int[]
     */
    public function executeAll(
        array $args,
        array $envs = [],
        array $inputs = [],
        array $outputs = [],
        bool $throwOnError = true
    ): array {
        $exitCodes = [];
        foreach ($args as $idx => $arg) {
            $exitCodes[] = $exitCode = $this->execute(
                $arg,
                $envs[$idx] ?? null,
                $inputs[$idx] ?? null,
                $outputs[$idx] ?? null
            );
            if ($throwOnError && $exitCode !== 0) {
                throw new ShellException(["Invalid exit code: %s", $exitCode]);
            }
        }
        return $exitCodes;
    }
}
