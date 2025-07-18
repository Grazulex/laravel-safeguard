<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Security;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NoSecretsInCode implements SafeguardRule
{
    public function id(): string
    {
        return 'no-secrets-in-code';
    }

    public function description(): string
    {
        return 'Scans codebase for potentially hardcoded secrets';
    }

    public function check(): SafeguardResult
    {
        $scanPaths = config('safeguard.scan_paths', []);
        $secretPatterns = config('safeguard.secret_patterns', []);
        $foundSecrets = [];

        foreach ($scanPaths as $path) {
            // Skip directories that don't exist
            if (! is_dir($path)) {
                continue;
            }

            $files = File::allFiles($path);

            foreach ($files as $file) {
                // Skip vendor directories in production
                if (! app()->environment('testing') && Str::contains($file->getPathname(), 'vendor/')) {
                    continue;
                }

                // Only check PHP files
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = File::get($file->getPathname());
                $lines = explode("\n", $content);

                foreach ($lines as $lineNumber => $line) {
                    $secretInfo = $this->lineContainsSecret($line, $secretPatterns);
                    if ($secretInfo !== []) {
                        $foundSecrets[] = [
                            'file' => $file->getRelativePathname(),
                            'line' => $lineNumber + 1,
                            'pattern' => $secretInfo['pattern'],
                            'content' => $secretInfo['content'],
                        ];
                    }
                }
            }
        }

        if ($foundSecrets !== []) {
            return SafeguardResult::critical(
                'Potential secrets found in code files',
                [
                    'findings' => $foundSecrets,
                    'recommendation' => 'Move secrets to environment variables and remove them from code',
                ]
            );
        }

        return SafeguardResult::pass(
            'No hardcoded secrets detected in codebase',
            [
                'scanned_paths' => $scanPaths,
                'patterns_checked' => $secretPatterns,
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true;
    }

    public function severity(): string
    {
        return 'critical';
    }

    private function lineContainsSecret(string $line, array $patterns): array
    {
        $line = mb_trim($line);

        // Skip comments and empty lines
        if ($line === '' || $line === '0' || str_starts_with($line, '//') || str_starts_with($line, '#') || str_starts_with($line, '/*')) {
            return [];
        }

        foreach ($patterns as $pattern) {
            // Look for variable assignments with quoted strings
            $regex = '/(\$?'.preg_quote($pattern, '/').')\s*=\s*[\'"][^\'"\\s]+[\'\"]/i';

            if (preg_match($regex, $line)) {
                return [
                    'pattern' => $pattern,
                    'content' => $line,
                ];
            }
        }

        return [];
    }
}
