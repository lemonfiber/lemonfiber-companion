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

    // Shown beside every value. Four, because the fourth is not a shade of the
    // first: a stack that could not work out where a value came from has said
    // something different from a stack reporting its own default, and the rule
    // about unknown origins turns on the two never reading alike.
    'came_from_bundled' => 'The stack\'s own setting',
    'came_from_operator' => 'Set here',
    'came_from_plugin' => 'Set by the :named plugin',
    'came_from_unknown' => 'Nobody could say where this came from — :why',

    'what_this_is_set_to' => 'What this machine is set to',

    'ask_again' => 'Ask again',
];
