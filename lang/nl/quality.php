<?php

declare(strict_types=1);

return [
    'road_in' => 'Hoe goed de media moet zijn',

    // Wat er van de keuze werd. Een repetitie en een tegengehouden keuze hebben
    // elk een eigen zin, en geen van beide zegt dat de keuze is vastgelegd.
    'became' => [
        'shown' => 'Wat nu gekozen is',
        'recorded' => 'Vastgelegd: dit is nu gekozen',
        'rehearsed' => 'Een repetitie: er is niets vastgelegd',
        'held' => 'Tegengehouden, niet vastgelegd: deze machine zou het in software moeten transcoderen',
        'reapplied' => 'De voorinstelling is teruggezet over de configuratie die je bewerkte',
        'would-reapply' => 'Een repetitie: de voorinstelling terugzetten zou de configuratie die je bewerkte overschrijven, en er is niets geschreven',
    ],
    'held_unexplained' => 'De stack hield het tegen zonder een voorinstelling te noemen die hier getranscodeerd zou worden.',
    'confirm' => 'Toch kiezen',

    'customised' => 'De kwaliteitsconfiguratie is met de hand bewerkt. Ze blijft zoals jij haar zette, en de voorinstelling gaat er niet over.',
    'not_put_back' => 'De voorinstelling terugzetten over je bewerkingen wordt hier niet aangeboden.',

    'for' => 'Voor :scope',
    'per_hour' => 'Ongeveer :size per uur',
    'transcodes_here' => 'Deze machine zou dit in software moeten transcoderen',
    'no_presets' => 'De stack meldt geen voorinstelling.',

    // Muziek heeft geen resolutie, en wordt gekozen naar formaat.
    'music' => [
        'heading' => 'Muziek, naar formaat in plaats van resolutie',
        'targets' => 'Mikt op :targets',
        'unset' => 'Er is geen formaat gekozen voor muziek.',
    ],

    'choose' => [
        'heading' => 'Een voorinstelling kiezen',
        'preset' => 'Voorinstelling, of een formaat voor muziek',
        'preset_help' => 'In de woorden van de stack, zoals hij ze hierboven noemt',
        'kind' => 'Soort media',
        'kind_help' => 'Laat leeg om voor alles te kiezen',
        'act' => 'Kiezen',
    ],

    'upgrade' => [
        'heading' => 'Wat er al is verbeteren',
        'apart' => 'Los aangeboden, en per soort beschreven voordat er iets wordt opgehaald.',
        'describe' => 'Wat zou verbeteren inhouden?',
        'described' => 'Wat verbeteren zou inhouden. Er is niets opgehaald.',
        'carried_out' => 'Verbeteren: elke dienst is gevraagd opnieuw te zoeken',
        'kind' => ':kind, op :preset',
        'nothing' => 'De stack noemt niets om te verbeteren.',
        'agree' => 'Verbeter wat er al is',
    ],

    // Wat er werd van het vragen aan een dienst, om opnieuw te zoeken of om
    // een formaat voor muziek over te nemen.
    'asked' => [
        'not-asked' => 'Er is niets aan de dienst gevraagd',
        'started' => 'De dienst stemde in en is ermee bezig',
        'not-started' => 'De dienst was nog niet klaar met opstarten, dus er is niets gevraagd',
        'failed' => 'De dienst weigerde, of was niet te bereiken',
    ],
];
