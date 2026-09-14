<?php

declare(strict_types=1);

return [
    // Where a request stands, in the household's own words rather than the
    // wire's. `N2-R11` is about the first of these; the rest are here because a
    // screen that showed only what is waiting would leave somebody wondering
    // what became of the thing they asked for last week.
    'waiting-for-approval' => 'Waiting for your decision',
    'declined' => 'Declined',
    'failed' => 'Could not be fetched',
    'getting' => 'Being fetched',
    'partly-here' => 'Partly here',
    'here' => 'Here',
    'gone' => 'No longer here',
    'waiting_count' => '{0} Nothing is waiting for you|{1} One request is waiting for you|[2,*] :count requests are waiting for you',
    'asked_by' => 'Asked for by :who',
    'size_measured' => ':size :unit',
    'size_guessed' => 'About :size :unit',
    'size_unknown' => 'Size not known yet',
    'megabytes' => 'MB',
    'gigabytes' => 'GB',
    'terabytes' => 'TB',
    'nothing_asked' => 'Nobody has asked for anything.',
    'nothing_asked_action' => 'What the household asks for shows up here as soon as somebody requests something.',
    'see_health' => 'See how this stack is doing',
    'asked_for' => 'What the household asked for',
    'refused_because' => 'Turned down: :reason',
    'refused_at' => 'Turned down at :when',
];
