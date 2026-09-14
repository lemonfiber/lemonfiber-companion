<?php

declare(strict_types=1);

return [
    // One reason and one alternative for every permission the app asks for.
    //
    // `N4-R2` wants the app's own words *before* the system prompt, which means
    // a sentence that exists whether or not anybody has been asked yet.
    // `N4-R3` wants every permission optional with a working alternative, and an
    // alternative nobody can read is not offered — so it is a sentence too,
    // written in terms of what the operator can still do rather than of what the
    // app cannot.
    'local_network_reason' => 'lemonfiber talks to your stack over your own network, and nowhere else.',
    'local_network_alternative' => 'Without it, you can still read what your stack last reported while you were connected.',
    'camera_reason' => 'The camera is used once, to read the pairing code on your stack.',
    'camera_alternative' => 'You can type the pairing code instead.',
    'notifications_reason' => 'Notifications tell you when a stack needs attention.',
    'unlock' => 'Unlock',
    'unlock_reason' => 'Unlock lemonfiber to see your stacks.',
    'notifications_alternative' => 'Without them, open lemonfiber to see what a notification would have said.',
    'permission_refused' => 'That permission was refused, and this screen still works without it.',
    'nowhere_to_write_it' => 'This phone has no room to prepare the report.',
    'nowhere_to_write_it_action' => 'Free up some space and try again.',
    'the_device_would_not_offer' => 'This phone would not offer a way to send it.',
    'the_device_would_not_offer_action' => 'The report is on the screen above; you can copy it by hand.',
    'share_diagnostics' => 'Send a report to someone helping you',
];
