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
    // A status the request service reported and lemonfiber has no word for. It
    // is shown rather than guessed at or dropped: the request is real, it
    // belongs to somebody, and the one thing nobody can say is where it has
    // got to.
    'unnamed' => 'Status not known here',
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
        // The same, said to the person who asked. They can see their own
        // request; what nobody can tell them is where it stands.
        'unnamed' => 'Status not known here',
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
    'turn_down_that' => 'Turn down :title',
    'turning_down' => 'Turning down :title',
    'turning_down_owes' => ':who will see what you write here, so say enough that they do not have to come and ask.',
    'reason_label' => 'Why not',
    'reason_placeholder' => 'There is no room for it this month',
    'reason_is_shown' => 'Shown to whoever asked for it.',
    'turn_it_down' => 'Turn it down',
    'never_mind' => 'Never mind',
    'nothing_asked' => 'Nobody has asked for anything.',
    'nothing_asked_action' => 'What the household asks for shows up here as soon as somebody requests something.',
    'refused_because' => 'Turned down: :reason',
    'refused_at' => 'Turned down at :when',
    // A member's four tabs, each also the title of the screen it opens.
    'tabs' => [
        'home' => 'Home',
        'search' => 'Search',
        'requests' => 'Requests',
        'profile' => 'Profile',
    ],

    // What the house says the person holding the session is owed. The
    // sentences themselves are the core's and are never in this catalogue —
    // these are the frame around them, and the two answers a reading can have
    // that are not sentences: nothing to tell you, and a way to ask again.
    'nothing_owed' => 'There is nothing to tell you here.',
    'nothing_owed_action' => 'The house has nothing to say about what you can ask for.',
    'signed_out' => 'You are signed out of this house.',
    'signed_in' => 'This house is open to you.',
    'ask_again' => 'Ask again',
    'ask_again_for_yours' => 'Ask again for what you asked for',
    'needs_an_update' => 'The house needs an update for this.',
    'needs_an_update_action' => 'Whoever looks after the house can update it.',

    // Searching, which this version of the app does not have yet.
    'search_is_coming' => 'Searching from your phone is coming.',
    'search_is_coming_action' => 'Until then, what you can watch is on Home, and what you asked for is under Requests.',

    // A member's own corner: changing house, the phone's settings, and taking
    // the house off this phone.
    'switch_house' => 'Switch house',
    'your_houses' => 'Your houses',
    'add_house' => 'Add a house',
    'open_house' => 'Open :house',
    'current_house' => ':house, the one you are in',
    'app_settings' => 'App settings',
    'remove_house' => 'Remove this house from the phone',
    'remove_house_confirm' => 'Remove :house from this phone? You can add it again later. Nothing changes at the house.',
    'remove' => 'Remove',
    'keep_it' => 'Keep it',
    'remove_house_refused' => 'The phone could not remove :house. Nothing was removed.',

    // What a member chose to hear and read titles in. Kept on this phone for
    // them, and never sent to the house.
    'languages' => [
        'hear' => 'Sound',
        'read' => 'Subtitles',
        'hear_original' => 'As it was made',
        'no_subtitles' => 'Off',
        'dutch' => 'Dutch',
        'english' => 'English',
        'kept_on_this_phone' => 'Kept on this phone. A title without your language plays its own sound, and no subtitles.',
    ],

    // What the house says this member may watch. The shelf itself is the
    // core's answer; these are the frame around it and the two answers that
    // are not a list — nothing on it, and a library that could not be reached.
    // The second is never drawn as the first: one says you have nothing, and
    // the other says your collection is out of reach.
    'shelf_is_empty' => 'There is nothing on your shelf.',
    'shelf_is_empty_action' => 'Anything the household adds for you shows up here.',
    'shelf_is_out_of_reach' => 'Your library could not be reached.',
    'shelf_is_out_of_reach_action' => 'The house answered, but could not read what is on your shelf.',
    // What stood in the way of reaching the house, as a member is told it:
    // the operator's sentences name a machine, an address and software, and a
    // member has none of those to look at.
    'out_of_reach' => [
        'no_answer' => 'The house is not answering right now.',
        'no_answer_action' => 'Check that this phone is on the home network, then try again in a moment.',
        'name_not_found' => 'This phone could not find the house.',
        'name_not_found_action' => 'Check that this phone is on the home network. If it is, ask whoever runs the house to set this phone up again.',
        'nothing_at_the_address' => 'The house is not where this phone last found it.',
        'nothing_at_the_address_action' => 'Ask whoever runs the house to set this phone up again.',
        'connection_refused' => 'The house is closed right now.',
        'connection_refused_action' => 'Ask whoever runs the house to open it again.',
    ],
    'medium' => [
        'film' => 'Film',
        'series' => 'Series',
        'episode' => 'Episode',
        'other' => 'Other',
    ],

    // The rows a shelf is drawn in: what came into the house most recently,
    // then one row for each kind it holds.
    'shelf' => [
        'carry_on' => 'Carry on watching',
        'new' => 'New in the house',
        'ready_for_you' => 'Ready for you',
        'on_its_way' => 'On its way',
        'film' => 'Films',
        'series' => 'Series',
        'episode' => 'Episodes',
        'other' => 'Other',
    ],

    // A poster on the shelf: the line at the top of its tile, and what a
    // screen reader says for it, which carries the whole title.
    'poster' => [
        'left' => ':minutes min left',
        'part_way' => 'Part-way through',
        'reads_part_way' => ':title, part-way through',
        'above' => ':year · :kind',
        'above_undated' => ':kind',
        'reads' => ':title, :kind, :year',
        'reads_undated' => ':title, :kind',
        'reads_standing' => ':title, :standing',
    ],

    // The newest title in the house, drawn across Home with Play and More.
    'hero' => [
        'above' => 'New in the house · :line',
        'reads' => 'New in the house: :reads',
        'more' => 'More',
        'more_named' => 'More about :title',
    ],

    // One title's screen, and the one action it carries. The reason Play
    // cannot be used is the app's own sentence, not the house's.
    'title' => [
        'play' => 'Play',
        'play_named' => 'Play :title',
        'nothing_to_play' => 'Nothing in this series can be played yet.',
        'absent' => 'This title isn\'t on your shelf. It may have left the house, or it isn\'t one you can watch.',
        'to_home' => 'Go to Home',
        'runs_hours' => ':hours h :minutes min',
        'runs_minutes' => ':minutes min',
        'certificate' => 'Rated :certificate',
        'released' => 'Released :day :month :year',
        'episode' => ':number. :title',
        'episode_unnumbered' => ':title',
        'between_genres' => ', ',
        'no_episodes' => 'There are no episodes in this season yet.',
    ],

    // Why Play did not play, or stopped where it could not go on, said as what
    // a member can do about it.
    'play' => [
        'cannot_be_played' => 'This can\'t be played right now. Let whoever looks after the house know.',
        'no_player' => 'Playing isn\'t possible on this device.',
        'out_of_reach' => 'The house can\'t be reached from here. Playing works when you\'re at home.',
        'not_the_house' => 'This stopped because something other than the house answered. Let whoever looks after the house know.',
        'not_on_this_device' => 'This device can\'t play this one.',
        'not_let_in' => 'This stopped because the house wouldn\'t let it play here. Press Play to try again.',
    ],

    // The months, by number, for when a title came out.
    'month' => [
        'january' => 'January',
        'february' => 'February',
        'march' => 'March',
        'april' => 'April',
        'may' => 'May',
        'june' => 'June',
        'july' => 'July',
        'august' => 'August',
        'september' => 'September',
        'october' => 'October',
        'november' => 'November',
        'december' => 'December',
    ],

    // The operator's preview of the member's side. The mark says what it is,
    // and its control leads back to the operator's own screens.
    'preview' => [
        'marked' => 'Preview: what a member sees',
        'about' => 'This is what somebody invited with the household\'s defaults sees. Nobody\'s own shelf or requests are shown.',
        'back' => 'Leave the preview',
        'ask' => 'Ask for something',
        'cannot_ask' => 'A preview cannot ask for anything.',
        'cannot_play' => 'A preview cannot play anything.',
    ],
];
