<?php

declare(strict_types=1);

namespace Gadget\Console\Command;

use Gadget\Console\Shell\ProcessShell;
use Gadget\Console\Shell\ProcessShellArgs;
use Gadget\Console\Shell\ProcessShellEnv;
use Gadget\Console\Shell\ProcessShellInput;
use Gadget\Console\Shell\ProcessShellOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ShellCommand extends Command
{
    public function __construct(
        protected ProcessShellEnv|null $env = null,
        protected ProcessShellInput|null $input = null,
        protected ProcessShellOutput|null $output = null,
        string|null $name = null
    ) {
        parent::__construct($name);
    }


    /** @inheritdoc */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this
            ->getShell($input, $output)
            ->executeAll($this->getArgs($input, $output));

        return self::SUCCESS;
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ProcessShell
     */
    protected function getShell(
        InputInterface $input,
        OutputInterface $output
    ): ProcessShell {
        return new ProcessShell(
            $this->getEnv($input, $output),
            $this->getInput($input, $output),
            $this->getOutput($input, $output)
        );
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ProcessShellEnv
     */
    protected function getEnv(
        InputInterface $input,
        OutputInterface $output
    ): ProcessShellEnv {
        return $this->env ?? new ProcessShellEnv();
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ProcessShellInput
     */
    protected function getInput(
        InputInterface $input,
        OutputInterface $output
    ): ProcessShellInput {
        return $this->input ?? new ProcessShellInput();
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ProcessShellOutput
     */
    protected function getOutput(
        InputInterface $input,
        OutputInterface $output
    ): ProcessShellOutput {
        return $this->output ?? new ProcessShellOutput(function (string $type, string $message) use ($output): void {
            $message = trim($message);
            if ($type !== ProcessShell::START && $type !== ProcessShell::TERMINATE) {
                $output->writeln($message);
            }
        });
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ProcessShellArgs[]
     */
    abstract protected function getArgs(
        InputInterface $input,
        OutputInterface $output
    ): array;
}
