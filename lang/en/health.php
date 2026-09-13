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
    'repair_refused' => 'The stack refused that repair: :reason',
];
