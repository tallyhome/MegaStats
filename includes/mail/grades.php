<?php

declare(strict_types=1);

function ms_mail_grade_from_score(int $score): array
{
    $grade = match (true) {
        $score >= 97 => 'A+',
        $score >= 93 => 'A',
        $score >= 90 => 'A-',
        $score >= 87 => 'B+',
        $score >= 83 => 'B',
        $score >= 80 => 'B-',
        $score >= 77 => 'C+',
        $score >= 73 => 'C',
        $score >= 70 => 'C-',
        $score >= 60 => 'D',
        $score >= 50 => 'E',
        default => 'F',
    };

    $level = match (true) {
        $score >= 90 => 'good',
        $score >= 70 => 'warn',
        default => 'bad',
    };

    return ['grade' => $grade, 'level' => $level, 'score' => $score];
}

function ms_mail_score_breakdown(array $scan): array
{
    $items = [];
    $dns = $scan['dns'] ?? [];
    foreach (['spf' => 'SPF', 'dkim' => 'DKIM', 'dmarc' => 'DMARC', 'ptr' => 'PTR', 'fcrdns' => 'FCrDNS'] as $k => $label) {
        if (!isset($dns[$k])) {
            continue;
        }
        if (!($dns[$k]['ok'] ?? false)) {
            $items[] = ['label' => $label, 'delta' => -12, 'reason' => $dns[$k]['detail'] ?? 'KO'];
        }
    }
    $smtp = $scan['smtp'] ?? [];
    foreach (['banner' => 'Banner', 'helo' => 'HELO', 'tls' => 'TLS', 'helo_fcrdns' => 'HELO↔FCrDNS'] as $k => $label) {
        if (!isset($smtp[$k])) {
            continue;
        }
        if (!($smtp[$k]['ok'] ?? false)) {
            $items[] = ['label' => $label, 'delta' => -8, 'reason' => $smtp[$k]['detail'] ?? 'KO'];
        }
    }
    $listed = (int) ($scan['rbl_listed'] ?? 0);
    if ($listed > 0) {
        $items[] = ['label' => 'RBL', 'delta' => -min(40, $listed * 10), 'reason' => $listed . ' liste(s)'];
    }

    return $items;
}
