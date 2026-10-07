<?php

declare(strict_types=1);

return [
    // De precieze opdracht achter een handeling, zoals de terminal van de
    // stack hem toont: wat er is uitgevoerd, of bij een voorproef wat er zou
    // worden uitgevoerd.
    'command' => [
        'ran' => 'De opdracht die is uitgevoerd',
        'will_run' => 'De opdracht die wordt uitgevoerd',
        'unread' => 'De stack kon niet zeggen welke opdracht wordt uitgevoerd',
    ],

    // Stackbestanden die de beheerder heeft aangepast, waar de stack ze ook
    // meldt. De regels van elk bestand zijn gemarkeerd zoals de stack ze
    // markeert: die van de beheerder `-`, die van lemonfiber `+`, en de
    // legenda noemt dezelfde tekens.
    'edits' => [
        'heading' => 'Bestanden die je hebt aangepast',
        'kept' => 'Je hebt :path aangepast, dus het blijft zoals je het achterliet.',
        'would_change' => 'Wat lemonfiber erin zou veranderen',
        'legend' => 'Regels met - zijn van jou, en regels met + zijn wat lemonfiber zou schrijven.',
        'theirs' => '- :line',
        'lemonfibers' => '+ :line',
    ],

    // De configuratie terugzetten. Een voorproef staat in de voorwaardelijke
    // wijs en nooit alsof het gebeurd is; alleen een verslag waarvan de stack
    // zegt dat het is uitgevoerd staat in de verleden tijd.
    'reset' => [
        'heading' => 'De configuratie terugzetten',
        'a_preview' => 'Een voorproef. Er is niets teruggezet.',
        'put_back' => 'De configuratie is teruggezet.',
        'would_revert_files' => 'Deze bestanden zouden teruggaan naar die van lemonfiber. Regels met - zijn bewerkingen die verloren zouden gaan; regels met + zijn wat lemonfiber zou schrijven.',
        'reverted_files' => 'Deze bestanden gingen terug naar die van lemonfiber. Regels met - zijn bewerkingen die verloren gingen; regels met + zijn wat lemonfiber schreef.',
        'would_revert_no_file' => 'Er zou geen bestand teruggaan.',
        'reverted_no_file' => 'Er ging geen bestand terug.',
        'differs_in_no_line' => 'Het verschilt van dat van lemonfiber in geen regel die getoond kan worden.',
        'differed_in_no_line' => 'Het verschilde van dat van lemonfiber in geen regel die getoond kan worden.',
        'would_revert_connections' => 'Deze verbindingen zouden mee teruggaan naar die van lemonfiber:',
        'reverted_connections' => 'Deze verbindingen gingen mee terug naar die van lemonfiber:',
        'would_revert_no_connection' => 'Er zou geen verbinding teruggaan.',
        'reverted_no_connection' => 'Er ging geen verbinding terug.',
        'would_change_nothing' => 'Er zou niets veranderen. Geen bestand en geen verbinding verschilt van die van lemonfiber.',
        'changed_nothing' => 'Er veranderde niets. Geen bestand en geen verbinding verschilde van die van lemonfiber.',
        'put_them_back' => 'Zet deze terug',
        'asking' => 'De stack zoekt uit wat het terugzetten van de configuratie zou veranderen.',
        'putting_back' => 'De stack zet de configuratie terug.',
        'no_preview' => 'De stack zegt niet meer wat het terugzetten van de configuratie zou veranderen.',
        'no_outcome' => 'De stack zegt niet meer wat er van het terugzetten van de configuratie geworden is. Misschien is het gebeurd. Opnieuw vragen opent een nieuwe voorproef van wat nog verschilt.',
        'refused_preview' => 'De stack wilde niet zeggen wat het terugzetten van de configuratie zou veranderen:',
        'refused' => 'De stack wilde de configuratie niet terugzetten:',
        'see_the_settings' => 'Bekijk de instellingen',
    ],
];
