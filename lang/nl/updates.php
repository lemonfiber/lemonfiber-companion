<?php

declare(strict_types=1);

return [
    'how' => [
        'current' => 'Bijgewerkt',
        'pending' => 'Er staat een update klaar',
        'stale' => 'Al een tijd niet gecontroleerd',
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
    'about_to_take' => 'Op het punt :version te nemen',
    'would_change' => '{1} Eén dienst stopt en start opnieuw|[2,*] :count diensten stoppen en starten opnieuw',
    'changes_nothing' => 'Deze release verandert geen enkele dienst op deze machine.',
    'something_worth_noticing' => 'Een hiervan is een verandering die het huishouden ziet',
    'take_this_one' => 'Neem deze',
    'take_that_one' => 'Neem :version',
    'last_update' => 'De laatste update',
    'did_not_arrive' => '{1} Eén dienst staat niet waar je hem wilde|[2,*] :count diensten staan niet waar je ze wilde',
    'unanswered' => 'Sommige diensten zijn gestart en geven geen antwoord, dus de stack kan niet zeggen wat ze doen.',
    'undo_carries_data' => 'Dit ongedaan maken zet ook de gegevens terug',
    'nothing_applied' => 'Op deze machine is nog geen update genomen.',
    'running_on' => 'Draait :version',
    'running_withdrawn' => 'Deze versie is teruggetrokken',
    'would_be_noticed' => 'Het huishouden ziet het verschil',
    'would_not_be_noticed' => 'Hier merkt niemand iets van',
    'nothing_waiting' => 'Er staat niets klaar.',
];
