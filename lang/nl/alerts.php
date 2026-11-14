<?php

declare(strict_types=1);

return [
    'where_alerts_go' => 'Waar meldingen heen gaan',
    'where_alerts_go_for' => 'Waar de meldingen van :stack heen gaan',
    'none_readable' => 'Geen enkele back-end kon worden gelezen.',
    'all_read' => 'Elke back-end is gelezen.',
    'title' => 'Waar meldingen heen gaan',
    'state' => [
        'unconfigured' => 'Niet ingesteld',
        'ready' => 'Bezorgt',
        'degraded' => 'Sommige back-ends falen',
        'unreachable' => 'Niets kan worden bezorgd',
    ],
    'kind' => [
        'ntfy' => 'ntfy',
        'gotify' => 'Gotify',
        'webhook' => 'Webhook',
        'apprise' => 'Apprise',
        'unifiedpush' => 'UnifiedPush',
    ],
    'chosen' => [
        'you' => 'Door jou gekozen',
        'member' => 'Gekozen door :member',
    ],
    'hosted_at' => 'Gehost bij :host. Wat ervoor wordt verstuurd:',
    'proof' => [
        'unproven' => 'Nog nooit bezorgd',
        'proven' => 'Laatst bewezen :ago',
        'held' => 'Tegengehouden: :why',
    ],
    'ended' => [
        'none' => 'Nog niets bezorgd',
        'confirmed' => 'Bevestigd :ago',
        'sent' => 'Verstuurd, niet bevestigd, :ago',
        'failed' => 'Mislukt :ago: :reason',
    ],
    'failed_since' => '{1} Eén keer mislukt sinds de laatste bevestigde bezorging|[2,*] :count keer mislukt sinds de laatste bevestigde bezorging',
    'retry' => 'Volgende poging :in, poging :attempt van :of',
    'open' => 'Alles over :where',
    'unread' => 'Kon niet worden gelezen',
    'failures' => 'Mislukt sinds de laatste bevestigde bezorging',
    'no_failures' => 'Niets is mislukt sinds de laatste bevestigde bezorging.',
    'no_longer_listed' => 'Deze machine noemt deze back-end niet meer.',
    'set_up_at_the_machine' => 'Back-ends worden op de machine ingesteld, in de webconsole of in de terminal.',
    'changed_at_the_machine' => 'Back-ends en hun inloggegevens worden op de machine gewijzigd, nooit vanaf deze telefoon.',
    'hosted' => [
        'heading' => 'Verstuurd door diensten die je koos',
        'none' => 'Geen back-end waar je meldingen heen gaan wordt elders gehost.',
        'sends_nothing' => 'Er staat niets bij wat ervoor wordt verstuurd.',
        'not_read' => 'Waar meldingen heen gaan kon niet worden gelezen: :reason',
    ],
];
