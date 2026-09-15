<?php

declare(strict_types=1);

return [
    'category' => [
        'environment' => 'Omgeving',
        'storage' => 'Opslag',
        'network' => 'Netwerk',
        'vpn' => 'VPN',
        'credentials' => 'Inloggegevens',
        'services' => 'Services',
        'providers' => 'Aanbieders',
        'queue' => 'Wachtrij',
        'config' => 'Configuratie',
    ],
    'conclusion' => [
        'fail' => 'Mislukt',
        'unverified' => 'Kon niet worden gecontroleerd',
        'warn' => 'Heeft aandacht nodig',
        'pass' => 'Geslaagd',
        'skipped' => 'Overgeslagen',
    ],
    'severity' => [
        'critical' => 'Gegevens of iets buiten deze machine loopt gevaar',
        'error' => 'Kapot',
        'warning' => 'Verminderd',
        'advisory' => 'Goed om te weten',
    ],
    'undoing' => [
        'permanent' => 'Dit kan niet ongedaan worden gemaakt',
        'possible' => 'Dit kan daarna ongedaan worden gemaakt',
    ],
    'overall' => [
        'broken' => 'Er is iets kapot',
        'unknown' => 'De gezondheid kon niet worden vastgesteld',
        'degraded' => 'Sommige services hebben aandacht nodig',
        'healthy' => 'Alles draait',
    ],
    'because_of' => 'Vanwege: :title',
    'ago' => [
        'minutes' => '{0} zojuist|{1} een minuut geleden|[2,*] :count minuten geleden',
        'hours' => '{1} een uur geleden|[2,*] :count uur geleden',
        'days' => '{1} een dag geleden|[2,*] :count dagen geleden',
    ],
    'stale' => 'Laatst gecontroleerd :ago',
    'family_and_count' => ':family (:count)',
    'no_findings' => 'Er is niets dat aandacht nodig heeft.',
    'repair_refused' => 'De stack heeft die reparatie geweigerd: :reason',
    'nothing_to_try' => 'De stack heeft hier geen suggestie voor.',
    'ask_again' => 'Opnieuw controleren',
    'see_how_it_is' => 'Bekijk hoe deze stack het doet',
    'working_it_out' => 'Deze stack kijkt wat er te herstellen valt.',
    'working_it_out_action' => 'Dit duurt even. Vraag het zo nog eens.',
    'nothing_came_back' => 'Deze vraag is verlopen.',
    'nothing_came_back_action' => 'Er is niets uitgevoerd. Vraag het opnieuw.',
    'nothing_to_put_right' => 'Deze stack heeft niets te herstellen.',
    'affects_nothing_else' => 'Raakt verder niets.',
    'would_put_right' => 'Kijk wat er te herstellen valt',
    'back_to_the_stack' => 'Terug naar deze stack',
    'carrying_it_out' => 'Deze stack is ermee bezig.',
    'carrying_it_out_action' => 'Dit duurt even. Vraag het zo nog eens.',
    'nobody_knows_what_happened' => 'Onbekend wat hiervan geworden is.',
    'nobody_knows_what_happened_action' => 'Er is mogelijk wel iets gebeurd. Bekijk hoe deze stack het nu doet in plaats van het opnieuw te vragen.',
    'changed_count' => '{0} Er is niets gewijzigd|{1} Eén ding is hersteld|[2,*] :count dingen zijn hersteld',
    'worth_another_go' => 'Opnieuw proberen kan een ander resultaat geven.',
    'agree_to_it' => 'Voer dit uit',
    'mended' => [
        'fixed' => 'Hersteld',
        'fix_failed' => 'Herstellen is mislukt',
        'stopped' => 'Halverwege gestopt',
        'declined' => 'Door de stack geweigerd',
        'would_overwrite' => 'Niet gedaan: het zou iets overschrijven',
    ],
    'nothing_was_carried_out' => 'Er bleek niets te doen.',
    'look_again' => 'Kijk opnieuw wat er te herstellen valt',
    // N2-R9 — wat niet meer binnenkomt. De fase is waar het bleef steken, en
    // dat is het hele verschil tussen een indexer die niets vindt en een
    // bestand dat de bibliotheek nooit heeft opgepakt.
    'stage' => [
        'not-monitored' => 'Hier wordt niet op gelet',
        'monitored' => 'Wordt in de gaten gehouden, nog niet gezocht',
        'searching' => 'Wordt gezocht',
        'found' => 'Er is een kopie en die is nog niet gepakt',
        'grabbed' => 'Gepakt, nog niet begonnen met binnenhalen',
        'downloading' => 'Komt nu binnen',
        'downloaded' => 'Binnen, nog niet aan de bibliotheek gegeven',
        'importing' => 'Wordt overgedragen',
        'imported' => 'Overgedragen, nog niet af te spelen',
        'available' => 'Staat er en is af te spelen',
    ],
    'shown' => [
        'all-of-it' => 'Dit is alles waar de stack op is vastgelopen.',
        'some-of-it' => 'De stack heeft er meer; hij stuurde een deel van de lijst.',
    ],
    'stuck_count' => '{0} Er komt niets vast te zitten|{1} Er is één ding blijven steken|[2,*] Er zijn :count dingen blijven steken',
    'stuck_in' => 'In :service',
    'stuck_for_good' => 'Hier gebeurt vanzelf niets meer mee.',
    'undeclared_count' => '{0} Er draait hier verder niets|{1} Er draait hier nog één ding|[2,*] Er draaien hier nog :count dingen',
    'undeclared_explained' => 'Deze draaien op de machine en de configuratie van deze stack noemt ze niet. lemonfiber heeft ze niet gestart en stopt ze niet.',
    'nothing_undeclared' => 'Er draait hier verder niets.',
    'nothing_undeclared_action' => 'Alles op deze machine is iets dat deze stack zelf heeft opgegeven.',
    'what_else_is_running' => 'Wat draait hier nog meer',

    'nothing_stopped' => 'Er is niets blijven steken.',
    'nothing_stopped_action' => 'Alles waar het huishouden om vroeg is onderweg of al binnen.',
    'what_stopped' => 'Wat niet meer binnenkomt',

    // N2-R10 — een begrensde, doorzoekbare weergave die de dienst noemt en
    // zegt dat dit een venster is en niet het geheel.
    'stream' => [
        'stdout' => 'Uitvoer',
        'stderr' => 'Opgemerkt',
    ],
    'what_a_service_said' => 'Bekijk wat deze dienst zei',
    'logs_for' => 'Wat :service heeft gezegd',
    'window_of' => 'De laatste :count regels die deze stack bewaarde. Er kan meer achter zitten.',
    'the_whole_of_it' => 'Alle :count regels die deze stack voor deze dienst heeft.',
    'search_label' => 'Zoek in deze regels',
    'search_placeholder' => 'Een woord uit de regel die je zoekt',
    'search_is_over_the_window' => 'Doorzoekt wat hierboven staat, niet de hele scrollback.',
    'matched_count' => '{0} Hier past niets bij|{1} Eén regel past|[2,*] :count regels passen',
    'nothing_matched' => 'Geen regel in dit venster bevat dat.',
    'nothing_matched_action' => 'Het kan verder terug liggen dan dit venster reikt. Wis de zoekopdracht om het hele venster weer te zien.',
    'service_said_nothing' => 'Deze dienst heeft niets gezegd.',
    'service_said_nothing_action' => 'Hij draait rustig door, of is net gestart.',
    'no_moment' => 'Geen tijd opgegeven',
    'every' => [
        'while_work_runs' => 'Kijkt elke :count seconden opnieuw zolang dit loopt.',
    ],

    // N2-R7 — wat er draait, en hoeveel elk ervan uitmaakt. De toestand is waar
    // een dienst nu staat; hoeveel het uitmaakt is wat het zou kosten als dat
    // misging, en dat hoort bij hoe de machine is opgezet.
    'service' => [
        'failed' => 'Omgevallen',
        'crash-looping' => 'Valt om en start steeds opnieuw',
        'unhealthy' => 'Draait, en antwoordt slecht',
        'absent' => 'Verwacht, en er niet',
        'stopped' => 'Uitgezet',
        'starting' => 'Start op',
        'running' => 'Draait',
        'healthy' => 'Draait goed',
        'host-managed' => 'Wordt door de machine gedraaid, niet door deze stack',
    ],
    'matters' => [
        'critical' => 'Zonder dit werkt niets anders',
        'core' => 'Hoort bij de stack zelf',
        'important' => 'Het huishouden merkt het vandaag',
        'enhancing' => 'Het huishouden merkt het op den duur',
        'optional' => 'Niemand merkt het',
    ],
    'running' => [
        'inactive' => 'Er draait niets',
        'degraded' => 'Draait, met iets mis',
        'partial' => 'Een deel draait',
        'active' => 'Alles draait',
    ],
    'do' => [
        'start' => 'Starten',
        'stop' => 'Stoppen',
        'restart' => 'Opnieuw starten',
    ],

    'what_it_runs' => 'Wat er draait',

    // N2-R7's screen. The verbs above are the buttons; these are the sentences
    // around them — what a row says about itself, and what a stop is stated to
    // disturb before anybody agrees to it.
    'in_form' => 'Onderdeel van :form',
    'it_exited' => 'Gestopt met :code',
    'host_runs_it' => 'Deze machine draait hem, niet de stack',
    'read_its_logs' => 'Lees wat hij gezegd heeft',
    'nothing_is_running' => 'Er draait niets op deze machine',
    'no_forms_at_all' => 'Er is nog niets ingericht op deze machine',
    'by_form' => 'Of een heel formulier tegelijk',

    // N2-R8. Said before the yes and not after it.
    'about_to' => 'Op het punt :what te wijzigen',
    'about_to_form' => 'Dit is elke dienst in dat formulier, niet alleen die ene.',
    'would_not_help' => 'Hij start al keer op keer opnieuw. Nog een herstart komt in de rij.',
    'leaning_on_it' => 'Deze werken niet zolang hij uit staat:',
    'nothing_leans_on_it' => 'Niets anders in de stack heeft hem nodig.',
    'go_ahead' => 'Doe maar',
    'never_mind' => 'Laat maar',
    'awaiting' => [
        'downloads' => 'Totdat alles wat nog binnenkomt klaar is',
    ],
    'for_at_most' => 'Maximaal :seconds seconden',
];
