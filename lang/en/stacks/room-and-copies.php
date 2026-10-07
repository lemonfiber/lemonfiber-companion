<?php

declare(strict_types=1);

return [
    // How full the machine is, and where the room went.
    'room' => [
        // Where the machine, or one volume, stands.
        'level' => [
            'unknown' => 'How full it is could not be read',
            'ample' => 'Plenty of room',
            'advisory' => 'Less room than is comfortable',
            'warning' => 'Going to fill with what is on its way',
            'critical' => 'Nearly full',
            'exhausted' => 'Full',
        ],
        'halted' => 'New downloads are stopped so the services can still write',
        'holds' => [
            'data' => 'Where the media and downloads live',
            'services' => 'Where the services keep their settings and databases',
        ],
        'free' => ':figure :unit free',
        // Never drawn as nought: an unplugged drive is not a full one.
        'free_unread' => 'What is free could not be read',
        'limit' => 'Of :figure :unit',
        'committed' => ':figure :unit on its way',
        'projected' => ':figure :unit free once that has landed',
        'as_of' => 'As last read :ago',
        'no_volumes' => 'The stack names no volume it watches',
        'account' => 'Where the room went',
        'about' => [
            'tree' => ':tree',
            'landing' => 'Downloads still being written',
            'seeding' => 'Downloads still being seeded',
            'orphaned' => 'Downloads no service took in',
            'extracted' => 'Archives already unpacked',
            'services' => 'The services\' own settings and databases',
            'unmanaged' => 'What you said to leave alone',
        ],
        'occupies' => 'Takes :figure :unit',
        'unshared' => 'Would take :figure :unit if nothing were shared',
        'reclaim' => [
            'by_losing_content' => 'Getting this back means losing something you chose to keep',
            'in_progress' => 'Nothing to get back: it is being written now',
            'at_the_cost_of_ratio' => 'Can be got back, at the cost of your standing with the trackers',
            'the_easy_win' => 'Can be got back, and costs nothing',
            'already_have_it' => 'Can be got back: the unpacked copy is the one in use',
            'marginally' => 'A little could be got back, and rarely worth it',
            'you_said_not' => 'Not to be got back, because you said so',
        ],
        'nothing_accounted' => 'Nothing is taking room',
        'downloads' => 'Finished downloads on this machine',
        'takes' => 'Takes :figure :unit',
        'standing' => [
            'never_imported' => 'Never taken into a library',
            'seeding' => 'Still being seeded',
            'left_alone' => 'You asked for this to be left alone',
        ],
        'ratio' => 'Ratio :ratio',
        'no_ratio' => 'No ratio: nothing was downloaded to divide by',
        'no_downloads' => 'No finished downloads are on this machine',
        // On every download alike, so none is singled out.
        'stop_seeding' => 'Stop seeding this',
        'stop_seeding_that' => 'Stop seeding :download',
        'at_the_machine' => 'Stopping seeding is offered one download at a time. Anything else is removed at the machine, not from here',
    ],

    // Stopping seeding one download: what it costs first, and only then the yes.
    'let_go' => [
        'names_no_download' => 'This names no download to stop seeding',
        'see_the_room' => 'See how full the machine is',
        'working_it_out' => 'Asking the stack what stopping seeding :download would cost',
        'offer_ended' => 'The stack no longer knows what stopping seeding :download would cost. Ask again to hear it afresh.',
        'what_it_costs' => 'What stopping seeding this costs',
        // Stopping seeding is its own act, and said to be before the cost.
        'its_own_act' => 'Stopping seeding is its own act: it asks the download client to let this one download go and stop sharing it. Nothing else on the machine is removed.',
        'stop_it' => 'Stop seeding it',
        'letting_go' => 'Stopping seeding :download',
        'no_outcome' => 'The stack no longer knows what became of stopping seeding :download',
        'no_outcome_action' => 'The client may have let it go. How full the machine is says whether it is still there.',
        // A rehearsal is said to be one, and never reported as room freed.
        'a_rehearsal' => 'A rehearsal: nothing has been let go',
        'rehearsed' => 'The stack is rehearsing, so the client still holds :download and is still seeding it.',
        'nothing_freed' => 'No room was freed. It still occupies :figure :unit.',
        'let_go' => 'The client let :download go',
        'occupied' => 'It occupied :figure :unit, as the client reported it.',
    ],

    // What the machine keeps, where, and why, and the copies it holds.
    'keeps' => [
        'road_in' => 'What this machine keeps',
        'roots' => 'Where it is all kept',
        'no_roots' => 'The stack names nowhere it keeps things',
        'kept' => 'What the stack keeps',
        'nothing_kept' => 'The stack keeps nothing here',
        // Said on every row; the value is never shown.
        'secret' => [
            'secret' => 'Holds a secret, which is never shown here',
            'plain' => 'Holds no secret',
        ],
        'beside' => 'Here, and not the stack\'s',
        'nothing_beside' => 'Nothing here belongs to anybody else',
        'copies' => 'Copies of the stack',
        'no_copies' => 'No copy has been taken',
        // Never drawn as an empty list.
        'copies_unread' => 'The copies could not be listed',
        'reading_copies' => 'Reading the copies…',
        'take_a_copy' => 'Take a copy',
        // On every copy listed, and on nothing else: a copy the stack did not
        // list is not one it offers to put back.
        'put_back' => 'Put this copy back',
        'put_back_that' => 'Put back :copy',
    ],

    // Taking a copy. Every sentence about one is built around the scope, so
    // the whole stack, one service and somebody else's setup are always named.
    'copy' => [
        'what_to_copy' => 'What to take a copy of',
        'the_whole_stack' => 'The whole stack',
        'or_one_service' => 'Or one service on its own',
        'only_this_service' => 'Take a copy of :name only',
        'no_services' => 'The stack runs no service to copy on its own',
        'about_to' => 'About to take a copy of :scope',
        'may_remove' => 'The stack keeps a set number of copies, so taking one can remove the oldest. What it removed is said when it finishes.',
        'taking' => 'Taking a copy of :scope',
        // The stack reports how large a copy came to when it finishes, and
        // nothing while it runs, so nothing here pretends to know.
        'no_progress_while_running' => 'The stack says how large a copy came to once it has finished, and nothing about how far it has got while it runs.',
        'no_outcome' => 'The stack no longer knows what became of the copy of :scope',
        'no_outcome_action' => 'It may have been taken. The list of copies says whether it is there.',
        // A rehearsal is said to be one, and nothing about it is in the past tense.
        'a_rehearsal' => 'A rehearsal: nothing has been written',
        'copied' => 'Took a copy of :scope',
        'would_copy' => 'This would take a copy of :scope',
        'removed' => '{0} It removed no older copy|{1} It removed one older copy to make room|[2,*] It removed :count older copies to make room',
        'would_remove' => '{0} It would remove no older copy|{1} It would remove one older copy to make room|[2,*] It would remove :count older copies to make room',
        'kept_every_other' => 'Every other copy is still kept',
        'pace' => [
            'brisk' => 'It came to :moved :moved_unit, inside the :budget :budget_unit a copy is reckoned to manage in a minute.',
            'slow' => 'It came to :moved :moved_unit, past the :budget :budget_unit a copy is reckoned to manage in a minute, so a long wait was the size of what is kept rather than a fault.',
            'would_be_brisk' => 'It would come to :moved :moved_unit, inside the :budget :budget_unit a copy is reckoned to manage in a minute.',
            'would_be_slow' => 'It would come to :moved :moved_unit, past the :budget :budget_unit a copy is reckoned to manage in a minute, so a long wait would be the size of what is kept rather than a fault.',
        ],
        'holds_credentials' => 'It holds the stack\'s passwords and keys. Keep it as private as they are.',
        'holds_no_credentials' => 'It holds none of the stack\'s passwords or keys.',
        'would_hold_credentials' => 'It would hold the stack\'s passwords and keys.',
        'would_hold_no_credentials' => 'It would hold none of the stack\'s passwords or keys.',
        'see_the_copies' => 'See the copies',
        'scope' => [
            'whole_stack' => 'the whole stack',
            'service' => ':name alone',
            'existing' => 'the setup :name, which lemonfiber does not manage',
        ],
    ],

    // Putting a copy back. The listing comes first and is a rehearsal; what
    // the stack did is said only after the yes.
    'put_back' => [
        'names_no_copy' => 'This names no copy to put back',
        'a_rehearsal' => 'A rehearsal: nothing has been put back',
        'would_put_back' => 'Putting this copy back would restore :scope',
        'taken_by' => 'Taken by lemonfiber :version on :at',
        'older' => 'It comes from an older major version of lemonfiber. Putting it back is allowed, and may need a further reconcile after.',
        'would_move' => 'Its data would go to :now, not to :was where it was taken.',
        'would_go_where_it_was' => 'Its data would go back where it was taken from.',
        'would_overwrite' => 'It would overwrite:',
        'holds_nothing' => 'The copy lists nothing inside it',
        'only_while_stopped' => 'The stack puts a copy back only while it is stopped, and says so if it is not.',
        'put_it_back' => 'Put it back',
        'putting_back' => 'Putting :copy back',
        'no_progress_while_running' => 'The stack says what it put back once it has finished, and nothing about how far it has got while it runs.',
        'no_outcome' => 'The stack no longer knows what became of putting it back',
        'no_outcome_action' => 'It may have been put back. The machine\'s health is where to look.',
        'put_back' => 'Put back :scope, from a copy taken by lemonfiber :version',
        'moved' => 'Its data went to :now, not to :was where it was taken.',
        'where_it_was' => 'Its data went back where it was taken from.',
        // The stack's own answer, told apart from a stack that could not be
        // reached. Asking the same again is answered the same way.
        'would_not_list' => 'The stack will not put this copy back, and says why',
        'refused' => 'The stack did not finish putting this copy back, and says why',
        'same_answer' => 'Asking again gets the same answer until what it says has changed.',
        'look_again' => 'Read what putting it back would do now',
    ],
];
