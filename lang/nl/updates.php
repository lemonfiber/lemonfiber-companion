<?php

declare(strict_types=1);

return [
    'pins' => [
        'current' => 'Up-to-date',
        'updates-available' => 'Er staat een update klaar',
        'updated' => 'Bijgewerkt',
        'partial' => 'Een deel van de update is doorgegaan',
        'failed' => 'De update is niet doorgegaan',
    ],
    'ended' => [
        'updated' => 'Bijgewerkt',
        'not-fetched' => 'De download is nooit aangekomen',
        'not-started' => 'Startte niet op de nieuwe versie',
        'not-reached' => 'Gestart, maar geeft geen antwoord',
    ],
    'undo' => [
        'rollback' => 'Terug naar de vorige versie',
        'restore' => 'Zet de eerst gemaakte momentopname terug',
    ],
    'about_to_take' => 'Op het punt de diensten bij te werken',
    'would_change' => '{1} Eén dienst stopt en start opnieuw|[2,*] :count diensten stoppen en starten opnieuw',
    'changes_nothing' => 'Deze release verandert geen enkele dienst op deze machine.',

    // Voor het ja gezegd, en bij de diensten waar het over gaat. Het ongedaan
    // maken van de update zet de rest terug en deze niet.
    'cannot_be_put_back' => '{1} Eén hiervan kan niet worden teruggedraaid|[2,*] :count hiervan kunnen niet worden teruggedraaid',
    'cannot_be_put_back_after' => 'De update later ongedaan maken draait dit niet terug.',
    'take_it' => 'Werk de diensten bij',
    'last_update' => 'De laatste update',
    'did_not_arrive' => '{1} Eén dienst staat niet waar je hem wilde|[2,*] :count diensten staan niet waar je ze wilde',
    'unanswered' => 'Sommige diensten zijn gestart en geven geen antwoord, dus de stack kan niet zeggen wat ze doen.',
    'undo_carries_data' => 'Dit ongedaan maken zet ook de gegevens terug',
    'nothing_applied' => 'Op deze machine is nog geen update genomen.',
    'running_on' => 'Draait :version',
    'running_withdrawn' => 'Deze versie is teruggetrokken',
    'what_it_changed' => 'Wat :version veranderde',
    'history' => 'Releasegeschiedenis',
    'withdrawn' => 'Teruggetrokken',
    'no_history' => 'De stack noemde geen releases.',
    'delivers_unsaid' => 'De stack heeft niet gezegd wat deze verandert',

    'would_be_noticed' => 'Het huishouden ziet het verschil',
    'would_not_be_noticed' => 'Hier merkt niemand iets van',
];
