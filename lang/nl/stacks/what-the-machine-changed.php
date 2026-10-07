<?php

declare(strict_types=1);

return [
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
        'put_back' => 'Wat dit terugzetten inhoudt',
        'put_back_that' => 'Wat het terugzetten van ":did", :when, inhoudt',
    ],
    'run_back' => [
        'names_no_run' => 'Dit noemt niets uit het verslag om terug te zetten',
        'not_on_the_record' => 'Het verslag bevat niets wat op dat moment is gedaan. Het kan ouder zijn dan het verslag teruggaat, of al teruggezet.',
        'goes_with_it' => '{1} Dit terugzetten zet de ene wijziging hieronder terug.|[2,*] Dit terugzetten zet alle :count wijzigingen terug die samen zijn gedaan, nooit een deel ervan.',
        'whole_or_nothing' => 'De stack zet alles terug of niets, en zegt wat hij niet kon terugzetten en waarom.',
        'cannot_go_back' => 'Een hiervan kan niet worden teruggezet, dus de stack zou geen ervan terugzetten.',
        'put_it_back' => 'Zet het terug',
        'putting_back' => 'Bezig het terug te zetten',
        'no_progress_while_running' => 'De stack zegt wat hij terugzette als hij klaar is, en niets over hoe ver hij is terwijl hij loopt.',
        'no_outcome' => 'De stack weet niet meer wat er van het terugzetten geworden is',
        'no_outcome_action' => 'Misschien is het teruggezet. Het verslag zegt wat er nu is.',
        'refused' => 'De stack antwoordde, en heeft dit niet teruggezet',
        'refused_same_answer' => 'Opnieuw vragen geeft hetzelfde antwoord. Het verslag zegt wat er nu is, en daar kan het opnieuw worden gekozen zodra wat de stack zei veranderd is.',
        'a_rehearsal' => 'Een generale repetitie: er is niets teruggezet',
        'did' => [
            'all' => 'Alles ging terug.',
            'not_all' => 'Niet alles ging terug. Wat er nog staat, en waarom:',
            'reversed' => 'Wat terugging:',
            'none_reversed' => 'Er ging niets terug',
        ],
        'would' => [
            'all' => 'Alles zou teruggaan.',
            'not_all' => 'Niet alles kan worden beloofd. Wat kan blijven staan, en waarom:',
            'reversed' => 'Wat zou teruggaan:',
            'none_reversed' => 'Er zou niets teruggaan',
        ],
        'noted' => 'Teruggaan betekent ook:',
        'does' => [
            'remove' => 'Wat het maakte wordt verwijderd',
            'restore' => 'De instelling krijgt terug wat ze had',
            'delete' => 'Wat het aanmaakte wordt gewist',
            'withdraw' => 'Wat lemonfiber in het bestand schreef wordt eruit gehaald',
            'rewind' => 'Het bestand dat lemonfiber overschreef krijgt terug wat erin stond',
            'repin' => 'Teruggezet op de versie waarop het stond',
            'reconfigure' => 'De eigen instelling van de dienst wordt teruggezet',
            'revoke' => 'De sleutel die het maakte wordt ingetrokken',
            'reinstate' => 'De sleutel die het introk wordt weer geldig',
        ],
    ],
];
