<?php

declare(strict_types=1);

return [
    // Where a request stands, in the household's own words rather than the
    // wire's. Only the first of these is required; the rest are here because a
    // screen that showed only what is waiting would leave somebody wondering
    // what became of the thing they asked for last week.
    'waiting-for-approval' => 'Waiting for your decision',
    'declined' => 'Declined',
    'failed' => 'Could not be fetched',
    'getting' => 'Being fetched',
    'partly-here' => 'Partly here',
    'here' => 'Here',
    'gone' => 'No longer here',
    // The same states, said to the person who asked rather than to the
    // operator deciding. Only the first differs in meaning rather than in
    // wording: a member is not the one whose decision is waited on, and a
    // screen telling them so would ask them for something they cannot give.
    'asked' => [
        'waiting-for-approval' => 'Waiting for approval',
        'declined' => 'Declined',
        'failed' => 'Could not be fetched',
        'getting' => 'On its way',
        'partly-here' => 'Partly here',
        'here' => 'Here',
        'gone' => 'No longer here',
    ],
    'your_requests' => 'What you have asked for',
    'nothing_asked_for' => 'You have not asked for anything yet.',
    'waiting_count' => '{0} Nothing is waiting for you|{1} One request is waiting for you|[2,*] :count requests are waiting for you',
    'asked_by' => 'Asked for by :who',
    'size_measured' => ':size :unit',
    'size_guessed' => 'About :size :unit',
    'size_unknown' => 'Size not known yet',
    'megabytes' => 'MB',
    'gigabytes' => 'GB',
    'terabytes' => 'TB',
    // A waiting request is approvable and refusable from here, and the sentence
    // is part of turning one down rather than something beside it.
    'approve' => 'Approve',
    'approve_that' => 'Approve :title',
    'turn_down' => 'Turn it down',
    'turning_down' => 'Turning down :title',
    'turning_down_owes' => ':who will see what you write here, so say enough that they do not have to come and ask.',
    'reason_label' => 'Why not',
    'reason_placeholder' => 'There is no room for it this month',
    'reason_is_shown' => 'Shown to whoever asked for it.',
    'turn_it_down' => 'Turn it down',
    'never_mind' => 'Never mind',
    'nothing_asked' => 'Nobody has asked for anything.',
    'nothing_asked_action' => 'What the household asks for shows up here as soon as somebody requests something.',
    'asked_for' => 'What the household asked for',
    'refused_because' => 'Turned down: :reason',
    'refused_at' => 'Turned down at :when',
    // What the machine says the person holding the session is owed. The
    // sentences themselves are the core's and are never in this catalogue —
    // these are the frame around them, and the two answers a reading can have
    // that are not sentences: nothing to tell you, and a way to ask again.
    'yours' => 'What you can ask for',
    'nothing_owed' => 'There is nothing to tell you here.',
    'nothing_owed_action' => 'This machine has nothing to say about what you can ask for.',
    'ask_again' => 'Ask again',
    'back_to_the_machine' => 'Back to the machine',

    // What the machine says this member may watch. The shelf itself is the
    // core's answer; these are the frame around it and the two answers that
    // are not a list — nothing on it, and a library that could not be reached.
    // The second is never drawn as the first: one says you have nothing, and
    // the other says your collection is out of reach.
    'shelf' => 'What you can watch',
    'shelf_is_empty' => 'There is nothing on your shelf.',
    'shelf_is_empty_action' => 'Anything the household adds for you shows up here.',
    'shelf_is_out_of_reach' => 'Your library could not be reached.',
    'shelf_is_out_of_reach_action' => 'The machine answered, but could not read what is on your shelf.',
    'medium' => [
        'film' => 'Film',
        'series' => 'Series',
        'other' => 'Other',
    ],
];
