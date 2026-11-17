<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Formatters;

use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Collection;

/**
 * Self-contained HTML report (inline CSS, no JavaScript, no external assets).
 */
class HtmlFormatter extends AbstractFormatter
{
    public function format(Collection $results, string $environment): string
    {
        $summary = self::summarize($results);
        $baseline = self::baselineSummary($results);
        $status = $this->status($summary);
        $generatedAt = now()->toISOString();

        $rows = '';

        foreach ($results as $check) {
            $result = $check['result'];

            if (! $result instanceof SafeguardResult) {
                continue;
            }

            $rows .= $this->row((string) $check['rule'], (string) ($check['description'] ?? ''), $result, $check);
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="4" class="empty">No rules were executed.</td></tr>'."\n";
        }

        $e = fn (string $value): string => $this->escape($value);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laravel Safeguard Security Report - {$e($environment)}</title>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 2rem; background: #f6f7f9; color: #1f2933; }
main { max-width: 1100px; margin: 0 auto; }
h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
.meta { color: #616e7c; margin: 0 0 1.5rem; }
.cards { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
.card { flex: 1 1 150px; background: #fff; border-radius: 8px; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.card .value { font-size: 1.8rem; font-weight: 700; }
.card .label { color: #616e7c; text-transform: uppercase; font-size: .75rem; letter-spacing: .05em; }
.status-passed { color: #1e8e3e; }
.status-warning { color: #b7791f; }
.status-failed { color: #c53030; }
.status-accepted { color: #4a5568; }
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
th, td { text-align: left; padding: .75rem 1rem; border-bottom: 1px solid #e4e7eb; vertical-align: top; }
th { background: #f0f2f5; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #52606d; }
tr:last-child td { border-bottom: none; }
.badge { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .75rem; font-weight: 600; color: #fff; }
.badge-passed { background: #1e8e3e; }
.badge-warning { background: #d69e2e; }
.badge-failed { background: #c53030; }
.badge-accepted { background: #718096; }
.baseline { margin-top: .35rem; font-size: .85rem; font-style: italic; }
.baseline-accepted { color: #4a5568; }
.baseline-expired { color: #c53030; font-weight: 600; }
code { font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace; font-size: .85rem; }
.description { color: #616e7c; font-size: .85rem; }
details { margin-top: .5rem; }
summary { cursor: pointer; color: #3e4c59; font-size: .85rem; }
dl { margin: .5rem 0 0; }
dt { font-weight: 600; font-size: .85rem; }
dd { margin: 0 0 .5rem 1rem; font-size: .85rem; white-space: pre-wrap; word-break: break-word; }
dd ul { margin: 0; padding-left: 1rem; }
.empty { text-align: center; color: #616e7c; }
</style>
</head>
<body>
<main>
<h1>Laravel Safeguard Security Report</h1>
<p class="meta">Environment: <strong>{$e($environment)}</strong> &middot; Generated at {$e($generatedAt)} &middot; Status: <strong class="status-{$status}">{$status}</strong></p>
<section class="cards">
<div class="card"><div class="value">{$summary['total']}</div><div class="label">Total</div></div>
<div class="card"><div class="value status-passed">{$summary['passed']}</div><div class="label">Passed</div></div>
<div class="card"><div class="value status-failed">{$summary['errors']}</div><div class="label">Errors</div></div>
<div class="card"><div class="value status-warning">{$summary['warnings']}</div><div class="label">Warnings</div></div>
<div class="card"><div class="value status-accepted">{$baseline['accepted']}</div><div class="label">Accepted (baseline)</div></div>
<div class="card"><div class="value status-failed">{$baseline['expired']}</div><div class="label">Expired exceptions</div></div>
</section>
<table>
<thead><tr><th>Status</th><th>Rule</th><th>Severity</th><th>Message</th></tr></thead>
<tbody>
{$rows}</tbody>
</table>
</main>
</body>
</html>

HTML;
    }

    private function row(string $rule, string $description, SafeguardResult $result, mixed $check = null): string
    {
        $status = self::isAccepted($check) ? 'accepted' : $this->checkStatus($result);
        $details = $this->detailsAsHtml($result->details());

        $ruleCell = '<code>'.$this->escape($rule).'</code>';

        if ($description !== '') {
            $ruleCell .= '<div class="description">'.$this->escape($description).'</div>';
        }

        $messageCell = $this->escape($result->message());

        $note = $this->baselineNote($check);

        if ($note !== '') {
            $messageCell .= sprintf(
                '<div class="baseline baseline-%s">%s</div>',
                self::isExpired($check) ? 'expired' : 'accepted',
                $this->escape($note)
            );
        }

        if ($details !== '') {
            $messageCell .= '<details><summary>Details</summary>'.$details.'</details>';
        }

        return sprintf(
            '<tr class="check-%s"><td><span class="badge badge-%s">%s</span></td><td>%s</td><td>%s</td><td>%s</td></tr>'."\n",
            $status,
            $status,
            $status,
            $ruleCell,
            $this->escape($result->severity()),
            $messageCell
        );
    }

    private function detailsAsHtml(array $details): string
    {
        if ($details === []) {
            return '';
        }

        $html = '<dl>';

        foreach ($details as $key => $value) {
            $html .= '<dt>'.$this->escape($this->label($key)).'</dt><dd>';

            if (is_array($value) && array_is_list($value)) {
                $html .= '<ul>';

                foreach ($value as $item) {
                    $html .= '<li>'.$this->escape($this->stringify($item)).'</li>';
                }

                $html .= '</ul>';
            } else {
                $html .= $this->escape($this->stringify($value));
            }

            $html .= '</dd>';
        }

        return $html.'</dl>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
