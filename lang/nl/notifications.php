<?php

declare(strict_types=1);

return [
    'plain' => [
        'onset_title' => ':stack heeft aandacht nodig',
        'onset_body' => 'Open lemonfiber om te zien wat er gemeld is. Kenmerk :code.',
        'resolved_title' => ':stack is weer in orde',
        'resolved_body' => 'Wat er gemeld was is voorbij. Kenmerk :code.',
        'more_title' => ':stack heeft je meer te vertellen',
        'more_body' => 'Open lemonfiber om alles te zien wat hij meldde.',
    ],
    'guarded' => [
        'onset_title' => 'Een stack heeft aandacht nodig',
        'onset_body' => 'Ontgrendel om te zien welke, en wat er gemeld is.',
        'resolved_title' => 'Een stack is weer in orde',
        'resolved_body' => 'Ontgrendel om te zien welke.',
        'more_title' => 'Een stack heeft je meer te vertellen',
        'more_body' => 'Ontgrendel om te zien welke.',
    ],
    'page' => [
        'title' => 'Meldingen',
        'switch_for' => ':stack: :kind',
        'floor_for' => ':stack: problemen vanaf :floor',
        'raises_nothing' => 'Deze stack stuurt zoals hij is ingesteld geen meldingen.',
        'not_read' => 'Welke meldingen deze stack stuurt kon niet worden gelezen: :reason',
        'kept_off' => 'Nog steeds uit voor deze stack: :kinds.',
        'kept_floor' => 'Problemen worden gemeld vanaf :floor.',
        'problems_from' => 'Vertel me over problemen vanaf',
        'problems_are_off' => 'Problemen staan uit voor deze stack.',
        'turn_on_above' => 'Zet meldingen hierboven aan om te kiezen.',
        'no_stacks' => 'Er staan nog geen stacks op deze telefoon.',
    ],
    'kind' => [
        'problem' => 'Problemen',
        'completion' => 'Werk waar je om vroeg, klaar',
        'update' => 'Updates beschikbaar',
        'backup' => 'Geslaagde back-ups',
        'request' => 'Verzoeken die op je wachten',
        'member' => 'Uitnodigingen aangenomen, afgeslagen of verlopen',
        'ready' => 'Iets waar een lid om vroeg is binnen',
    ],
    'floor' => [
        'advisory' => 'Advies',
        'warning' => 'Waarschuwing',
        'error' => 'Fout',
        'critical' => 'Kritiek',
    ],
];
