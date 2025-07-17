<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard;

class SafeguardResult
{
    public function __construct(
        private readonly bool $success,
        private readonly string $message,
        private readonly string $severity = 'error',
        private readonly array $details = []
    ) {}

    public static function pass(string $message, array $details = []): self
    {
        return new self(true, $message, 'info', $details);
    }

    public static function fail(string $message, string $severity = 'error', array $details = []): self
    {
        return new self(false, $message, $severity, $details);
    }

    public static function warning(string $message, array $details = []): self
    {
        return new self(false, $message, 'warning', $details);
    }

    public static function critical(string $message, array $details = []): self
    {
        return new self(false, $message, 'critical', $details);
    }

    public function passed(): bool
    {
        return $this->success;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function severity(): string
    {
        return $this->severity;
    }

    public function details(): array
    {
        return $this->details;
    }
}
