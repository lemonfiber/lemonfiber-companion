<?php

declare(strict_types=1);

return [
    // Two wordings per notification, and which one is used is decided by the
    // type rather than at the call site (N4-R20). The guarded pair is what a
    // locked device may show: no stack, no service, no finding detail.
    'plain' => [
        'title' => ':stack needs attention',
        'body' => 'Open lemonfiber to see what it reported. Reference :code.',
    ],
    'guarded' => [
        'title' => 'A stack needs attention',
        'body' => 'Unlock to see which one, and what it reported.',
    ],
];
