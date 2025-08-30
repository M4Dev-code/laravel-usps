<?php

namespace UspsShipping\Laravel\Exceptions;

class UspsException extends \Exception
{
    protected array $context;

    public function __construct(string $message, int $code = 0, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
