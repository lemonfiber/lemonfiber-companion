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
    'notifications_alternative' => 'Without them, open lemonfiber to see what a notification would have said.',
    'permission_refused' => 'That permission was refused, and this screen still works without it.',
];
