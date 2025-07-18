<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Contracts;

use Grazulex\LaravelSafeguard\SafeguardResult;

interface SafeguardRule
{
    /**
     * Unique identifier for the rule.
     */
    public function id(): string;

    /**
     * Human-readable description of what this rule checks.
     */
    public function description(): string;

    /**
     * Execute the security check.
     */
    public function check(): SafeguardResult;

    /**
     * Check if this rule should run for the given environment.
     */
    public function appliesToEnvironment(string $environment): bool;

    /**
     * Severity level of this rule (info, warning, error, critical).
     */
    public function severity(): string;

    /**
     * Format detailed output for this rule's result.
     * This allows each rule to control how its details are displayed.
     *
     * @param  SafeguardResult  $result  The result to format
     * @return array Array of formatted lines to display
     */
    public function formatDetails(SafeguardResult $result): array;
}
