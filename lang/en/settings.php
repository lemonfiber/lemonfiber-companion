<?php

declare(strict_types=1);

// The phone's own settings.

return [
    'title' => 'Settings',
    'no_secure_storage' => 'This phone has no secure storage, so lemonfiber keeps nothing between launches.',
    'lock' => 'Lock',
    'lock_after' => 'Lock after',
    'lock_after_is' => 'How long lemonfiber can be away before it asks for your passcode again.',
    'readings' => 'Readings',
    'keep_readings' => 'Keep readings',
    'keep_readings_is' => 'Older readings are deleted from this phone.',
    'days' => ':count day|:count days',
    'one_year' => '1 year',
    'until_removed' => 'Until removed',
    'other' => 'Other…',
    'days_label' => 'Days',
    'days_between' => 'Between :fewest and :most days.',
    'save' => 'Save',
    'saved_data' => 'Saved data',
    'clear_saved_data' => 'Clear saved data',
    'clear_confirm' => 'Clear every reading and setting on this phone? Your stacks stay paired and you stay signed in.',
    'clear' => 'Clear',
    'keep_it' => 'Keep it',
    'remove_from_phone' => 'Remove from phone',
    'remove_confirm' => 'Remove :name from this phone? Its readings and settings go with it. The stack itself keeps running.',
    'remove' => 'Remove',
    'remove_refused' => 'lemonfiber could not remove :name from this phone. Nothing was removed.',
    'after' => [
        'immediately' => 'Immediately',
        'one_minute' => '1 minute',
        'five_minutes' => '5 minutes',
        'fifteen_minutes' => '15 minutes',
        'one_hour' => '1 hour',
    ],
];
