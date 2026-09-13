<?php

declare(strict_types=1);

return [
    'category' => [
        'environment' => 'Environment',
        'storage' => 'Storage',
        'network' => 'Network',
        'vpn' => 'VPN',
        'credentials' => 'Credentials',
        'services' => 'Services',
        'providers' => 'Providers',
        'queue' => 'Queue',
        'config' => 'Configuration',
    ],
    'conclusion' => [
        'fail' => 'Failed',
        'unverified' => 'Could not be checked',
        'warn' => 'Needs attention',
        'pass' => 'Passed',
        'skipped' => 'Skipped',
    ],
    'severity' => [
        'critical' => 'Data or something outside this machine is at risk',
        'error' => 'Broken',
        'warning' => 'Degraded',
        'advisory' => 'Worth knowing',
    ],
    'undoing' => [
        'permanent' => 'This cannot be undone',
        'possible' => 'This can be undone afterwards',
    ],
    'overall' => [
        'broken' => 'Something is broken',
        'unknown' => 'Health could not be determined',
        'degraded' => 'Some services need attention',
        'healthy' => 'Everything is running',
    ],
    'because_of' => 'Because of: :title',
    'ago' => [
        'minutes' => '{0} moments ago|{1} a minute ago|[2,*] :count minutes ago',
        'hours' => '{1} an hour ago|[2,*] :count hours ago',
        'days' => '{1} a day ago|[2,*] :count days ago',
    ],
    'stale' => 'Last checked :ago',
    'family_and_count' => ':family (:count)',
    'no_findings' => 'Nothing needs attention.',
    'repair_refused' => 'The stack refused that repair: :reason',
    'nothing_to_try' => 'The stack did not suggest anything to try for this.',
    'ask_again' => 'Check again',
    'see_how_it_is' => 'See how this stack is doing',
];
