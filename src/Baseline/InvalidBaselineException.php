<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Baseline;

use RuntimeException;

/**
 * Thrown when a baseline file cannot be read or does not have the expected shape.
 */
class InvalidBaselineException extends RuntimeException {}
