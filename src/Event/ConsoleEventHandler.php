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
        $this->eventStack = new Stack();
    }


    /**
     * @param ConsoleCommandEvent $event
     * @return void
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $commandName = $event->getCommand()?->getName() ?? null;

        if (is_string($commandName)) {
            $this->info(sprintf(
                "Command started: Name=>%s, Args=>%s",
                $commandName,
                JSON::encode(array_merge(
                    $event->getInput()->getArguments(),
                    $event->getInput()->getOptions()
                ))
            ));

            $this->eventStack->push([$commandName, (new Timer())->start()]);
        } else {
            $this->eventStack->push(null);
        }

        $this->processor->commandName = $commandName;
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
        list($commandName, $timer) = $this->eventStack->pop() ?? [null, null];
        list($this->processor->commandName,) = $this->eventStack->peek() ?? [null, null];
        if ($commandName !== null && $timer !== null) {
            $this->info(sprintf(
                "Command terminated: Name=>%s, Code=>%d, Elapsed=>%s",
                $commandName,
                $event->getExitCode(),
                $timer->getElapsed()
            ));
        }
    }
}
