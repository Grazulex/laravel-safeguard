<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRuleCommand extends Command
{
    protected $signature = 'safeguard:make-rule {name} {--severity=info : The severity level (info, warning, error)}';

    protected $description = 'Create a new Safeguard rule';

    public function handle(): int
    {
        $name = $this->argument('name');
        $severity = $this->option('severity');

        // Validate severity
        if (! in_array($severity, ['info', 'warning', 'error'])) {
            $this->error('Invalid severity. Must be one of: error, warning, info');

            return self::FAILURE;
        }

        // Convert name to class name format
        $className = Str::studly($name);
        $filename = $className.'.php';

        // Create in base Rules directory for simplicity
        $fullPath = app_path('Safeguard/Rules');

        // Ensure directory exists
        File::ensureDirectoryExists($fullPath);

        $filePath = $fullPath.'/'.$filename;

        // Check if file already exists
        if (File::exists($filePath)) {
            $this->error("Rule {$className} already exists at {$filePath}");

            return self::FAILURE;
        }

        // Generate the rule content
        $stub = $this->getStub();
        $content = str_replace(
            ['{{namespace}}', '{{class}}', '{{severity}}', '{{id}}', '{{description}}'],
            [
                'App\\Safeguard\\Rules',
                $className,
                $severity,
                Str::kebab($name),
                $this->generateDescription($name),
            ],
            $stub
        );

        // Write the file
        File::put($filePath, $content);

        $this->info('Rule created successfully.');
        $this->comment("Don't forget to register your rule in a service provider or configure it in config/safeguard.php");

        return self::SUCCESS;
    }

    private function generateDescription(string $name): string
    {
        return 'Checks '.Str::lower(Str::snake($name, ' '));
    }

    private function getStub(): string
    {
        $stubPath = __DIR__.'/stubs/rule.stub';

        if (File::exists($stubPath)) {
            return File::get($stubPath);
        }

        // Fallback inline stub
        return '<?php

declare(strict_types=1);

namespace {{namespace}};

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class {{class}} implements SafeguardRule
{
    public function id(): string
    {
        return \'{{id}}\';
    }

    public function description(): string
    {
        return \'{{description}}\';
    }

    public function check(): SafeguardResult
    {
        // TODO: Implement your rule logic here
        
        // Example of a passing check:
        // return SafeguardResult::pass(\'Check passed\');
        
        // Example of a failing check:
        // return SafeguardResult::fail(\'Check failed\', [\'details\' => \'Additional context\']);
        
        return SafeguardResult::pass(\'Rule not yet implemented\');
    }

    public function appliesToEnvironment(string $environment): bool
    {
        // Return true to run in all environments, or customize as needed
        return true;
    }

    public function severity(): string
    {
        return \'{{severity}}\';
    }
}
';
    }
}
