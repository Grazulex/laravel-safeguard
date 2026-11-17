<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Baseline;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * A finding accepted in the baseline: a rule, the reason why the risk is
 * accepted and the last day (inclusive) the exception is valid.
 */
class BaselineEntry
{
    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_EXPIRED = 'expired';

    public function __construct(
        public readonly string $rule,
        public readonly string $reason,
        public readonly CarbonImmutable $expires,
    ) {}

    /**
     * The exception is valid up to and including its expiry date.
     */
    public function isExpired(?DateTimeInterface $today = null): bool
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return $today->greaterThan($this->expires->startOfDay());
    }

    /**
     * Annotation attached to a failed check covered by this entry.
     *
     * @return array{status: string, reason: string, expires: string}
     */
    public function toAnnotation(?DateTimeInterface $today = null): array
    {
        return [
            'status' => $this->isExpired($today) ? self::STATUS_EXPIRED : self::STATUS_ACCEPTED,
            'reason' => $this->reason,
            'expires' => $this->expires->format('Y-m-d'),
        ];
    }
}
