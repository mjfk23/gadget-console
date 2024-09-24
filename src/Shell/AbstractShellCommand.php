<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

use Gadget\Log\LoggerProxyInterface;
use Gadget\Log\LoggerProxyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractShellCommand extends Command implements LoggerProxyInterface
{
    use LoggerProxyTrait;


    /** @inheritdoc */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $shell = new ProcessShell(
            $this->getCallback($input, $output),
            $this->getEnv($input, $output),
            $this->getWorkDir($input, $output)
        );

        $shell->executeAll($this->getCommands($input, $output));

        return self::SUCCESS;
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return (callable(string $type, string $message): mixed)
     */
    protected function getCallback(
        InputInterface $input,
        OutputInterface $output
    ): callable {
        return function (string $type, string $message) use ($output): void {
            $message = trim($message);
            if ($type === ProcessShell::START || $type === ProcessShell::TERMINATE) {
                $output->writeln($message);
            }
            $this->logger?->info($message);
        };
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string[]
     */
    protected function getEnv(
        InputInterface $input,
        OutputInterface $output
    ): array {
        return [];
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    protected function getWorkDir(
        InputInterface $input,
        OutputInterface $output
    ): string|null {
        return null;
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array<string[]>
     */
    abstract protected function getCommands(
        InputInterface $input,
        OutputInterface $output
    ): array;
}
