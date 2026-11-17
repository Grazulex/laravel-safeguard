<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Formatters;

use Grazulex\LaravelSafeguard\Baseline\BaselineEntry;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Collection;
use Stringable;

abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * Count passed checks, errors (error/critical/other) and warnings.
     *
     * Failed checks accepted by the baseline are counted neither as errors
     * nor as warnings: see baselineSummary().
     *
     * @return array{total: int, passed: int, errors: int, warnings: int}
     */
    public static function summarize(Collection $results): array
    {
        $passed = 0;
        $errors = 0;
        $warnings = 0;

        foreach ($results as $check) {
            $result = $check['result'];

            if (! $result instanceof SafeguardResult) {
                continue;
            }

            if (self::isAccepted($check)) {
                continue;
            }

            if ($result->passed()) {
                $passed++;
            } elseif ($result->severity() === 'warning') {
                $warnings++;
            } else {
                $errors++;
            }
        }

        return [
            'total' => $results->count(),
            'passed' => $passed,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Count failed checks accepted by the baseline and failed checks whose
     * baseline exception has expired (the latter are also counted as
     * errors/warnings by summarize()).
     *
     * @return array{accepted: int, expired: int}
     */
    public static function baselineSummary(Collection $results): array
    {
        $accepted = 0;
        $expired = 0;

        foreach ($results as $check) {
            $baseline = self::baselineOf($check);

            if ($baseline === null) {
                continue;
            }

            if ($baseline['status'] === BaselineEntry::STATUS_ACCEPTED) {
                $accepted++;
            } elseif ($baseline['status'] === BaselineEntry::STATUS_EXPIRED) {
                $expired++;
            }
        }

        return [
            'accepted' => $accepted,
            'expired' => $expired,
        ];
    }

    /**
     * Whether a check is a failed finding accepted by the baseline (not expired).
     */
    public static function isAccepted(mixed $check): bool
    {
        return (self::baselineOf($check)['status'] ?? null) === BaselineEntry::STATUS_ACCEPTED;
    }

    /**
     * Whether a check is a failed finding whose baseline exception has expired.
     */
    public static function isExpired(mixed $check): bool
    {
        return (self::baselineOf($check)['status'] ?? null) === BaselineEntry::STATUS_EXPIRED;
    }

    /**
     * Baseline annotation of a failed check, if any.
     *
     * @return array{status: string, reason: string, expires: string}|null
     */
    public static function baselineOf(mixed $check): ?array
    {
        if (! is_array($check) || ! isset($check['baseline']) || ! is_array($check['baseline'])) {
            return null;
        }

        $result = $check['result'] ?? null;

        if (! $result instanceof SafeguardResult || $result->passed()) {
            return null;
        }

        $baseline = $check['baseline'];

        return [
            'status' => (string) ($baseline['status'] ?? ''),
            'reason' => (string) ($baseline['reason'] ?? ''),
            'expires' => (string) ($baseline['expires'] ?? ''),
        ];
    }

    /**
     * Human readable note describing the baseline annotation of a check.
     */
    protected function baselineNote(mixed $check): string
    {
        $baseline = self::baselineOf($check);

        if ($baseline === null) {
            return '';
        }

        return $baseline['status'] === BaselineEntry::STATUS_EXPIRED
            ? "Baseline exception expired on {$baseline['expires']}: {$baseline['reason']}"
            : "Accepted in baseline until {$baseline['expires']}: {$baseline['reason']}";
    }

    /**
     * Overall status of a run: failed, warning or passed.
     *
     * @param  array{total: int, passed: int, errors: int, warnings: int}  $summary
     */
    protected function status(array $summary): string
    {
        if ($summary['errors'] > 0) {
            return 'failed';
        }

        return $summary['warnings'] > 0 ? 'warning' : 'passed';
    }

    /**
     * Status of a single check: passed, warning or failed.
     */
    protected function checkStatus(SafeguardResult $result): string
    {
        if ($result->passed()) {
            return 'passed';
        }

        return $result->severity() === 'warning' ? 'warning' : 'failed';
    }

    /**
     * Human readable label for a details key (e.g. "security_impact" => "Security Impact").
     */
    protected function label(string|int $key): string
    {
        return ucwords(str_replace('_', ' ', (string) $key));
    }

    /**
     * Convert a scalar or nested details value to a printable string.
     */
    protected function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return $json === false ? '' : $json;
    }

    /**
     * Render result details as plain text lines.
     */
    protected function detailsAsText(array $details): string
    {
        $lines = [];

        foreach ($details as $key => $value) {
            $label = $this->label($key);

            if (is_array($value) && array_is_list($value)) {
                $lines[] = $label.':';

                foreach ($value as $item) {
                    $lines[] = '  - '.$this->stringify($item);
                }

                continue;
            }

            $lines[] = $label.': '.$this->stringify($value);
        }

        return implode("\n", $lines);
    }
}
