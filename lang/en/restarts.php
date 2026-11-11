<?php

declare(strict_types=1);

return [
    'title' => 'While nobody was looking',
    'after_the_last_restart' => 'After the last restart',
    'started' => 'The machine last started :ago, and the check after it ran.',
    'not_run' => 'The machine last started :ago, and the check after a restart has not run since.',
    'start_unknown' => 'The machine does not say when it last started, so no check can be placed after it.',
    'declined' => 'The stack says why: :reason',
    'nothing_kept' => 'The stack keeps no record of a restart yet.',
    'checked' => 'Checked :ago.',
    'earlier' => 'The newest check kept ran :ago, after an earlier start.',
    'not_back' => 'Did not come back: :services.',
    'failing' => '{1} It has not come back after the last restart, failing since :since.|[2,*] It has not come back after :count restarts in a row, failing since :since.',
    'failing_undated' => '{1} It has not come back after the last restart.|[2,*] It has not come back after :count restarts in a row.',
    'ended' => [
        'passed' => 'Everything came back.',
        'failed' => 'Not everything came back.',
        'waiting-for-install' => 'Everything else came back, and a part waits for its install from this build.',
    ],
    'tunnel' => [
        'held' => 'The tunnel came back too.',
        'not-established' => 'The tunnel did not come back: :said',
        'not-checked' => 'Nothing checked the tunnel.',
    ],
    'port' => [
        'same' => 'On the same forwarded port, :now.',
        'moved' => 'The forwarded port moved from :was to :now.',
        'now' => 'The forwarded port is :now.',
        'unknown' => 'The stack could not say which port is forwarded.',
        'not_every_client' => 'Not every client takes it yet.',
    ],
    'keeps_running' => 'What keeps it running',
    'keeps_running_open' => 'What keeps running here',
    'reading_keeps_running' => 'Reading what keeps it running…',
    'keeps_running_unread' => 'What keeps it running could not be read.',
];
