<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

abstract class AbstractSafeguardRule implements SafeguardRule
{
    /**
     * Default implementation for formatting details.
     * Rules can override this method for custom formatting.
     */
    public function formatDetails(SafeguardResult $result): array
    {
        $details = $result->details();
        $lines = [];

        if ($details === []) {
            return $lines;
        }

        // Format special keys with better labels
        $formatMap = [
            'current_setting' => ['💡', 'Current Setting'],
            'recommendation' => ['💡', 'Recommendation'],
            'security_impact' => ['⚠️', 'Security Impact'],
            'issues' => ['📋', 'Issues Found'],
            'recommendations' => ['📋', 'Recommendations'],
            'vulnerable_connections' => ['📋', 'Vulnerable Connections'],
            'secure_connections' => ['📋', 'Secure Connections'],
            'file_path' => ['📁', 'File Path'],
            'current_permissions' => ['📌', 'Current Permissions'],
            'recommended_permissions' => ['📌', 'Recommended Permissions'],
            'total_packages' => ['📌', 'Total Packages'],
            'total_issues' => ['📌', 'Total Issues'],
            'total_connections' => ['📌', 'Total Connections'],
        ];

        foreach ($details as $key => $value) {
            [$icon, $label] = $formatMap[$key] ?? ['📌', ucwords(str_replace('_', ' ', $key))];

            if (is_array($value)) {
                $lines[] = "   📋 {$label}:";

                foreach ($value as $item) {
                    if (is_array($item)) {
                        $lines = array_merge($lines, $this->formatArrayItem($item));
                    } elseif ($item !== '' && $item !== null && $item !== 0) {
                        // Skip empty or meaningless values
                        $lines[] = "     • {$item}";
                    }
                }
            } else {
                $lines[] = "   {$icon} {$label}: {$value}";
            }
        }

        return $lines;
    }

    /**
     * Format array items (can be overridden by specific rules).
     */
    protected function formatArrayItem(array $item): array
    {
        $lines = [];

        // Check if it's a structured vulnerability/issue object
        if (isset($item['type']) && isset($item['severity'])) {
            $severity = mb_strtoupper($item['severity']);
            $type = ucwords(str_replace('_', ' ', $item['type']));

            $line = "     🔍 [{$severity}] {$type}";

            if (isset($item['package'])) {
                $line .= " - Package: {$item['package']}";
                if (isset($item['version'])) {
                    $line .= " ({$item['version']})";
                }
            }

            if (isset($item['connection'])) {
                $line .= " - Connection: {$item['connection']}";
            }

            $lines[] = $line;

            if (isset($item['message'])) {
                $lines[] = "       📝 {$item['message']}";
            }

            if (isset($item['reason'])) {
                $lines[] = "       📋 Reason: {$item['reason']}";
            }

            if (isset($item['risk'])) {
                $lines[] = "       ⚠️  Risk: {$item['risk']}";
            }

        } elseif (isset($item['connection'])) {
            // Database connection format
            $line = "     🔗 Connection: {$item['connection']}";
            if (isset($item['driver'])) {
                $line .= " (Driver: {$item['driver']})";
            }
            $lines[] = $line;

            if (isset($item['reason'])) {
                $lines[] = "       📋 Reason: {$item['reason']}";
            }

        } else {
            // For simple arrays, show as key-value pairs
            $parts = [];
            foreach ($item as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $parts[] = "{$key}: {$value}";
                }
            }
            if ($parts !== []) {
                $lines[] = '     • '.implode(' | ', $parts);
            }
        }

        return $lines;
    }

    /**
     * Check if a value is empty (null, empty string, empty array, whitespace only, or false).
     */
    protected function isEmpty($value): bool
    {
        if ($value === null || $value === '' || $value === [] || $value === false) {
            return true;
        }

        if (is_string($value) && mb_strlen(mb_trim($value)) === 0) {
            return true;
        }

        return $value === [];
    }
}
