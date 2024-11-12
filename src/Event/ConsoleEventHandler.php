<?php

declare(strict_types=1);

namespace Gadget\Console\Event;

use Gadget\Io\JSON;
use Gadget\Lang\StackTrace;
use Gadget\Log\LoggerProxyInterface;
use Gadget\Log\LoggerProxyTrait;
use Gadget\Log\Monolog\Processor\ConsoleCommandProcessor;
use Gadget\Time\Timer;
use Gadget\Util\Stack;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventHandler implements EventSubscriberInterface, LoggerProxyInterface
{
    use LoggerProxyTrait;


    /** @inheritdoc */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 1],
            ConsoleEvents::ERROR => ['onConsoleError', 1],
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', 1]
        ];
    }


    /** @var Stack<array{string,Timer}|null> $eventStack */
    private Stack $eventStack;


    /**
     * @param ConsoleCommandProcessor $processor
     */
    public function __construct(private ConsoleCommandProcessor $processor)
    {
        /** @var Stack<array{string,Timer}|null> $eventStack */
        $eventStack = new Stack();
        $this->eventStack = $eventStack;
    }


    /**
     * @param ConsoleCommandEvent $event
     * @return void
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $hasEvents = !$this->eventStack->empty();
        $commandName = $event->getCommand()?->getName() ?? null;
        if (is_string($commandName)) {
            $this->eventStack->push([$commandName, (new Timer())->start()]);
        } else {
            $this->eventStack->push(null);
        }

        if ($hasEvents) {
            $this->logCommandStarted($event, $commandName, $hasEvents);
        }
        $this->processor->commandName = $commandName;
        if (!$hasEvents) {
            $this->logCommandStarted($event, $commandName, $hasEvents);
        }
    }


    /**
     * @param ConsoleErrorEvent $event
     * @return void
     */
    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $stackTrace = new StackTrace($event->getError());
        foreach ($stackTrace as $l) {
            $this->error($l);
        }
    }


    /**
     * @param ConsoleTerminateEvent $event
     * @return void
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        list($prevCommand, $prevTimer) = $this->eventStack->pop() ?? [null, null];
        list($currCommand, ) = $this->eventStack->peek() ?? [null, null];
        $hasEvents = !$this->eventStack->empty();

        if (!$hasEvents) {
            $this->logCommandTerminated($event, $prevCommand, $prevTimer, $hasEvents);
        }
        $this->processor->commandName = $currCommand;
        if ($hasEvents) {
            $this->logCommandTerminated($event, $prevCommand, $prevTimer, $hasEvents);
        }
    }


    /**
     * @param ConsoleCommandEvent $event
     * @param string|null $commandName
     * @param bool $hasEvents
     * @return void
     */
    private function logCommandStarted(
        ConsoleCommandEvent $event,
        string|null $commandName,
        bool $hasEvents
    ): void {
        if (is_string($commandName)) {
            $args = JSON::encode(array_filter(array_merge(
                $event->getInput()->getArguments(),
                $event->getInput()->getOptions(),
                [
                    "command" => null,
                    "help" => null,
                    "quiet" => null,
                    "verbose" => null,
                    "version" => null,
                    "ansi" => null,
                    "no-interaction" => null,
                    "env" => null,
                    "no-debug" => null,
                    "profile" => null
                ]
            )));

            if ($hasEvents) {
                $this->info(sprintf("Command started: Name=>%s, Args=>%s", $commandName, $args));
            } else {
                $this->info(sprintf("Started: %s", $args));
            }
        }
    }


    /**
     * @param ConsoleTerminateEvent $event
     * @param string|null $commandName
     * @param Timer|null $timer
     * @param bool $hasEvents
     * @return void
     */
    private function logCommandTerminated(
        ConsoleTerminateEvent $event,
        string|null $commandName,
        Timer|null $timer,
        bool $hasEvents
    ): void {
        if ($commandName !== null && $timer !== null) {
            if ($hasEvents) {
                $this->info(sprintf(
                    "Command terminated: Name=>%s, Code=>%d, Elapsed=>%s",
                    $commandName,
                    $event->getExitCode(),
                    $timer->getElapsed()
                ));
            } else {
                $this->info(sprintf(
                    "Terminated: Code=>%d, Elapsed=>%s",
                    $event->getExitCode(),
                    $timer->getElapsed()
                ));
            }
        }
    }
}
