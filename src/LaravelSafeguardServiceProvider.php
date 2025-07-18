<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard;

use Grazulex\LaravelSafeguard\Console\Commands\MakeRuleCommand;
use Grazulex\LaravelSafeguard\Console\Commands\SafeguardCheckCommand;
use Grazulex\LaravelSafeguard\Console\Commands\SafeguardListCommand;
use Grazulex\LaravelSafeguard\Rules\Authentication\PasswordPolicyCompliance;
use Grazulex\LaravelSafeguard\Rules\Authentication\SessionSecuritySettings;
use Grazulex\LaravelSafeguard\Rules\Authentication\TwoFactorAuthEnabled;
use Grazulex\LaravelSafeguard\Rules\Configuration\AppKeyIsSet;
use Grazulex\LaravelSafeguard\Rules\Database\DatabaseBackupSecurity;
use Grazulex\LaravelSafeguard\Rules\Database\DatabaseConnectionEncrypted;
use Grazulex\LaravelSafeguard\Rules\Database\DatabaseCredentialsNotDefault;
use Grazulex\LaravelSafeguard\Rules\Database\DatabaseQueryLogging;
use Grazulex\LaravelSafeguard\Rules\Encryption\EncryptionKeyRotation;
use Grazulex\LaravelSafeguard\Rules\Encryption\SensitiveDataEncryption;
use Grazulex\LaravelSafeguard\Rules\Environment\AppDebugFalseInProduction;
use Grazulex\LaravelSafeguard\Rules\Environment\EnvHasAllRequiredKeys;
use Grazulex\LaravelSafeguard\Rules\FileSystem\EnvFilePermissions;
use Grazulex\LaravelSafeguard\Rules\Security\ComposerPackageSecurity;
use Grazulex\LaravelSafeguard\Rules\Security\CsrfEnabled;
use Grazulex\LaravelSafeguard\Rules\Security\NoSecretsInCode;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;
use Illuminate\Support\ServiceProvider;

final class LaravelSafeguardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/safeguard.php' => config_path('safeguard.php'),
        ], 'safeguard-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SafeguardCheckCommand::class,
                SafeguardListCommand::class,
                MakeRuleCommand::class,
            ]);
        }

        $this->registerDefaultRules();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/safeguard.php', 'safeguard');

        $this->app->singleton(SafeguardManager::class, function (): SafeguardManager {
            return new SafeguardManager();
        });
    }

    /**
     * Register the default security rules.
     */
    private function registerDefaultRules(): void
    {
        $manager = $this->app->make(SafeguardManager::class);

        // Environment Rules
        $manager->registerRule(new AppDebugFalseInProduction());
        $manager->registerRule(new EnvHasAllRequiredKeys());

        // Configuration Rules
        $manager->registerRule(new AppKeyIsSet());

        // Security Rules
        $manager->registerRule(new NoSecretsInCode());
        $manager->registerRule(new CsrfEnabled());
        $manager->registerRule(new ComposerPackageSecurity());

        // File System Rules
        $manager->registerRule(new EnvFilePermissions());

        // Database Rules
        $manager->registerRule(new DatabaseConnectionEncrypted());
        $manager->registerRule(new DatabaseCredentialsNotDefault());
        $manager->registerRule(new DatabaseBackupSecurity());
        $manager->registerRule(new DatabaseQueryLogging());

        // Authentication Rules
        $manager->registerRule(new PasswordPolicyCompliance());
        $manager->registerRule(new TwoFactorAuthEnabled());
        $manager->registerRule(new SessionSecuritySettings());

        // Encryption Rules
        $manager->registerRule(new EncryptionKeyRotation());
        $manager->registerRule(new SensitiveDataEncryption());

        // Load custom rules from the configured path
        $manager->loadCustomRules();
    }
}
