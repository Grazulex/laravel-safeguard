<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Formatters;

use Illuminate\Support\Collection;
use JsonException;

class JsonFormatter extends AbstractFormatter
{
    /**
     * @throws JsonException When the results cannot be encoded.
     */
    public function format(Collection $results, string $environment): string
    {
        $summary = self::summarize($results);

        $output = [
            'status' => $this->status($summary),
            'environment' => $environment,
            'timestamp' => now()->toISOString(),
            'summary' => $summary,
            'results' => $results->map(function (array $check): array {
                return [
                    'rule' => $check['rule'],
                    'description' => $check['description'],
                    'status' => $check['result']->passed() ? 'passed' : 'failed',
                    'message' => $check['result']->message(),
                    'severity' => $check['result']->severity(),
                    'details' => $check['result']->details(),
                ];
            })->values()->all(),
        ];

        return json_encode(
            $output,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }
}
