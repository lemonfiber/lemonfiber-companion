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
];
