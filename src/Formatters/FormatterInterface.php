<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Formatters;

use Illuminate\Support\Collection;

interface FormatterInterface
{
    /**
     * Render the check results as a string.
     *
     * Each item of the collection is an array with the keys
     * `rule`, `description`, `severity` and `result` (a SafeguardResult),
     * as returned by SafeguardManager::runChecks().
     */
    public function format(Collection $results, string $environment): string;
}
