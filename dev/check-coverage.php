<?php

declare(strict_types=1);

// Global line-coverage floor for CI (ADR-0008). Usage:
//   php dev/check-coverage.php coverage.xml 60
// Reads the clover report's project totals; per-layer targets stay a fork
// policy — one honest global gate beats four fictional ones.

$file = $argv[1] ?? 'coverage.xml';
$floor = (float) ($argv[2] ?? 60);

if (!is_file($file)) {
    fwrite(STDERR, "check-coverage: clover report '$file' not found\n");
    exit(2);
}

$xml = simplexml_load_file($file);
if ($xml === false || !isset($xml->project->metrics)) {
    fwrite(STDERR, "check-coverage: cannot parse '$file'\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$total = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($total === 0) {
    fwrite(STDERR, "check-coverage: no statements found — is coverage enabled?\n");
    exit(2);
}

$pct = round($covered / $total * 100, 2);
printf("check-coverage: %.2f%% statements covered (floor %.2f%%)\n", $pct, $floor);

exit($pct + 0.0001 >= $floor ? 0 : 1);
