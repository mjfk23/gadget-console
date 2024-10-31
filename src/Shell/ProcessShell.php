<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

use Gadget\Console\Exception\ShellException;
use Symfony\Component\Process\Process;

class ProcessShell
{
    public const OUT = 'out';
    public const ERR = 'err';


    /**
     * @param ProcessShellEnv $env
     */
    public function __construct(private ProcessShellEnv $env)
    {
    }


    /**
     * @param ProcessShellArgs $args
     * @return Process
     */
    public function create(ProcessShellArgs $args): Process
    {
        return new Process(
            command: $args->getArgs(),
            cwd: $this->env->getWorkDir(),
            env: $this->env->getEnv()
        );
    }


    /**
     * @param ProcessShellArgs $args
     * @param ProcessShellInput $input
     * @param ProcessShellOutput $output
     * @return Process
     */
    public function start(
        ProcessShellArgs $args,
        ProcessShellInput $input,
        ProcessShellOutput $output
    ): Process {
        $process = $this
            ->create($args)
            ->setInput($input->getInput());

        $process->start($output);

        return $process;
    }


    /**
     * @param array{ProcessShellArgs,ProcessShellInput,ProcessShellOutput}[] $args
     * @param int $maxProcesses
     * @param float $waitInterval
     * @return int[]
     */
    public function startAll(
        array $args,
        int $maxProcesses = 4,
        float $waitInterval = 0.01
    ): array {
        /**
         * @var Process[] $processes
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
            foreach ($processes as $idx => $process) {
                if ($process->isTerminated()) {
                    $exitCodes[$idx] = $process->getExitCode() ?? 0;
                    $updatedQueue = true;
                    unset($processes[$idx]);
                }
            }

            // Add processes to queue
            while (count($args) > 0 && count($processes) < $maxProcesses) {
                $processes[] = $this->start(...array_shift($args));
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
     * @param ProcessShellInput $input
     * @param ProcessShellOutput $output
     * @return int
     */
    public function execute(
        ProcessShellArgs $args,
        ProcessShellInput $input,
        ProcessShellOutput $output
    ): int {
        $process = $this->start($args, $input, $output);
        $exitCode = $process->wait();
        return $exitCode;
    }


    /**
     * @param array{ProcessShellArgs,ProcessShellInput,ProcessShellOutput}[] $args
     * @param bool $throwOnError
     * @return int[]
     */
    public function executeAll(
        array $args,
        bool $throwOnError = true
    ): array {
        $exitCodes = [];
        foreach ($args as $arg) {
            $exitCodes[] = $exitCode = $this->execute(...$arg);
            if ($throwOnError && $exitCode !== 0) {
                throw new ShellException(["Invalid exit code: %s", $exitCode]);
            }
        }
        return $exitCodes;
    }
}
