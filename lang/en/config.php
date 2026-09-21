<?php

declare(strict_types=1);

return [
    'setting_count' => '{0} This stack has nothing set|{1} This stack has one setting|[2,*] This stack has :count settings',

    // Said on every reading. The operator will also see these settings in the
    // stack's own interfaces, and what keeps the two from reading as two
    // different sources of truth is this app saying, plainly, that it is
    // showing the stack's list and not one of its own.
    'listing_is_the_stacks' => 'This is the list the stack sent, in the order it sent it.',

    'nothing_is_set' => 'Nothing is set here yet',
    'nothing_is_set_action' => 'Settings appear here once this stack has some.',

    'what_this_is_set_to' => 'What this machine is set to',

    'cost' => [
        'cheap' => 'A restart of the services it affects, and nothing else',
        'consequential' => 'This one is worth reading twice',
    ],

    'stance' => [
        'unchanged' => 'It already holds this',
        'pending' => 'Staged — nothing has been written yet',
        'blocked' => 'Nothing was written',
        'applied' => 'Written',
    ],

    'what_it_would_hold' => 'What it would hold',
    'holds_now' => 'It holds :value now',
    'holds_nothing_yet' => 'Nothing is in it yet',
    'what_would_happen' => 'See what this would do',
    'agree' => 'Make this change',
    'never_mind' => 'Leave it as it is',
    'change_this' => 'Change this',

    'agree_to' => 'Make this change to :key',
    'what_would_happen_to' => 'See what changing :key would do',
    'change_key' => 'Change :key',

    'would_hold' => 'It would hold :value',
    'services_will_restart' => 'The services this affects will restart.',
    'worth_reading_twice' => 'This one may move data or take a service away.',

    'ask_again' => 'Ask again',
];
