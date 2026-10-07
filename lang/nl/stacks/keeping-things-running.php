<?php

declare(strict_types=1);

return [
    // Wat er tussen een langlopende opdracht en de machine staat, in de woorden
    // van de operator en niet die van de draad. Twee van de zes zijn de reden
    // dat deze groep is uitgeschreven: *installed-unverified* leest als draait
    // en is het niet, en *unsupported* leest als uit en is niet aan te zetten.
    'hosting' => [
        'not-hosted' => 'Draait alleen zolang er een terminal openstaat',
        'hosted' => 'Komt terug na een herstart',
        'installed-unverified' => 'Geïnstalleerd — de machine zei niet of het draait',
        'stopped' => 'Geïnstalleerd, en draait niet',
        'orphaned' => 'Geïnstalleerd voor een programma dat er niet meer is',
        'unsupported' => 'Niet beschikbaar op deze machine',
    ],

    // De ingang, vanaf het scherm van de machine zelf. Het noemt de vraag
    // en niet het mechanisme: een operator weet wat een herstart is en
    // hoeft niet te weten wat een launch agent is om dit te willen.
    'what_keeps_running' => 'Wat een herstart overleeft',

    // Hoeveel er geïnstalleerd zijn en niet draaien. Voor de rijen genoemd,
    // om dezelfde reden als bij het vastgelopen-scherm: wie dit de ochtend na
    // een herstart opent, zou ze niet moeten hoeven tellen.
    'did_not_come_back' => '{1} Eén ding kwam niet terug|[2,*] :count dingen kwamen niet terug',

    // Alleen een wees heeft er een, en die noemt het programma en niet de
    // service — het verschil tussen *dit draait niet* en *het bestand dat het
    // draait is er niet meer*.
    'missing_program' => 'Het programma dat het draait is weg: :program',

    // Een machine die niets draaiend houdt. Een eigen zin in plaats van een
    // lege lijst, en iets anders dan de machine die dit product niet kan
    // instellen — die draagt een instructie.
    'keeps_nothing_running' => 'Hier overleeft niets een herstart',
    'keeps_nothing_running_action' => 'Stel er een in op de machine, dan verschijnt die hier.',

    // Waarmee de machine dingen draaiend houdt terwijl niemand is ingelogd.
    // Bij naam genoemd in plaats van omschreven: een operator die `launchd`
    // leest kan ernaar zoeken, en een zin over *het servicebeheer van het
    // systeem* geeft ze niets om in te tikken.
    'keeps-running' => [
        'launchd' => 'launchd, in je eigen inlogsessie',
        'systemd' => 'systemd, in je eigen sessie',
        'unsupported' => 'Deze machine heeft geen servicebeheer dat lemonfiber instelt',
    ],

    // Eén commando aan deze machine geven om draaiend te houden, of het
    // terugnemen. Elke knop noemt zijn commando, zodat twee regels nooit
    // dezelfde woorden dragen.
    'handing_over' => [
        'install' => ':name draaiend houden op deze machine',
        'remove' => ':name niet langer draaiend houden',
    ],
    'handing_over_asked' => [
        'install' => ':name draaiend houden op deze machine?',
        'remove' => ':name niet langer draaiend houden?',
    ],
    'handing_over_means' => [
        'install' => 'Het servicebeheer van de machine wordt gevraagd het te draaien. De stack zegt daarna of het gestart is en waar het wegschrijft wat het zegt.',
        'remove' => 'Het draait dan alleen zolang een terminal het vasthoudt, en elk bestand dat de installatie maakte wordt teruggenomen.',
    ],

    // Wat ervan kwam, zoals de stack het zei. De kop noemt wat gevraagd werd en
    // zegt nooit dat het lukte: of het commando draait, zegt de stand.
    'handed_over' => [
        'heading' => [
            'install' => 'Gevraagd :name draaiend te houden',
            'remove' => 'Gevraagd :name niet langer draaiend te houden',
            'did_not' => ':name is niet overgedragen',
        ],
        'rehearsed' => 'Een repetitie: er is niets aan deze machine veranderd.',
        'stands' => 'Hoe het er nu voor staat: :standing',
        'started' => 'Het is gestart.',
        'not_started' => 'Het is niet gestart.',
        'writes_to' => 'Wat het zegt wordt weggeschreven naar :output',
        'writes_unsaid' => 'De stack zei niet waar wordt weggeschreven wat het zegt.',
        'touched' => [
            'install' => 'Geschreven: :file',
            'remove' => 'Teruggenomen: :file',
        ],
        'would_touch' => [
            'install' => 'Zou worden geschreven: :file',
            'remove' => 'Zou worden teruggenomen: :file',
        ],
        'touched_nothing' => [
            'install' => 'Er is geen bestand geschreven.',
            'remove' => 'Er was niets om terug te nemen.',
        ],
    ],
];
