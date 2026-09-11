<?php

declare(strict_types=1);

return [
    'healthy' => 'Everything is running',
    'degraded' => 'Some services need attention',
    'unreachable' => 'This stack cannot be reached from here.',
    'unreachable_action' => 'Check that the stack is on and on the same network.',
    'stale' => 'Last checked :ago',
    'no_findings' => 'Nothing needs attention.',
    'repair_refused' => 'The stack refused that repair: :reason',
];
