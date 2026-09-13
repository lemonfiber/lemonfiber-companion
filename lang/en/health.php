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
    'unreachable' => 'This stack cannot be reached from here.',
    'unreachable_action' => 'Check that the stack is on and on the same network.',
    'stale' => 'Last checked :ago',
    'no_findings' => 'Nothing needs attention.',
    'nothing_to_report' => 'Every check passed.',
    'repair_refused' => 'The stack refused that repair: :reason',
    'nothing_to_try' => 'The stack did not suggest anything to try for this.',
];
