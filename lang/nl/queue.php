<?php

declare(strict_types=1);

return [
    'title' => 'Hoe de wachtrij zichzelf herstelt',
    'open' => 'Hoe de wachtrij zichzelf herstelt',
    'rehearsal' => 'Oefening: er is niets opgeruimd',
    'strikes_needed' => '{0} Het grijpt in zodra een ronde een item markeert|{1} Het grijpt in zodra een ronde een item heeft gemarkeerd|[2,*] Het grijpt in zodra :count rondes na elkaar een item om dezelfde reden hebben gemarkeerd',
    'classified' => 'De laatste ronde heeft de wachtrij :ago ingedeeld',
    'never_classified' => 'Nog geen ronde heeft de wachtrij ingedeeld',
    'incomplete' => 'Er kon iets niet worden gelezen, dus deze lijst kan onvolledig zijn',
    'mismatch' => 'Een downloadprogramma zet de downloads van een curator buiten de map van die curator, dus daar wordt niet op ingegrepen',
    'wedged' => 'Waar het op zou ingrijpen',
    'nothing_wedged' => 'Niets in de wachtrij is ongezond',
    'held_by' => 'Bij :service',
    'release' => 'Release :release',
    'strikes' => 'Markeringen: :strikes van :needed',
    'added' => ':ago toegevoegd',
    'in_grace' => 'Pas toegevoegd, dus het blijft met rust; dat eindigt :in',
    'next' => 'De volgende ronde zou, na elkaar:',
    'next_none' => 'De volgende ronde zou er niet op ingrijpen',
    'unreachable' => 'Wat het niet kon bereiken',
    'nothing_unreachable' => 'Het heeft elke curator en elk downloadprogramma bereikt',
    'runs' => 'Elke ronde, nieuwste eerst',
    'no_runs' => 'Er is nog geen ronde bewaard',
    'ran' => ':ago uitgevoerd',
    'run_acted_on_nothing' => 'Het heeft nergens op ingegrepen',
    'act_came_to' => ':act: :outcome',
    'no_acts' => 'Er is niets mee gedaan',
    'instead' => 'In plaats daarvan: :act',
    'healing' => [
        'on' => 'Herstel staat aan: het grijpt in op wat te vaak gemarkeerd is',
        'off' => 'Herstel staat uit: het deelt alleen in en grijpt nergens op in',
    ],
    'grace' => [
        'minutes' => '{0} Een nieuwe download blijft minder dan een minuut met rust|{1} Een nieuwe download blijft een minuut met rust|[2,*] Een nieuwe download blijft :count minuten met rust',
        'hours' => '{1} Een nieuwe download blijft een uur met rust|[2,*] Een nieuwe download blijft :count uur met rust',
        'days' => '{1} Een nieuwe download blijft een dag met rust|[2,*] Een nieuwe download blijft :count dagen met rust',
    ],
    'class' => [
        'stalled' => 'Vastgelopen: er beweegt niets en geen bron kan het op gang brengen',
        'slow' => 'Traag: het zou langer duren dan is toegestaan',
        'failed' => 'Mislukt: het downloadprogramma of de curator heeft het opgegeven',
        'stuck-on-metadata' => 'Vast op metadata: de torrent weet nog niet wat hij is',
        'orphaned' => 'Verweesd: in een downloadprogramma onder de categorie van een curator, en in de wachtrij van geen enkele curator',
    ],
    'act' => [
        'remove' => 'Uit het downloadprogramma halen',
        'blocklist' => 'De release blokkeren, zodat die niet opnieuw wordt gepakt',
        'search' => 'Opnieuw naar de titel zoeken',
    ],
    'outcome' => [
        'done' => 'gedaan',
        'would' => 'zou zijn gedaan',
        'refused' => 'niet gedaan',
        'fell-back' => 'er is iets lichters gedaan',
    ],
];
