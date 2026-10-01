<?php

declare(strict_types=1);

// The phone's own settings.

return [
    'title' => 'Settings',
    'no_secure_storage' => 'This phone has no secure storage, so lemonfiber keeps nothing between launches.',
    'lock' => 'Lock',
    'lock_after' => 'Lock after',
    'lock_after_is' => 'How long lemonfiber can be away before it asks for your passcode again.',
    'after' => [
        'immediately' => 'Immediately',
        'one_minute' => '1 minute',
        'five_minutes' => '5 minutes',
        'fifteen_minutes' => '15 minutes',
        'one_hour' => '1 hour',
    ],
];
