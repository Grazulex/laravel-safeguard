<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Formatters;

use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Collection;
use JsonException;

/**
 * SARIF 2.1.0 report, readable by GitHub code scanning and other SARIF viewers.
 *
 * Every executed rule is declared in the tool driver. Every failed rule is a
 * result: error/critical severities map to the "error" level, warnings to
 * "warning". Findings accepted by the baseline are kept as suppressed results
 * with the "note" level, so that they stay visible without failing anything.
 */
class SarifFormatter extends AbstractFormatter
{
    public const VERSION = '2.1.0';

    public const SCHEMA = 'https://json.schemastore.org/sarif-2.1.0.json';

    public const TOOL_NAME = 'Laravel Safeguard';

    public const INFORMATION_URI = 'https://github.com/Grazulex/laravel-safeguard';

    /**
     * Location used for findings that are not tied to a file: code scanning
     * requires every result to point to a file of the repository.
     */
    public const DEFAULT_LOCATION = 'composer.json';

    /**
     * Maximum number of locations kept per result.
     */
    public const MAX_LOCATIONS = 10;

    private const MAX_DETAILS_DEPTH = 4;

    /**
     * @throws JsonException When the results cannot be encoded.
     */
    public function format(Collection $results, string $environment): string
    {
        $rules = [];
        $sarifResults = [];

        foreach ($results as $check) {
            $result = is_array($check) ? ($check['result'] ?? null) : null;

            if (! is_array($check) || ! $result instanceof SafeguardResult) {
                continue;
            }

            $ruleId = $this->stringify($check['rule'] ?? '');
            $ruleIndex = count($rules);
            $rules[] = $this->ruleDescriptor($ruleId, $check);

            if (! $result->passed()) {
                $sarifResults[] = $this->result($ruleId, $ruleIndex, $result, $check);
            }
        }

        $output = [
            '$schema' => self::SCHEMA,
            'version' => self::VERSION,
            'runs' => [
                [
                    'tool' => [
                        'driver' => [
                            'name' => self::TOOL_NAME,
                            'informationUri' => self::INFORMATION_URI,
                            'rules' => $rules,
                        ],
                    ],
                    'automationDetails' => [
                        'id' => 'laravel-safeguard/'.$environment,
                    ],
                    'invocations' => [
                        [
                            'executionSuccessful' => true,
                            'endTimeUtc' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
                        ],
                    ],
                    'results' => $sarifResults,
                    'properties' => [
                        'environment' => $environment,
                        'summary' => self::summarize($results),
                        'baseline' => self::baselineSummary($results),
                    ],
                ],
            ],
        ];

        return json_encode(
            $output,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * SARIF reportingDescriptor of a rule.
     *
     * @param  array<array-key, mixed>  $check
     * @return array<string, mixed>
     */
    private function ruleDescriptor(string $ruleId, array $check): array
    {
        $description = $this->stringify($check['description'] ?? '');
        $severity = $this->stringify($check['severity'] ?? 'error');

        return [
            'id' => $ruleId,
            'shortDescription' => [
                'text' => $description !== '' ? $description : $ruleId,
            ],
            'defaultConfiguration' => [
                'level' => $this->level($severity),
            ],
            'properties' => [
                'tags' => ['security'],
                'severity' => $severity,
                'security-severity' => $this->securitySeverity($severity),
            ],
        ];
    }

    /**
     * SARIF result of a failed check.
     *
     * @param  array<array-key, mixed>  $check
     * @return array<string, mixed>
     */
    private function result(string $ruleId, int $ruleIndex, SafeguardResult $result, array $check): array
    {
        $accepted = self::isAccepted($check);
        $baseline = self::baselineOf($check);

        $text = $result->message();
        $note = $this->baselineNote($check);
        $details = $this->detailsAsText($result->details());

        if ($note !== '') {
            $text .= "\n".$note;
        }

        if ($details !== '') {
            $text .= "\n\n".$details;
        }

        $properties = [
            'severity' => $result->severity(),
            'status' => $accepted ? 'accepted' : $this->checkStatus($result),
        ];

        if ($baseline !== null) {
            $properties['baseline'] = $baseline;
        }

        $sarif = [
            'ruleId' => $ruleId,
            'ruleIndex' => $ruleIndex,
            // Accepted findings never count as errors, even for tools ignoring suppressions
            'level' => $accepted ? 'note' : $this->level($result->severity()),
            'message' => [
                'text' => $text,
            ],
            'locations' => $this->locations($result->details()),
            'properties' => $properties,
        ];

        if ($accepted && $baseline !== null) {
            $sarif['suppressions'] = [
                [
                    'kind' => 'external',
                    'status' => 'accepted',
                    'justification' => $baseline['reason'],
                ],
            ];
        }

        return $sarif;
    }

    /**
     * SARIF level of a severity: unknown severities are errors, as in the other formats.
     */
    private function level(string $severity): string
    {
        return match ($severity) {
            'warning' => 'warning',
            'info' => 'note',
            default => 'error',
        };
    }

    /**
     * Score (0.0 to 10.0) used by code scanning to rank security findings.
     */
    private function securitySeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => '9.0',
            'warning' => '4.0',
            'info' => '0.0',
            default => '7.0',
        };
    }

    /**
     * SARIF locations found in the details of a result ("file" or "file_path"
     * keys, with an optional "line"), or the default location.
     *
     * @param  array<array-key, mixed>  $details
     * @return list<array<string, mixed>>
     */
    private function locations(array $details): array
    {
        $found = [];
        $this->collectLocations($details, $found, 0);

        if ($found === []) {
            $found[] = ['uri' => self::DEFAULT_LOCATION, 'line' => null];
        }

        $locations = [];

        foreach (array_slice(array_values($found), 0, self::MAX_LOCATIONS) as $location) {
            $physical = [
                'artifactLocation' => [
                    'uri' => $location['uri'],
                ],
            ];

            if ($location['line'] !== null) {
                $physical['region'] = ['startLine' => $location['line']];
            }

            $locations[] = ['physicalLocation' => $physical];
        }

        return $locations;
    }

    /**
     * Collect the distinct file locations (URI and optional line) of a details array.
     *
     * @param  array<array-key, mixed>  $details
     * @param  array<array-key, array{uri: string, line: int|null}>  $found
     */
    private function collectLocations(array $details, array &$found, int $depth): void
    {
        $file = $details['file'] ?? $details['file_path'] ?? null;

        if (is_string($file) && $file !== '') {
            $uri = $this->uri($file);

            if ($uri !== null) {
                $line = $details['line'] ?? null;
                $line = is_int($line) && $line > 0 ? $line : null;
                $found[$uri.'#'.($line ?? '')] = ['uri' => $uri, 'line' => $line];
            }
        }

        if ($depth >= self::MAX_DETAILS_DEPTH) {
            return;
        }

        foreach ($details as $value) {
            if (is_array($value)) {
                $this->collectLocations($value, $found, $depth + 1);
            }
        }
    }

    /**
     * Convert a file path to a URI relative to the project root. Returns null
     * for files outside of the project, which cannot be located in a repository.
     */
    private function uri(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        $base = mb_rtrim(str_replace('\\', '/', base_path()), '/');

        if (! $this->isAbsolute($path)) {
            $path = $this->resolveRelative($path, $base);
        }

        if ($this->isAbsolute($path)) {
            if (! str_starts_with($path, $base.'/')) {
                return null;
            }

            $path = mb_substr($path, mb_strlen($base) + 1);
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                return null;
            }

            $segments[] = rawurlencode($segment);
        }

        return $segments === [] ? null : implode('/', $segments);
    }

    /**
     * Some rules report paths relative to a scanned directory: find the
     * directory they belong to. Paths that cannot be resolved are kept as is.
     */
    private function resolveRelative(string $path, string $base): string
    {
        if (is_file($base.'/'.$path)) {
            return $path;
        }

        $scanPaths = config('safeguard.scan_paths', []);

        foreach (is_array($scanPaths) ? $scanPaths : [] as $scanPath) {
            if (! is_string($scanPath) || $scanPath === '') {
                continue;
            }

            $candidate = mb_rtrim(str_replace('\\', '/', $scanPath), '/').'/'.$path;

            if (! $this->isAbsolute($candidate)) {
                $candidate = $base.'/'.$candidate;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $path;
    }

    private function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path) === 1;
    }
}
