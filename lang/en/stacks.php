<?php

declare(strict_types=1);

return [
    // What stands between one long-running command and the machine, in the
    // operator's words rather than the wire's. Two of the six are the whole
    // reason this group is written out: *installed-unverified* is the answer
    // that reads like running and is not, and *unsupported* is the one that
    // reads like off and cannot be switched on.
    'hosting' => [
        'not-hosted' => 'Runs only while a terminal is open',
        'hosted' => 'Comes back after a restart',
        'installed-unverified' => 'Installed — the machine would not say whether it is running',
        'stopped' => 'Installed, and not running',
        'orphaned' => 'Installed for a program that is no longer there',
        'unsupported' => 'Not available on this machine',
    ],

    // The road in, from the machine's own screen. It names the question
    // rather than the mechanism: an operator knows what a restart is and
    // does not need to know what a launch agent is to want this.
    'what_keeps_running' => 'What survives a restart',

    // How many are installed and are not running. Said before the rows for
    // the stuck screen's reason: somebody who opened this the morning after a
    // reboot should not have to count them.
    'did_not_come_back' => '{1} One thing did not come back|[2,*] :count things did not come back',

    // Only an orphan has one, and it names the program rather than the
    // service — the difference between *this is not running* and *the file it
    // runs is not there any more*.
    'missing_program' => 'The program it runs is gone: :program',

    // A machine that keeps nothing running. Its own sentence rather than a
    // blank list, and distinct from the machine this product cannot configure,
    // which carries an instruction instead.
    'keeps_nothing_running' => 'Nothing here survives a restart',
    'keeps_nothing_running_action' => 'Set one up at the machine, and it will show here.',

    // What the machine uses to keep things running when nobody is signed in.
    // Named rather than described: an operator reading `launchd` can search for
    // it, and a sentence about *the system service manager* leaves them nothing
    // to type.
    'keeps-running' => [
        'launchd' => 'launchd, in your own login session',
        'systemd' => 'systemd, in your own session',
        'unsupported' => 'This machine has no service manager lemonfiber sets up',
    ],

    // How far one change the stack made could be put back. `none` is a word of
    // its own rather than a blank: a change that cannot be undone says so.
    'reversal' => [
        'whole' => 'Can be put back completely',
        'partial' => 'Can be put back only in part',
        'none' => 'Cannot be put back',
    ],

    // What the machine has changed about itself.
    'record' => [
        // The road in, naming the question rather than the mechanism.
        'road_in' => 'What changed here',
        // The stack's own sentence about how far back the record reaches,
        // after a lead-in of ours. Said where the list ends, whether or not
        // anything is above it. The lead-in claims nothing about what fell
        // off: the stack's sentence says whether anything older was dropped,
        // and a lead-in guessing either way would contradict it half the time.
        'horizon' => 'What is kept: :horizon.',
        // What did it, and to what.
        'by' => ':operation, to :target',
        // Always said, including when it came alone: undoing one line of a
        // larger operation leaves a machine in a state nobody chose.
        'alongside' => '{1} Made on its own|[2,*] One of :count changes made together',
        // Why putting it back stops short — never why it was made, which the
        // stack does not say.
        'stops_short' => 'Stops short because: :because',
        'instead' => 'Instead: :instead',
        // An answer, within the horizon below it, and not a machine that could
        // not be asked.
        'nothing_changed' => 'Nothing has been changed',
        // Where the stack's clock would not say when. A sentence rather than
        // a date, because the only date the stack wrote was nobody's guess.
        'clock_unreadable' => 'At a time the machine could not tell',
    ],

    // Where each service on the machine comes from.
    'origins' => [
        // The road in, naming the question rather than the mechanism.
        'road_in' => 'Where this comes from',
        // Said once over the list. Nothing here is looked up, so a project
        // that has gone away changes none of it — which somebody reading a
        // licence should know is not a check made today.
        'as_declared' => 'As this machine declares it. Nothing here is looked up from the projects themselves.',
        // The image and the version it is pinned at, printed together because
        // a version without its image names nothing that can be fetched.
        'runs' => 'Runs :image, pinned at :pinned',
        // On every row, not only where it is unusual.
        'licence' => 'Licence: :licence',
        'upstream' => 'Built from :upstream',
        // An answer, and not a machine that could not be asked.
        'nothing_declared' => 'This machine declares no services',
    ],

    // Everything that leaves the machine, in two lists that are never merged.
    'outbound' => [
        // The road in, naming the question rather than the mechanism.
        'road_in' => 'What leaves this machine',
        // Each of lemonfiber's own requests, by what it asks for.
        'asks_for' => [
            'registry' => 'Fetching service images',
            'guides' => 'Checking the quality guides',
            'echo' => 'Finding this machine\'s public address',
            'indexer' => 'Proving an indexer key',
            'usenet' => 'Proving a Usenet login',
            'household' => 'Telling a household member something',
            'updates' => 'Checking for a newer lemonfiber',
        ],
        'allowed' => [
            'allowed' => 'Allowed by this machine\'s settings',
            'switched_off' => 'Switched off',
        ],
        'ours' => [
            'heading' => 'What lemonfiber itself sends',
            'sends' => 'Sends: :sends',
            'goes_to' => 'To :destination',
            // Not *switched off*: a request can be allowed with nowhere to go.
            'nowhere' => 'Nowhere is configured for it to reach',
            'switch' => 'Switched off by :switch',
            // On every row, because turning something off is the decision.
            'cost' => 'Turning it off: :cost',
            'none' => 'lemonfiber itself sends nothing',
        ],
        'theirs' => [
            'heading' => 'What the services send',
            'reaches' => 'Reaches :destination',
            // An answer: the stack records this service reaching nothing.
            'reaches_nothing' => 'Reaches nothing',
            // Not an answer. Said in this app's words rather than the stack's
            // placeholder, and never as *nothing*.
            'unrecorded' => 'lemonfiber has no record of what this reaches',
            'none' => 'No service here sends anything',
            // Who put the service on the stack, drawn where that is not the
            // stack itself, with the legend said once where anything is.
            'origin' => [
                'bundled' => 'One of the stack\'s own services',
                'operator' => 'A service added here',
                'plugin' => 'Brought by the :named plugin',
                'unknown' => 'Nobody could say where this service came from — :why',
                'overridden' => 'Changed by the :named plugin',
                'orphaned' => 'Brought by the :named plugin, which is no longer installed',
                'legend' => 'A service marked with where it came from is not one of the stack\'s own; every other is.',
            ],
        ],
    ],

    // What the machine will tell its operator about.
    'alerts' => [
        'road_in' => 'What you are told about',
        // The preset's name is the stack's; what it means is drawn beside it.
        'preset' => 'Preset: :preset',
        'set_apart' => 'Set apart from the preset',
        'heard' => [
            'heard' => 'You hear about this, whatever the preset says',
            'silenced' => 'Kept quiet, whatever the preset says',
        ],
        'nothing_set_apart' => 'Nothing is set apart; every event follows the preset',
        // This screen reads the setting and changes nothing.
        'changed_at_the_machine' => 'Changed at the machine, not from here',
    ],

    // Which version of lemonfiber the machine runs.
    'itself' => [
        'road_in' => 'Which lemonfiber this is',
        'running' => 'lemonfiber :version',
        'installed' => [
            'homebrew' => 'Installed with Homebrew',
            'scoop' => 'Installed with Scoop',
            'winget' => 'Installed with winget',
            'cargo' => 'Installed with cargo',
            'distribution' => 'Installed by the system\'s package manager',
            'installer' => 'Installed with lemonfiber\'s installer',
            'elsewhere' => 'Installed some other way',
            // Never read as a copy lemonfiber can replace.
            'untellable' => 'How it was installed could not be told',
        ],
        'owner' => 'Kept up to date by :owner',
        'standing' => [
            'current' => 'This is the newest version',
            'update-available' => 'A newer version is out',
            'managed-externally' => 'Another tool updates this copy',
            // Never read as up to date.
            'check-failed' => 'Whether a newer version is out could not be checked',
        ],
        'offered' => 'Newest: :version',
        'run_at_the_machine' => 'To update, run this at the machine:',
        'not_the_services' => 'This is lemonfiber itself. The services are updated from their own screen.',
    ],

    // What lemonfiber's words mean, as the stack's glossary explains them.
    'words' => [
        'road_in' => 'What lemonfiber\'s words mean',
        'heading' => 'What lemonfiber\'s words mean',
        'search_label' => 'Find a word',
        'search_placeholder' => 'A word, or what another app calls it',
        'also_called' => 'Also called: :names',
        'in_place' => ':word: :short',
        'more' => 'More about ‘:word’',
        'less' => 'Less about ‘:word’',
        'nothing_matched' => 'No word, and nothing else a word is called, matches that',
        'none' => 'This machine explains no words',
    ],

    // How full the machine is, and where the room went.
    'room' => [
        'road_in' => 'How full this machine is',
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
        'at_the_machine' => 'Removing anything is done at the machine, not from here',
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
        'at_the_machine' => 'Copies are taken and put back at the machine, not from here',
    ],

    // How the machine shares its line with the household.
    'line' => [
        'road_in' => 'How the line is shared',
        'restraint' => [
            'unlimited' => 'Nothing holds the stack back',
            'limited' => 'The stack is held to a limit',
            'scheduled-active' => 'The house is up, so the stack is held back',
            'scheduled-quiet' => 'The house is asleep, so the line is the stack\'s',
            'overridden' => 'The limits are lifted for now',
            'cap-warning' => 'Close to this month\'s cap',
            'cap-exceeded' => 'This month\'s cap has been reached',
        ],
        // Each direction in the stack's own sentence.
        'down' => 'Download: :says',
        'up' => 'Upload: :says',
        'upload_cost' => 'Holding the upload back costs: :costs',
        'capacity' => 'What the line carries',
        'carries' => ':down :down_unit down, :up :up_unit up',
        // A line's speed, in what it is sold in.
        'rate' => [
            'kilobits' => 'kbit/s',
            'megabits' => 'Mbit/s',
            'gigabits' => 'Gbit/s',
        ],
        'measured' => [
            // Declared is a claim; said as one.
            'declared' => 'As declared, not measured',
            'observed' => 'As the stack has seen it move',
        ],
        'tunnel' => [
            'through' => 'Measured through the private tunnel the stack\'s traffic takes',
            'beside' => 'Measured beside the private tunnel the stack\'s traffic takes',
        ],
        'unmeasured' => 'Nothing has measured the line',
        'monthly_cap' => 'Monthly cap',
        // Nought is a cap, and drawn as one.
        'capped_at' => ':figure :unit a month',
        'cap' => [
            'pause' => 'Reaching it stops fetching until the month turns over',
            'throttle' => 'Reaching it slows fetching so what is half-finished can finish',
            'continue' => 'Reaching it changes nothing; fetching carries on',
        ],
        'month' => [
            'within' => 'This month is comfortably inside it',
            'warning' => 'This month is close to it',
            'exceeded' => 'This month has reached it',
        ],
        // Not a cap of nothing: no cap was declared.
        'uncapped' => 'No cap is declared',
        'untouched' => 'Outside every limit',
        'nothing_untouched' => 'Nothing is outside the limits',
        'no_cautions' => 'The stack has nothing to add about this reading',
        'changed_at_the_machine' => 'Limits and caps are changed at the machine, not from here',
    ],
];
