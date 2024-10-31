<?php

declare(strict_types=1);

namespace Gadget\Console\Shell;

class ProcessShellOutput
{
    /**
     * @param self[] $outputs
     * @return self
     */
    public static function fromArray(array $outputs): self
    {
        return new self(
            fn(string $type, string $message): mixed => array_map(
                fn(self $o): mixed => $o->__invoke($type, $message),
                $outputs
            )
        );
    }


    /**
     * @param (callable(string $type, string $message): mixed)|null $output
     * @param bool $trimOutput
     */
    public function __construct(
        private mixed $output = null,
        private bool $trimOutput = true
    ) {
    }


    public function getOutput(): callable
    {
        return is_callable($this->output)
            ? $this->output
            : throw new \RuntimeException();
    }


    /**
     * @param (callable(string $type, string $message): mixed) $output
     * @return static
     */
    public function setOutput(callable $output): static
    {
        $this->output = $output;
        return $this;
    }


    /**
     * @return bool
     */
    public function getTrimOutput(): bool
    {
        return $this->trimOutput;
    }


    /**
     * @param bool $trimOutput
     * @return static
     */
    public function setTrimOutput(bool $trimOutput): static
    {
        $this->trimOutput = $trimOutput;
        return $this;
    }


    /**
     * @param string $type
     * @param string $message
     * @return array{string,string}
     */
    protected function formatOutput(
        string $type,
        string $message
    ): array {
        return [$type, $this->trimOutput ? trim($message) : $message];
    }


    /**
     * @param string $type
     * @param string $message
     * @return mixed
     */
    public function __invoke(
        string $type,
        string $message
    ): mixed {
        return ($this->getOutput())(...$this->formatOutput($type, $message));
    }
}
