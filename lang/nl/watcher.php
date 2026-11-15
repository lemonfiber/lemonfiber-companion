<?php

declare(strict_types=1);

return [
    'title' => 'De wachter buiten',
    'open' => 'De wachter buiten',
    'blind' => 'De wachter antwoordt niet, dus niets hieronder wordt bewaakt',
    'watched_by' => 'Bewaakt door :watcher, :ago gevraagd',
    'monitors' => 'Monitors',
    'no_monitors' => 'De wachter heeft geen monitors',
    'said_by_the_watcher' => 'De wachter zegt: :state (sinds :ago)',
    'said_by_the_stack' => 'De stack zegt: :state',
    'they_disagree' => 'Ze zijn het oneens',
    'covers_this' => 'Bewaakt :kind :name',
    'heartbeat_missing' => 'De hartslag is :ago weggevallen',
    'heartbeat_never' => 'De hartslag is nooit aangekomen',
    'not_set_up' => 'Kon niet worden ingesteld om :wanted te bewaken, dus bewaakt het :watches',
    'switched_off' => 'Uitgeschakeld',
    'uncovered' => 'Niets bewaakt deze',
    'nothing_uncovered' => 'Elke belangrijke dienst en ingang wordt bewaakt',
    'kind' => [
        'http' => 'Webadres',
        'keyword' => 'Webadres en woord',
        'port' => 'Poort',
        'ping' => 'Ping',
        'dns' => 'Naam opzoeken',
        'docker' => 'Container',
        'heartbeat' => 'Hartslag',
    ],
    'state' => [
        'up' => 'bereikbaar',
        'down' => 'onbereikbaar',
        'pending' => 'nog niet zeker',
        'paused' => 'gepauzeerd',
        'expected-down' => 'met opzet uit',
        'not-reporting' => 'meldt niets',
    ],
    'path' => [
        'household' => 'Bereikt zoals het huishouden het bereikt',
        'internal' => 'Bereikt van binnen de stack',
    ],
    'covers' => [
        'service' => 'dienst',
        'entry-point' => 'ingang',
        'job' => 'taak',
    ],
];
