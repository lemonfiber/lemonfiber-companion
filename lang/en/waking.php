<?php

declare(strict_types=1);

return [
    'heading' => 'Waking',
    'switch' => 'Wake this phone',
    'none_installed' => 'Install a UnifiedPush distributor, such as ntfy, to wake this phone when the stack has something to say. Without one, Android decides when lemonfiber may listen.',
    'off' => 'This stack can wake this phone through :distributor when it has something to say. Only the wake goes through :distributor; what you are told is read from the stack.',
    'off_choosing' => 'This stack can wake this phone through a UnifiedPush distributor when it has something to say. Only the wake goes through the distributor; what you are told is read from the stack.',
    'on' => 'On: the stack wakes this phone through :distributor.',
    'owed' => 'Off on this phone. The stack still holds this phone\'s address until lemonfiber next reaches it, and will remove it then.',
    'choose' => 'Which distributor wakes this phone? You choose once, for every stack, and can change it in App settings.',
    'no_code' => 'No code could be drawn for this address.',
    'no_key_name' => 'Waking is registered under the name this phone\'s key has on the stack, and this phone has no key there yet.',
    'not_kept' => 'This phone could not keep that the stack wakes it, so the stack was asked to forget it again.',
    'no_endpoint' => [
        'not_here' => 'This phone cannot be woken through a distributor.',
        'no_key' => 'This phone could not make the key the stack would encrypt each wake to.',
        'refused' => 'The distributor would not give this phone an address for this stack.',
        'no_answer' => 'The distributor did not answer. Check that it is running, and try again.',
        'dropped' => 'The distributor no longer holds an address for this stack.',
    ],
    'removing_tells' => 'This stack wakes this phone. :name is asked to forget this phone\'s address first.',
    'still_held' => 'Waking is off for :name on this phone. The stack could not be reached, so it still holds this phone\'s address until you remove it there.',
    'through' => 'Wake through',
    'through_explained' => 'Every stack that wakes this phone does so through this distributor. Changing it registers each of them again through the new one.',
    'woken_through' => 'This stack wakes this phone through :distributor.',
    'may_be_late' => 'While lemonfiber is closed, your phone decides when it may listen to this stack, so it may tell you late or not at all.',
];
