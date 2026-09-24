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

    // Hoe ver een wijziging van de stack terug te draaien is.
    'reversal' => [
        'whole' => 'Kan helemaal worden teruggedraaid',
        'partial' => 'Kan maar gedeeltelijk worden teruggedraaid',
        'none' => 'Kan niet worden teruggedraaid',
    ],

    // Wat de machine aan zichzelf heeft veranderd.
    'record' => [
        'road_in' => 'Wat hier is veranderd',
        'horizon' => 'Wat bewaard wordt: :horizon.',
        'by' => ':operation, aan :target',
        'alongside' => '{1} Op zichzelf gedaan|[2,*] Een van :count wijzigingen die samen zijn gedaan',
        'stops_short' => 'Stopt eerder omdat: :because',
        'instead' => 'In plaats daarvan: :instead',
        'nothing_changed' => 'Er is niets veranderd',
        'clock_unreadable' => 'Op een moment dat de machine niet kon vertellen',
    ],
    'origins' => [
        'road_in' => 'Waar dit vandaan komt',
        'as_declared' => 'Zoals deze machine het opgeeft. Niets hiervan wordt bij de projecten zelf opgezocht.',
        'runs' => 'Draait :image, vastgezet op :pinned',
        'licence' => 'Licentie: :licence',
        'upstream' => 'Gebouwd vanuit :upstream',
        'nothing_declared' => 'Deze machine geeft geen diensten op',
    ],
    'outbound' => [
        'road_in' => 'Wat deze machine verlaat',
        'asks_for' => [
            'registry' => 'Images van diensten ophalen',
            'guides' => 'De kwaliteitsgidsen controleren',
            'echo' => 'Het publieke adres van deze machine opzoeken',
            'indexer' => 'Een indexersleutel controleren',
            'usenet' => 'Een Usenet-login controleren',
            'household' => 'Een huisgenoot iets laten weten',
            'updates' => 'Kijken of er een nieuwere lemonfiber is',
        ],
        'allowed' => [
            'allowed' => 'Toegestaan door de instellingen van deze machine',
            'switched_off' => 'Uitgeschakeld',
        ],
        'ours' => [
            'heading' => 'Wat lemonfiber zelf verstuurt',
            'sends' => 'Verstuurt: :sends',
            'goes_to' => 'Naar :destination',
            'nowhere' => 'Er is niets ingesteld om te bereiken',
            'switch' => 'Uit te zetten met :switch',
            'cost' => 'Als het uit staat: :cost',
            'none' => 'lemonfiber verstuurt zelf niets',
        ],
        'theirs' => [
            'heading' => 'Wat de diensten versturen',
            'reaches' => 'Bereikt :destination',
            'reaches_nothing' => 'Bereikt niets',
            'unrecorded' => 'lemonfiber weet niet wat deze dienst bereikt',
            'none' => 'Geen enkele dienst hier verstuurt iets',
            'origin' => [
                'bundled' => 'Een eigen dienst van de stack',
                'operator' => 'Een hier toegevoegde dienst',
                'plugin' => 'Meegebracht door de plug-in :named',
                'unknown' => 'Niemand kon zeggen waar deze dienst vandaan komt — :why',
                'legend' => 'Een dienst waarbij staat waar hij vandaan komt, is geen eigen dienst van de stack; alle andere wel.',
            ],
        ],
    ],
    'alerts' => [
        'road_in' => 'Waarover je bericht krijgt',
        'preset' => 'Voorinstelling: :preset',
        'set_apart' => 'Apart gezet van de voorinstelling',
        'heard' => [
            'heard' => 'Je hoort hierover, wat de voorinstelling ook zegt',
            'silenced' => 'Stil gehouden, wat de voorinstelling ook zegt',
        ],
        'nothing_set_apart' => 'Niets is apart gezet; elke gebeurtenis volgt de voorinstelling',
        'changed_at_the_machine' => 'Aan te passen op de machine, niet hier',
    ],
    'line' => [
        'road_in' => 'Hoe de lijn gedeeld wordt',
        'restraint' => [
            'unlimited' => 'Niets houdt de stack tegen',
            'limited' => 'De stack is aan een limiet gebonden',
            'scheduled-active' => 'Het huis is wakker, dus de stack wordt afgeremd',
            'scheduled-quiet' => 'Het huis slaapt, dus de lijn is van de stack',
            'overridden' => 'De limieten zijn tijdelijk opgeheven',
            'cap-warning' => 'Dicht bij het maandplafond',
            'cap-exceeded' => 'Het maandplafond is bereikt',
        ],
        'down' => 'Download: :says',
        'up' => 'Upload: :says',
        'upload_cost' => 'De upload afremmen kost: :costs',
        'capacity' => 'Wat de lijn aankan',
        'carries' => ':down :down_unit omlaag, :up :up_unit omhoog',
        'rate' => [
            'kilobits' => 'kbit/s',
            'megabits' => 'Mbit/s',
            'gigabits' => 'Gbit/s',
        ],
        'measured' => [
            'declared' => 'Zoals opgegeven, niet gemeten',
            'observed' => 'Zoals de stack het heeft zien gaan',
        ],
        'tunnel' => [
            'through' => 'Gemeten door de privétunnel waar het verkeer van de stack doorheen gaat',
            'beside' => 'Gemeten naast de privétunnel waar het verkeer van de stack doorheen gaat',
        ],
        'unmeasured' => 'Niets heeft de lijn gemeten',
        'monthly_cap' => 'Maandplafond',
        'capped_at' => ':figure :unit per maand',
        'cap' => [
            'pause' => 'Bij het bereiken stopt het ophalen tot de nieuwe maand',
            'throttle' => 'Bij het bereiken gaat het ophalen langzaam door, zodat half werk af kan',
            'continue' => 'Bij het bereiken verandert er niets; het ophalen gaat door',
        ],
        'month' => [
            'within' => 'Deze maand zit er ruim onder',
            'warning' => 'Deze maand zit er dichtbij',
            'exceeded' => 'Deze maand heeft het bereikt',
        ],
        'uncapped' => 'Er is geen plafond opgegeven',
        'untouched' => 'Buiten elke limiet',
        'nothing_untouched' => 'Niets valt buiten de limieten',
        'no_cautions' => 'De stack heeft niets toe te voegen over deze meting',
        'changed_at_the_machine' => 'Limieten en plafonds worden op de machine aangepast, niet hier',
    ],
];
