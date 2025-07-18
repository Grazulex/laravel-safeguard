<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Encryption;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;

class SensitiveDataEncryption implements SafeguardRule
{
    private const SENSITIVE_FIELD_PATTERNS = [
        'password', 'secret', 'token', 'key', 'ssn', 'social_security',
        'credit_card', 'card_number', 'cvv', 'cvv2', 'expiry',
        'bank_account', 'iban', 'swift', 'routing_number',
        'phone', 'email', 'address', 'postal_code', 'zip_code',
        'date_of_birth', 'birth_date', 'dob', 'age',
        'salary', 'income', 'tax_id', 'passport', 'license',
        'medical_record', 'health_record', 'diagnosis',
        'api_key', 'access_token', 'refresh_token', 'oauth',
        'private_key', 'public_key', 'certificate',
    ];

    private const ENCRYPTION_INDICATORS = [
        'encrypted', 'hashed', 'cipher', 'crypt', 'encode',
        'Encryptable', 'Encrypted', 'HasEncrypted',
    ];

    public function id(): string
    {
        return 'sensitive-data-encryption';
    }

    public function description(): string
    {
        return 'Scans models for sensitive fields that should be encrypted';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        $recommendations = [];
        $scanResults = [];

        // Scan Eloquent models for sensitive fields
        $this->scanEloquentModels($issues, $recommendations, $scanResults);

        // Check encryption implementation
        $this->checkEncryptionImplementation($issues, $recommendations, $scanResults);

        // Check database schema for sensitive data
        $this->checkDatabaseSchema($issues, $recommendations, $scanResults);

        if ($issues !== []) {
            $severity = $this->determineSeverity($issues);

            return SafeguardResult::fail(
                'Sensitive data encryption issues detected',
                $severity,
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'scan_results' => $scanResults,
                    'total_issues' => count($issues),
                ]
            );
        }

        return SafeguardResult::pass(
            'Sensitive data encryption appears properly implemented',
            [
                'scan_results' => $scanResults,
                'security_level' => $this->getEncryptionSecurityLevel($scanResults),
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true;
    }

    public function severity(): string
    {
        return 'error';
    }

    /**
     * @param  array<array<string, mixed>|string>  $issues
     * @param  array<string>  $recommendations
     * @param  array<string, mixed>  $scanResults
     */
    private function scanEloquentModels(array &$issues, array &$recommendations, array &$scanResults): void
    {
        $modelPaths = $this->getModelPaths();
        $modelsScanned = 0;
        $modelsWithIssues = 0;
        $unencryptedFields = [];

        foreach ($modelPaths as $modelPath) {
            if (! File::exists($modelPath)) {
                continue;
            }

            $modelClass = $this->getModelClassFromPath($modelPath);
            if ($modelClass === null) {
                continue;
            }
            if ($modelClass === '') {
                continue;
            }
            if ($modelClass === '0') {
                continue;
            }

            $modelsScanned++;
            $modelIssues = $this->scanModelForSensitiveFields($modelClass, $modelPath);

            if ($modelIssues !== []) {
                $modelsWithIssues++;
                $unencryptedFields = array_merge($unencryptedFields, $modelIssues);

                foreach ($modelIssues as $field) {
                    $issues[] = [
                        'type' => 'unencrypted_sensitive_field',
                        'severity' => $this->getFieldSeverity($field['field_name']),
                        'message' => "Potentially sensitive field not encrypted: {$field['model']}::{$field['field_name']}",
                        'model' => $field['model'],
                        'field' => $field['field_name'],
                        'risk' => $this->getFieldRisk($field['field_name']),
                    ];
                }
            }
        }

        $scanResults['models_scanned'] = $modelsScanned;
        $scanResults['models_with_issues'] = $modelsWithIssues;
        $scanResults['unencrypted_fields'] = $unencryptedFields;

        if ($modelsWithIssues > 0) {
            $recommendations[] = 'Implement field-level encryption for sensitive data';
            $recommendations[] = 'Consider using Laravel\'s Eloquent encryption packages';
            $recommendations[] = 'Use Eloquent mutators/accessors for automatic encryption/decryption';
        }
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function scanModelForSensitiveFields(string $modelClass, string $modelPath): array
    {
        $issues = [];

        try {
            if (! class_exists($modelClass)) {
                return $issues;
            }

            $reflection = new ReflectionClass($modelClass);
            $model = $reflection->newInstanceWithoutConstructor();

            // Get fillable fields
            $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];

            // Get casts
            $casts = method_exists($model, 'getCasts') ? $model->getCasts() : [];

            // Get hidden fields
            $hidden = method_exists($model, 'getHidden') ? $model->getHidden() : [];

            // Check each fillable field
            foreach ($fillable as $field) {
                if ($this->isSensitiveField($field)) {
                    // Check if field is encrypted/protected
                    $isProtected = $this->isFieldProtected($field, $casts, $hidden, $modelPath);

                    if (! $isProtected) {
                        $issues[] = [
                            'model' => $modelClass,
                            'field_name' => $field,
                            'protection_status' => 'unprotected',
                        ];
                    }
                }
            }

        } catch (ReflectionException $e) {
            // Skip models that can't be reflected
        }

        return $issues;
    }

    private function isSensitiveField(string $fieldName): bool
    {
        $normalized = mb_strtolower($fieldName);

        foreach (self::SENSITIVE_FIELD_PATTERNS as $pattern) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $casts
     * @param  array<string>  $hidden
     */
    private function isFieldProtected(string $field, array $casts, array $hidden, string $modelPath): bool
    {
        // Check if field is hidden
        if (in_array($field, $hidden)) {
            return true;
        }

        // Check if field has encryption cast
        $fieldCast = $casts[$field] ?? '';
        if (str_contains(mb_strtolower($fieldCast), 'encrypt')) {
            return true;
        }

        // Check for encryption-related code in the model file
        $modelContent = File::get($modelPath);

        // Look for encryption indicators related to this field
        foreach (self::ENCRYPTION_INDICATORS as $indicator) {
            if (str_contains($modelContent, $indicator) &&
                str_contains($modelContent, $field)) {
                return true;
            }
        }

        // Check for custom mutators/accessors
        $mutatorMethod = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field))).'Attribute';
        $accessorMethod = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field))).'Attribute';

        if (str_contains($modelContent, $mutatorMethod) ||
            str_contains($modelContent, $accessorMethod)) {
            // If custom accessor/mutator exists, assume it might handle encryption
            return str_contains($modelContent, 'encrypt') ||
                   str_contains($modelContent, 'decrypt') ||
                   str_contains($modelContent, 'hash');
        }

        return false;
    }

    /**
     * @param  array<array<string, mixed>|string>  $issues
     * @param  array<string>  $recommendations
     * @param  array<string, mixed>  $scanResults
     */
    private function checkEncryptionImplementation(array &$issues, array &$recommendations, array &$scanResults): void
    {
        $encryptionPackages = [
            'austinheap/laravel-database-encryption' => 'Laravel Database Encryption',
            'spatie/laravel-encrypted-casting' => 'Spatie Encrypted Casting',
            'netsells/laravel-eloquent-encryption' => 'Laravel Eloquent Encryption',
            'pragmarx/encryption' => 'PragmaRX Encryption',
        ];

        $installedPackages = [];

        foreach ($encryptionPackages as $package => $name) {
            if ($this->isPackageInstalled($package)) {
                $installedPackages[] = $name;
            }
        }

        $scanResults['encryption_packages'] = $installedPackages;

        if ($installedPackages === [] && ! empty($scanResults['unencrypted_fields'] ?? [])) {
            $issues[] = [
                'type' => 'no_encryption_package',
                'severity' => 'warning',
                'message' => 'Sensitive fields detected but no encryption package installed',
                'risk' => 'Manual encryption implementation may be error-prone',
            ];
            $recommendations[] = 'Install a field-level encryption package for robust encryption';
        }

        // Check for custom encryption implementation
        $hasCustomEncryption = $this->hasCustomEncryptionImplementation();
        $scanResults['custom_encryption'] = $hasCustomEncryption;

        if (! $hasCustomEncryption && $installedPackages === []) {
            $issues[] = [
                'type' => 'no_encryption_implementation',
                'severity' => 'error',
                'message' => 'No encryption implementation detected for sensitive data',
            ];
            $recommendations[] = 'Implement field-level encryption for sensitive data';
        }
    }

    /**
     * @param  array<array<string, mixed>|string>  $issues
     * @param  array<string>  $recommendations
     * @param  array<string, mixed>  $scanResults
     */
    private function checkDatabaseSchema(array &$issues, array &$recommendations, array &$scanResults): void
    {
        // Check for migration files that might indicate sensitive data storage
        $migrationPath = database_path('migrations');
        $suspiciousMigrations = [];

        if (File::exists($migrationPath)) {
            $migrations = File::files($migrationPath);

            foreach ($migrations as $migration) {
                $content = File::get($migration->getPathname());

                foreach (self::SENSITIVE_FIELD_PATTERNS as $pattern) {
                    if (str_contains(mb_strtolower($content), $pattern)) {
                        $suspiciousMigrations[] = [
                            'file' => $migration->getFilename(),
                            'pattern' => $pattern,
                        ];
                        break;
                    }
                }
            }
        }

        $scanResults['suspicious_migrations'] = $suspiciousMigrations;

        if ($suspiciousMigrations !== []) {
            $issues[] = [
                'type' => 'sensitive_data_in_migrations',
                'severity' => 'info',
                'message' => 'Migration files contain potentially sensitive field names',
                'count' => count($suspiciousMigrations),
            ];
            $recommendations[] = 'Review migration files for sensitive data handling';
        }
    }

    /**
     * @return array<string>
     */
    private function getModelPaths(): array
    {
        $paths = [];
        $modelPath = app_path('Models');

        if (File::exists($modelPath)) {
            $files = File::allFiles($modelPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $paths[] = $file->getPathname();
                }
            }
        }

        // Also check app root for models (older Laravel versions)
        $appFiles = File::glob(app_path('*.php'));
        foreach ($appFiles as $file) {
            $content = File::get($file);
            if (str_contains($content, 'extends Model') ||
                str_contains($content, 'use Illuminate\Database\Eloquent\Model')) {
                $paths[] = $file;
            }
        }

        return $paths;
    }

    private function getModelClassFromPath(string $path): ?string
    {
        $content = File::get($path);

        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? 'App';

        // Extract class name
        preg_match('/class\s+([^\s]+)/', $content, $classMatches);
        $className = $classMatches[1] ?? null;

        if ($className === null) {
            return null;
        }

        return $namespace.'\\'.$className;
    }

    private function getFieldSeverity(string $fieldName): string
    {
        $criticalFields = ['password', 'secret', 'token', 'key', 'credit_card', 'ssn'];
        $errorFields = ['phone', 'email', 'bank_account', 'tax_id'];

        $normalized = mb_strtolower($fieldName);

        foreach ($criticalFields as $critical) {
            if (str_contains($normalized, $critical)) {
                return 'critical';
            }
        }

        foreach ($errorFields as $error) {
            if (str_contains($normalized, $error)) {
                return 'error';
            }
        }

        return 'warning';
    }

    private function getFieldRisk(string $fieldName): string
    {
        $normalized = mb_strtolower($fieldName);

        if (str_contains($normalized, 'password') || str_contains($normalized, 'secret')) {
            return 'Authentication bypass, unauthorized access';
        }

        if (str_contains($normalized, 'credit_card') || str_contains($normalized, 'bank')) {
            return 'Financial fraud, identity theft';
        }

        if (str_contains($normalized, 'ssn') || str_contains($normalized, 'tax_id')) {
            return 'Identity theft, compliance violations';
        }

        if (str_contains($normalized, 'phone') || str_contains($normalized, 'email')) {
            return 'Privacy violations, spam, social engineering';
        }

        return 'Privacy violations, data breaches';
    }

    private function hasCustomEncryptionImplementation(): bool
    {
        // Check for custom encryption traits or services
        if (File::exists(app_path('Traits/Encryptable.php'))) {
            return true;
        }
        if (File::exists(app_path('Services/EncryptionService.php'))) {
            return true;
        }

        return File::exists(app_path('Concerns/HasEncryption.php'));
    }

    private function isPackageInstalled(string $package): bool
    {
        $composerLock = base_path('composer.lock');

        if (! File::exists($composerLock)) {
            return false;
        }

        $content = File::get($composerLock);

        return str_contains($content, '"name": "'.$package.'"');
    }

    /**
     * @param  array<string, mixed>  $scanResults
     */
    private function getEncryptionSecurityLevel(array $scanResults): string
    {
        $totalFields = count($scanResults['unencrypted_fields'] ?? []);
        $hasEncryption = ! empty($scanResults['encryption_packages']) ||
                        ($scanResults['custom_encryption'] ?? false);
        $modelsScanned = $scanResults['models_scanned'] ?? 0;
        $modelsWithIssues = $scanResults['models_with_issues'] ?? 0;

        if ($modelsScanned === 0) {
            return 'unknown';
        }

        if ($totalFields === 0 && $hasEncryption) {
            return 'excellent';
        }

        if ($totalFields === 0) {
            return 'good';
        }

        if ($hasEncryption && $modelsWithIssues < $modelsScanned / 2) {
            return 'fair';
        }

        if ($hasEncryption) {
            return 'poor';
        }

        return 'critical';
    }

    /**
     * @param  array<array<string, mixed>|string>  $issues
     */
    private function determineSeverity(array $issues): string
    {
        foreach ($issues as $issue) {
            if (is_array($issue) && isset($issue['severity']) && $issue['severity'] === 'critical') {
                return 'critical';
            }
        }

        foreach ($issues as $issue) {
            if (is_array($issue) && isset($issue['severity']) && $issue['severity'] === 'error') {
                return 'error';
            }
        }

        return 'warning';
    }
}
