<?php

declare(strict_types=1);

return [
    // Two wordings per notification, and which one is used is decided by the
    // type rather than at the call site. The guarded pair is what a
    // locked device may show: no stack, no service, no finding detail.
    'plain' => [
        'onset_title' => ':stack needs attention',
        'onset_body' => 'Open lemonfiber to see what it reported. Reference :code.',
        'resolved_title' => ':stack is back to normal',
        'resolved_body' => 'What it reported has stopped. Reference :code.',
        'more_title' => ':stack has more to tell you',
        'more_body' => 'Open lemonfiber to see everything it reported.',
    ],
    'guarded' => [
        'onset_title' => 'A stack needs attention',
        'onset_body' => 'Unlock to see which one, and what it reported.',
        'resolved_title' => 'A stack is back to normal',
        'resolved_body' => 'Unlock to see which one.',
        'more_title' => 'A stack has more to tell you',
        'more_body' => 'Unlock to see which one.',
    ],
];
