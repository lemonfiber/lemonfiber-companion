<?php

declare(strict_types=1);

return [
    'setting_count' => '{0} Deze stack heeft niets ingesteld|{1} Deze stack heeft één instelling|[2,*] Deze stack heeft :count instellingen',

    'listing_is_the_stacks' => 'Dit is de lijst die de stack stuurde, in de volgorde waarin hij hem stuurde.',

    'what_setup_settled' => 'Wat de installatie besliste toen deze stack voor het eerst werd ingesteld, staat tussen deze instellingen, zoals de stack het nu heeft. Hier iets aanpassen is een instelling wijzigen, niet de stack opnieuw installeren.',

    'first_run_setup' => 'De eerste installatie gebeurt niet vanuit deze app',
    'first_run_setup_why' => 'Een stack instellen is wat hem vanaf een telefoon bereikbaar maakt, dus dat gebeurt op de machine zelf.',

    'nothing_is_set' => 'Hier is nog niets ingesteld',
    'nothing_is_set_action' => 'Instellingen verschijnen hier zodra deze stack ze heeft.',

    'came_from_bundled' => 'Standaardinstelling van de stack',
    'came_from_operator' => 'Hier ingesteld',
    'came_from_plugin' => 'Ingesteld door de plug-in :named',
    'came_from_unknown' => 'Niemand kon zeggen waar dit vandaan komt — :why',
    'came_from_overridden' => 'Ingesteld door de plug-in :named, over wat er eerder stond',
    'came_from_orphaned' => 'Ingesteld door de plug-in :named, die niet meer is geïnstalleerd',
    // Wat de wijziging van een plug-in verving, onder de regel die de plug-in noemt.
    'before' => [
        'held' => 'Eerder stond hier :value',
        'nothing_set' => 'Eerder was niets ingesteld en gold de standaard van de stack',
        'withheld' => 'Eerder stond hier een geheim, dat niet wordt getoond',
    ],


    'cost' => [
        'cheap' => 'Een herstart van de betrokken diensten, en verder niets',
        'consequential' => 'Deze is het waard om twee keer te lezen',
    ],

    'stance' => [
        'unchanged' => 'Dit staat er al in',
        'pending' => 'Klaargezet — er is nog niets geschreven',
        'blocked' => 'Er is niets geschreven',
        'applied' => 'Geschreven',
    ],

    'what_it_would_hold' => 'Wat erin zou komen te staan',
    'holds_now' => 'Er staat nu :value in',
    'holds_nothing_yet' => 'Er staat nog niets in',
    'what_would_happen' => 'Bekijk wat dit zou doen',
    'agree' => 'Deze wijziging doorvoeren',
    'never_mind' => 'Laat het zoals het is',
    'change_this' => 'Dit wijzigen',

    'agree_to' => 'Deze wijziging aan :key doorvoeren',
    'what_would_happen_to' => 'Bekijk wat het wijzigen van :key zou doen',
    'change_key' => ':key wijzigen',
    'never_mind_about' => 'Laat :key zoals het is',

    'would_hold' => 'Er zou :value in komen te staan',
    'services_will_restart' => 'De betrokken diensten starten opnieuw op.',
    'worth_reading_twice' => 'Deze kan gegevens verplaatsen of een dienst wegnemen.',

    // Waar het terugzetten van elk bewerkt bestand wordt aangeboden.
    'put_it_all_back' => 'Zet de configuratie terug',

    'findings' => [
        'in_its_words' => ':words',
        'heading' => 'Wat dit op de machine betekent',
        'stops' => 'Het stopt :service.',
        'keeps' => 'Het laat :service zoals het is.',
        'interrupts' => [
            'usenet' => 'Het onderbreekt :name, voor :progress% binnen via Usenet.',
            'torrent' => 'Het onderbreekt :name, voor :progress% binnen als torrent.',
        ],
        'nothing' => 'De stack vond niets anders op de machine dat dit zou verstoren.',
        'library_carried' => ':service houdt zijn bibliotheek op :path.',
        'library_not_carried' => ':service houdt zijn bibliotheek op :path niet.',
        'asks_for' => 'Het vraagt je om :what.',
        'kept_in' => 'Je antwoord wordt bewaard in :setting.',
        'to_obtain' => 'Dit is een account dat je zelf regelt; geen instelling bewaart het.',
        'edited' => 'Deze instelling is buiten lemonfiber gewijzigd sinds lemonfiber hem het laatst schreef.',
        'wrote' => 'lemonfiber schreef het laatst :value.',
        'wrote_empty' => 'lemonfiber liet hem het laatst leeg.',
        'wrote_withheld' => 'lemonfiber schreef het laatst een geheim, dat niet wordt getoond.',
        'found' => 'In het bestand staat nu :value.',
        'found_empty' => 'In het bestand staat daar nu niets.',
        'found_withheld' => 'In het bestand staat nu een geheim, dat niet wordt getoond.',
    ],

    'ask_again' => 'Opnieuw vragen',
];
