<?php

declare(strict_types=1);

return [
    'heading' => 'Wekken',
    'switch' => 'Deze telefoon wekken',
    'none_installed' => 'Installeer een UnifiedPush-distributor, zoals ntfy, om deze telefoon te wekken als de stack iets te melden heeft. Zonder distributor beslist Android wanneer lemonfiber mag luisteren.',
    'off' => 'Deze stack kan deze telefoon via :distributor wekken als hij iets te melden heeft. Alleen het wekken gaat via :distributor; wat je te horen krijgt, wordt bij de stack gelezen.',
    'off_choosing' => 'Deze stack kan deze telefoon via een UnifiedPush-distributor wekken als hij iets te melden heeft. Alleen het wekken gaat via de distributor; wat je te horen krijgt, wordt bij de stack gelezen.',
    'on' => 'Aan: de stack wekt deze telefoon via :distributor.',
    'owed' => 'Uit op deze telefoon. De stack bewaart het adres van deze telefoon nog tot lemonfiber hem weer bereikt, en verwijdert het dan.',
    'choose' => 'Via welke distributor wordt deze telefoon gewekt? Je kiest één keer, voor alle stacks, en kunt het wijzigen in App-instellingen.',
    'no_code' => 'Voor dit adres kon geen code worden getekend.',
    'no_key_name' => 'Wekken wordt geregistreerd onder de naam van de sleutel van deze telefoon op de stack, en deze telefoon heeft daar nog geen sleutel.',
    'not_kept' => 'Deze telefoon kon niet bewaren dat de stack hem wekt, dus is de stack gevraagd het weer te vergeten.',
    'no_endpoint' => [
        'not_here' => 'Deze telefoon kan niet via een distributor worden gewekt.',
        'no_key' => 'Deze telefoon kon de sleutel niet maken waarmee de stack elke wekker versleutelt.',
        'refused' => 'De distributor wilde deze telefoon geen adres voor deze stack geven.',
        'no_answer' => 'De distributor antwoordde niet. Controleer of hij draait, en probeer het opnieuw.',
        'dropped' => 'De distributor heeft geen adres meer voor deze stack.',
    ],
    'removing_tells' => 'Deze stack wekt deze telefoon. :name wordt eerst gevraagd het adres van deze telefoon te vergeten.',
    'still_held' => 'Wekken staat uit voor :name op deze telefoon. De stack was niet bereikbaar, dus bewaart hij het adres van deze telefoon tot je het daar verwijdert.',
    'through' => 'Wekken via',
    'through_explained' => 'Elke stack die deze telefoon wekt, doet dat via deze distributor. Als je hem wijzigt, wordt elke stack opnieuw via de nieuwe geregistreerd.',
    'woken_through' => 'Deze stack wekt deze telefoon via :distributor.',
    'may_be_late' => 'Als lemonfiber gesloten is, beslist je telefoon wanneer het naar deze stack mag luisteren, dus het kan je laat of helemaal niet iets laten weten.',
];
