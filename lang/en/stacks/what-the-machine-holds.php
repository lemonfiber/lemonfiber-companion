<?php

declare(strict_types=1);

return [
    // What each service on the machine is for, and what became of any the
    // stack dropped. What it does and what the house goes without lead; the
    // name follows.
    'catalogue' => [
        'as_declared' => 'As this machine describes its own services, with nothing started.',
        'without_it' => 'Without it: :without',
        'nothing_declared' => 'This machine declares no services',
        'dropped' => 'No longer part of this stack',
        'removed_in' => 'Dropped in :version: :reason',
        // Where something took its place, named so somebody looking for the
        // old one is answered.
        'replaced_by' => 'Replaced by :by',
        // Nothing did, which is the commonest answer.
        'not_replaced' => 'Nothing took its place',
        'nothing_dropped' => 'This stack has not dropped any service',
        // The stack's own answer, told apart from a machine that is not
        // answering: it could not read the description of itself.
        'refused' => 'The stack answered, and could not say what its services are for',
        'same_answer' => 'Asking again gets the same answer until what it names is put right on the machine.',
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
        // Beside the version, the digest that makes the pin immutable.
        'digest' => 'Pinned to digest :digest',
        // An image pinned by tag alone, said rather than left blank.
        'no_digest' => 'Pinned by tag alone; this machine names no digest for it',
        // On every row, not only where it is unusual.
        'licence' => 'Licence: :licence',
        'upstream' => 'Built from :upstream',
        // An answer, and not a machine that could not be asked.
        'nothing_declared' => 'This machine declares no services',
    ],

    // Everything that leaves the machine, in two lists that are never merged.
    'outbound' => [
        // The road in, naming the question rather than the mechanism.
        // Each of lemonfiber's own requests, by what it asks for.
        'asks_for' => [
            'registry' => 'Fetching service images',
            'guides' => 'Checking the quality guides',
            'echo' => 'Finding this machine\'s public address',
            'indexer' => 'Proving an indexer key',
            'usenet' => 'Proving a Usenet login',
            'household' => 'Telling a household member something',
            'updates' => 'Checking for a newer lemonfiber',
            'plugin-source' => 'Fetching a plugin from its source',
            'catalogue' => 'Looking up a plugin in the catalogue',
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
        'running' => 'lemonfiber :version',
        'installed' => [
            'homebrew' => 'Installed with Homebrew',
            'scoop' => 'Installed with Scoop',
            'winget' => 'Installed with winget',
            'cargo' => 'Installed with cargo',
            'distribution' => 'Installed by the system\'s package manager',
            'installer' => 'Installed with lemonfiber\'s installer',
            'image' => 'Installed from lemonfiber\'s container image',
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
        'is_out' => 'lemonfiber :version is out',
        'run_at_the_machine' => 'To update, run this at the machine:',
        'not_the_services' => 'This is lemonfiber itself. The services are updated from their own screen.',
    ],

    // Which versions a machine runs, reached from About. Each version is
    // explained where it is shown, and notes that do not describe this copy
    // are said as what they are rather than left out.
    'versions' => [
        'road_in' => 'What is running',
        'title' => 'Versions',
        'lemonfiber' => 'lemonfiber :version',
        'lemonfiber_means' => 'The program on the machine that sets up the services and answers this app.',
        'stack' => 'Stack :version',
        'stack_means' => 'The services lemonfiber runs and how they are connected, as this version ships them.',
        'engine' => 'Container engine :version',
        'engine_unknown' => 'Container engine not known',
        'engine_means' => 'What runs each service in a container of its own. Nothing starts without it.',
        'notes_pending' => 'Notes not written yet',
        'notes_pending_means' => 'This version is out, and what it changed has not been written down yet.',
        'notes_stale' => 'Notes out of step',
        'notes_stale_means' => 'The notes this copy carries do not match the version it is, so they are not shown.',
    ],

    // A guard on the data location, held while its screen asks. Every line
    // keeps it apart from what the machine hosts: it lives only while the
    // screen asks about it, and says so before it starts and while it runs.
    'guard' => [
        'road_in' => 'Guard the data location while you watch',
        'apart' => 'Or guard the data location only while a screen of this app is open, which is not hosting it',
        'heading' => 'Guard the data location while you watch',
        'lives_while_asked' => 'This guard lives only while this screen keeps asking about it. Leave the screen and it stops.',
        'not_hosted' => 'It is not handed to the machine, so it does not outlast this screen, a locked phone or a restart of the stack.',
        'to_host_one' => 'Keep a guard running when nobody is looking',
        'would_do' => 'While it guards, the stack looks at the data location. If it disappears or turns out to be a different drive, the stack stops the forms named here, so they do not write a library onto whatever is left, and does not start them again.',
        'not_said_before' => 'This stack does not say beforehand which location it would guard, how often it looks, or the command it would run.',
        'which_forms' => 'Which forms should it stop?',
        'name' => 'Name ‘:form’',
        'named' => '‘:form’ is named',
        'leave_out' => 'Leave ‘:form’ out',
        'no_forms' => 'This stack declares no forms to guard',
        'start' => 'Guard the forms named',
        'about_to' => 'Guard the data location for :forms?',
        'guarding' => 'Guarding the data location for :forms',
        'saw_it_go' => 'The guard saw the data location go',
        'stopped_them' => 'It stopped these forms:',
        'did_not_stop_them' => 'It could not stop these forms, and they may still be writing to whatever is left:',
        'named_no_forms' => 'The stack named no forms',
        'did_not_start' => 'The guard did not start',
        'let_go' => 'The guard was let go without seeing anything: it was released, or nothing asked about it for long enough',
        'unknown' => 'This stack no longer knows the guard. It restarted, and the guard did not survive that, so nothing is guarding now',
        'start_over' => 'Name forms for another guard',
    ],

    // What lemonfiber's words mean, as the stack's glossary explains them.
    'words' => [
        'heading' => 'What lemonfiber\'s words mean',
        'search_label' => 'Find a word',
        'search_placeholder' => 'A word, or what another app calls it',
        'also_called' => 'Also called: :names',
        'in_place' => ':word: :short',
        'more' => 'More about ‘:word’',
        'less' => 'Less about ‘:word’',
        'nothing_matched' => 'No word, and nothing else a word is called, matches that',
        'none' => 'This machine explains no words',
        'ask' => 'Ask this machine what ‘:word’ means',
        'unexplained' => 'This machine has no entry for ‘:word’ either, so it is shown as it came',
        'ask_in_place' => 'Ask what ‘:word’ means',
    ],
];
