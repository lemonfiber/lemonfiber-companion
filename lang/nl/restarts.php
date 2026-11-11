<?php

declare(strict_types=1);

return [
    'title' => 'Terwijl niemand keek',
    'after_the_last_restart' => 'Na de laatste herstart',
    'started' => 'De machine is :ago voor het laatst gestart, en de controle daarna is uitgevoerd.',
    'not_run' => 'De machine is :ago voor het laatst gestart, en de controle na een herstart is sindsdien niet uitgevoerd.',
    'start_unknown' => 'De machine zegt niet wanneer hij voor het laatst is gestart, dus geen controle kan daarna worden geplaatst.',
    'declined' => 'De stack zegt waarom: :reason',
    'nothing_kept' => 'De stack houdt nog niets bij over een herstart.',
    'checked' => 'Gecontroleerd: :ago.',
    'earlier' => 'De nieuwste bewaarde controle liep :ago, na een eerdere start.',
    'not_back' => 'Niet teruggekomen: :services.',
    'failing' => '{1} Hij is na de laatste herstart niet teruggekomen, al sinds :since.|[2,*] Hij is na :count herstarts op rij niet teruggekomen, al sinds :since.',
    'failing_undated' => '{1} Hij is na de laatste herstart niet teruggekomen.|[2,*] Hij is na :count herstarts op rij niet teruggekomen.',
    'ended' => [
        'passed' => 'Alles is teruggekomen.',
        'failed' => 'Niet alles is teruggekomen.',
        'waiting-for-install' => 'De rest is teruggekomen, en een onderdeel wacht op zijn installatie uit deze build.',
    ],
    'tunnel' => [
        'held' => 'De tunnel is ook teruggekomen.',
        'not-established' => 'De tunnel is niet teruggekomen: :said',
        'not-checked' => 'Niets heeft de tunnel gecontroleerd.',
    ],
    'port' => [
        'same' => 'Op dezelfde doorgestuurde poort, :now.',
        'moved' => 'De doorgestuurde poort is verplaatst van :was naar :now.',
        'now' => 'De doorgestuurde poort is :now.',
        'unknown' => 'De stack kon niet zeggen welke poort wordt doorgestuurd.',
        'not_every_client' => 'Nog niet elke client gebruikt hem.',
    ],
    'keeps_running' => 'Wat hem draaiende houdt',
    'keeps_running_open' => 'Wat hier blijft draaien',
    'reading_keeps_running' => 'Lezen wat hem draaiende houdt…',
    'keeps_running_unread' => 'Wat hem draaiende houdt kon niet worden gelezen.',
];
