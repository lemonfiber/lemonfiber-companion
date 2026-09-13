<?php

declare(strict_types=1);

return [
    'category' => [
        'environment' => 'Omgeving',
        'storage' => 'Opslag',
        'network' => 'Netwerk',
        'vpn' => 'VPN',
        'credentials' => 'Inloggegevens',
        'services' => 'Services',
        'providers' => 'Aanbieders',
        'queue' => 'Wachtrij',
        'config' => 'Configuratie',
    ],
    'conclusion' => [
        'fail' => 'Mislukt',
        'unverified' => 'Kon niet worden gecontroleerd',
        'warn' => 'Heeft aandacht nodig',
        'pass' => 'Geslaagd',
        'skipped' => 'Overgeslagen',
    ],
    'severity' => [
        'critical' => 'Gegevens of iets buiten deze machine loopt gevaar',
        'error' => 'Kapot',
        'warning' => 'Verminderd',
        'advisory' => 'Goed om te weten',
    ],
    'undoing' => [
        'permanent' => 'Dit kan niet ongedaan worden gemaakt',
        'possible' => 'Dit kan daarna ongedaan worden gemaakt',
    ],
    'overall' => [
        'broken' => 'Er is iets kapot',
        'unknown' => 'De gezondheid kon niet worden vastgesteld',
        'degraded' => 'Sommige services hebben aandacht nodig',
        'healthy' => 'Alles draait',
    ],
    'because_of' => 'Vanwege: :title',
    'stale' => 'Laatst gecontroleerd :ago',
    'no_findings' => 'Er is niets dat aandacht nodig heeft.',
    'repair_refused' => 'De stack heeft die reparatie geweigerd: :reason',
    'nothing_to_try' => 'De stack heeft hier geen suggestie voor.',
    'ask_again' => 'Opnieuw controleren',
    'see_how_it_is' => 'Bekijk hoe deze stack het doet',
];
