<?php

declare(strict_types=1);

return [
    'title' => 'The outside watcher',
    'open' => 'The outside watcher',
    'blind' => 'The watcher is not answering, so everything below is uncovered',
    'watched_by' => 'Watched by :watcher, asked :ago',
    'monitors' => 'Monitors',
    'no_monitors' => 'The watcher holds no monitors',
    'said_by_the_watcher' => 'The watcher says: :state (since :ago)',
    'said_by_the_stack' => 'The stack says: :state',
    'they_disagree' => 'They disagree',
    'covers_this' => 'Covers :kind :name',
    'heartbeat_missing' => 'The heartbeat went missing :ago',
    'heartbeat_never' => 'The heartbeat has never arrived',
    'not_set_up' => 'Could not be set up to watch :wanted, so it watches :watches instead',
    'switched_off' => 'Switched off',
    'uncovered' => 'Nothing watches these',
    'nothing_uncovered' => 'Every critical service and entry point is watched',
    'kind' => [
        'http' => 'Web address',
        'keyword' => 'Web address and word',
        'port' => 'Port',
        'ping' => 'Ping',
        'dns' => 'Name lookup',
        'docker' => 'Container',
        'heartbeat' => 'Heartbeat',
    ],
    'state' => [
        'up' => 'up',
        'down' => 'down',
        'pending' => 'not settled yet',
        'paused' => 'paused',
        'expected-down' => 'down on purpose',
        'not-reporting' => 'not reporting',
    ],
    'path' => [
        'household' => 'Reached the way the household reaches it',
        'internal' => 'Reached from inside the stack',
    ],
    'covers' => [
        'service' => 'service',
        'entry-point' => 'entry point',
        'job' => 'job',
    ],
];
