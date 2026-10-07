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

    // Handing one command to this machine to keep running, or taking it back.
    // Each control names its command, so two rows never carry the same words.
    'handing_over' => [
        'install' => 'Keep :name running on this machine',
        'remove' => 'Stop keeping :name running',
    ],
    'handing_over_asked' => [
        'install' => 'Keep :name running on this machine?',
        'remove' => 'Stop keeping :name running?',
    ],
    'handing_over_means' => [
        'install' => 'The machine\'s service manager is asked to run it. The stack then says whether it started and where it writes what it says.',
        'remove' => 'It will run only while a terminal holds it, and every file the install made is taken back.',
    ],

    // What came of it, as the stack said it. The heading names what was asked
    // for and never says it worked: whether the command runs is the standing.
    'handed_over' => [
        'heading' => [
            'install' => 'Asked to keep :name running',
            'remove' => 'Asked to stop keeping :name running',
            'did_not' => ':name was not handed over',
        ],
        'rehearsed' => 'A rehearsal: nothing on this machine was changed.',
        'stands' => 'Where it stands now: :standing',
        'started' => 'It was started.',
        'not_started' => 'It was not started.',
        'writes_to' => 'What it says is written to :output',
        'writes_unsaid' => 'The stack did not say where what it says is written.',
        'touched' => [
            'install' => 'Written: :file',
            'remove' => 'Taken back: :file',
        ],
        'would_touch' => [
            'install' => 'Would be written: :file',
            'remove' => 'Would be taken back: :file',
        ],
        'touched_nothing' => [
            'install' => 'No file was written.',
            'remove' => 'Nothing was there to take back.',
        ],
    ],
];
