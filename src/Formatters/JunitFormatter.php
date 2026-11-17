<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Formatters;

use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Collection;

/**
 * JUnit XML report, readable by GitLab, Jenkins, Azure DevOps, GitHub test reporters, etc.
 *
 * Every rule is a test case. Failed rules with an error/critical severity are
 * reported as <failure>; warnings do not fail the suite and are reported in
 * <system-out> so that CI pipelines keep the same semantics as the CLI output.
 */
class JunitFormatter extends AbstractFormatter
{
    public const SUITE_NAME = 'Laravel Safeguard';

    public function format(Collection $results, string $environment): string
    {
        $summary = self::summarize($results);
        $baseline = self::baselineSummary($results);
        $suiteName = self::SUITE_NAME.' ('.$environment.')';
        $classname = 'safeguard.'.$environment;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= sprintf(
            '<testsuites name="%s" tests="%d" failures="%d" errors="0">'."\n",
            $this->escape(self::SUITE_NAME),
            $summary['total'],
            $summary['errors']
        );
        $xml .= sprintf(
            '  <testsuite name="%s" tests="%d" failures="%d" errors="0" skipped="%d" timestamp="%s">'."\n",
            $this->escape($suiteName),
            $summary['total'],
            $summary['errors'],
            $baseline['accepted'],
            $this->escape(now()->format('Y-m-d\TH:i:s'))
        );
        $xml .= "    <properties>\n";
        $xml .= sprintf('      <property name="environment" value="%s"/>'."\n", $this->escape($environment));
        $xml .= sprintf('      <property name="warnings" value="%d"/>'."\n", $summary['warnings']);
        $xml .= sprintf('      <property name="baseline_accepted" value="%d"/>'."\n", $baseline['accepted']);
        $xml .= sprintf('      <property name="baseline_expired" value="%d"/>'."\n", $baseline['expired']);
        $xml .= "    </properties>\n";

        foreach ($results as $check) {
            $result = $check['result'];

            if (! $result instanceof SafeguardResult) {
                continue;
            }

            $xml .= $this->testcase((string) $check['rule'], $classname, $result, $check);
        }

        $xml .= "  </testsuite>\n";

        return $xml."</testsuites>\n";
    }

    private function testcase(string $rule, string $classname, SafeguardResult $result, mixed $check = null): string
    {
        $open = sprintf(
            '    <testcase name="%s" classname="%s"',
            $this->escape($rule),
            $this->escape($classname)
        );

        if ($result->passed()) {
            return $open."/>\n";
        }

        $note = $this->baselineNote($check);

        // Accepted findings are reported as skipped: they do not fail the suite.
        if (self::isAccepted($check)) {
            $text = '['.$result->severity().'] '.$result->message();

            return $open.">\n"
                .sprintf('      <skipped message="%s"/>'."\n", $this->escape($note))
                .'      <system-out>'.$this->escape($text)."</system-out>\n"
                ."    </testcase>\n";
        }

        $body = $this->detailsAsText($result->details());

        if ($note !== '') {
            $body = $note.($body !== '' ? "\n".$body : '');
        }

        if ($result->severity() === 'warning') {
            $text = '[warning] '.$result->message().($body !== '' ? "\n".$body : '');

            return $open.">\n"
                .'      <system-out>'.$this->escape($text)."</system-out>\n"
                ."    </testcase>\n";
        }

        $failure = sprintf(
            '      <failure message="%s" type="%s"',
            $this->escape($result->message()),
            $this->escape($result->severity())
        );

        $failure .= $body === ''
            ? "/>\n"
            : '>'.$this->escape($body)."</failure>\n";

        return $open.">\n".$failure."    </testcase>\n";
    }

    /**
     * Escape a value for XML text or attribute content and remove characters
     * that are not allowed in XML 1.0.
     */
    private function escape(string $value): string
    {
        $value = mb_scrub($value, 'UTF-8');
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
