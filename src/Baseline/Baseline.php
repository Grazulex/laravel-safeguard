<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Baseline;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Collection;
use JsonException;

/**
 * Accepted findings loaded from a baseline file (safeguard-baseline.json).
 *
 * File format:
 *
 *     {
 *         "accepted": [
 *             {"rule": "csrf-enabled", "reason": "Legacy webhook endpoint", "expires": "2026-12-31"}
 *         ]
 *     }
 *
 * A failed check whose rule has a valid (not expired) entry is "accepted" and
 * no longer blocks; a failed check whose entry has expired is flagged and
 * blocks again like any other finding.
 */
class Baseline
{
    /**
     * @var array<string, BaselineEntry>
     */
    private array $entries = [];

    /**
     * @param  array<int, BaselineEntry>  $entries
     */
    public function __construct(array $entries = [])
    {
        foreach ($entries as $entry) {
            $this->entries[$entry->rule] = $entry;
        }
    }

    /**
     * Load the baseline configured in safeguard.baseline.
     * Returns null when the baseline is disabled or the file does not exist.
     *
     * @throws InvalidBaselineException When the file exists but is invalid.
     */
    public static function fromConfig(): ?self
    {
        if (! (bool) config('safeguard.baseline.enabled', true)) {
            return null;
        }

        $path = config('safeguard.baseline.path');

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return null;
        }

        return self::fromFile($path);
    }

    /**
     * @throws InvalidBaselineException When the file is missing, unreadable or invalid.
     */
    public static function fromFile(string $path): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidBaselineException("Baseline file not found or not readable: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidBaselineException("Unable to read baseline file: {$path}");
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidBaselineException("Invalid JSON in baseline file {$path}: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($data)) {
            throw new InvalidBaselineException("Invalid baseline file {$path}: expected a JSON object.");
        }

        try {
            return self::fromArray($data);
        } catch (InvalidBaselineException $e) {
            throw new InvalidBaselineException("Invalid baseline file {$path}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Build a baseline from decoded data: either {"accepted": [...]} or a plain list of entries.
     *
     * @throws InvalidBaselineException When an entry is invalid.
     */
    public static function fromArray(array $data): self
    {
        if (! array_is_list($data)) {
            if (! array_key_exists('accepted', $data)) {
                throw new InvalidBaselineException('missing "accepted" list.');
            }

            $data = $data['accepted'];

            if (! is_array($data) || ! array_is_list($data)) {
                throw new InvalidBaselineException('"accepted" must be a list.');
            }
        }

        $entries = [];
        $seen = [];

        foreach ($data as $index => $item) {
            $entry = self::parseEntry($item, $index);

            if (isset($seen[$entry->rule])) {
                throw new InvalidBaselineException("duplicate entry for rule \"{$entry->rule}\".");
            }

            $seen[$entry->rule] = true;
            $entries[] = $entry;
        }

        return new self($entries);
    }

    /**
     * @return array<string, BaselineEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    public function entryFor(string $rule): ?BaselineEntry
    {
        return $this->entries[$rule] ?? null;
    }

    /**
     * Annotate failed checks covered by the baseline with a "baseline" key
     * ({status: accepted|expired, reason, expires}). Passed checks and checks
     * without an entry are returned unchanged.
     */
    public function apply(Collection $results, ?DateTimeInterface $today = null): Collection
    {
        return $results->map(function (mixed $check) use ($today): mixed {
            if (! is_array($check) || ! isset($check['rule'], $check['result'])) {
                return $check;
            }

            $result = $check['result'];

            if (! $result instanceof SafeguardResult || $result->passed()) {
                return $check;
            }

            $entry = $this->entryFor((string) $check['rule']);

            if (! $entry instanceof BaselineEntry) {
                return $check;
            }

            $check['baseline'] = $entry->toAnnotation($today);

            return $check;
        });
    }

    private static function parseEntry(mixed $item, int $index): BaselineEntry
    {
        $position = 'entry #'.($index + 1);

        if (! is_array($item)) {
            throw new InvalidBaselineException("{$position} must be an object.");
        }

        foreach (['rule', 'reason', 'expires'] as $field) {
            if (! isset($item[$field]) || ! is_string($item[$field]) || mb_trim($item[$field]) === '') {
                throw new InvalidBaselineException("{$position} requires a non-empty \"{$field}\" string.");
            }
        }

        $expires = mb_trim($item['expires']);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $expires, $matches) !== 1
            || ! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            throw new InvalidBaselineException("{$position} has an invalid \"expires\" date \"{$expires}\" (expected YYYY-MM-DD).");
        }

        return new BaselineEntry(
            mb_trim($item['rule']),
            mb_trim($item['reason']),
            CarbonImmutable::parse($expires)->startOfDay(),
        );
    }
}
