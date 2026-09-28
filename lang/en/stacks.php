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
        // The way to putting back what was done at one moment. It opens a
        // screen that says what goes with it; nothing is put back from here.
        'put_back' => 'What putting this back takes',
        // The same, for somebody being read to, naming the moment.
        'put_back_that' => 'What putting back ":did", :when, takes',
    ],

    // Putting back one run the record shows. The record's own rows are what is
    // agreed to, because the stack takes no yes and offers no rehearsal of it.
    'run_back' => [
        'names_no_run' => 'This names nothing on the record to put back',
        // A stamp nothing on the record carries: fallen past the horizon, or
        // already put back.
        'not_on_the_record' => 'The record holds nothing done at that moment. It may be older than the record reaches, or already put back.',
        // Said before the yes, with the count the record's own row gives.
        'goes_with_it' => '{1} Putting this back puts back the one change below.|[2,*] Putting this back puts back all :count changes made together, never some of them.',
        'whole_or_nothing' => 'The stack puts back the whole of it or none of it, and says what it could not put back and why.',
        'cannot_go_back' => 'One of these cannot be put back, so the stack would put back none of them.',
        'put_it_back' => 'Put it back',
        'putting_back' => 'Putting it back',
        'no_progress_while_running' => 'The stack says what it put back once it has finished, and nothing about how far it has got while it runs.',
        'no_outcome' => 'The stack no longer knows what became of putting it back',
        'no_outcome_action' => 'It may have gone back. The record says what is there now.',
        // The stack's own answer, told apart from a stack that could not be
        // reached. Asking after the same work is answered the same way.
        'refused' => 'The stack answered, and did not put this run back',
        'refused_named' => 'It names :named',
        'refused_same_answer' => 'Asking again gets the same answer. The record says what is there now, and the run can be chosen there again once what the stack said has changed.',
        // A rehearsal is said to be one, and nothing about it is in the past tense.
        'a_rehearsal' => 'A rehearsal: nothing has been put back',
        // What was left leads, in the report's tense.
        'did' => [
            'all' => 'All of it went back.',
            'not_all' => 'Not all of it went back. Still standing, and why:',
            'reversed' => 'What went back:',
            'none_reversed' => 'Nothing went back',
        ],
        'would' => [
            'all' => 'All of it would go back.',
            'not_all' => 'Not all of it can be promised. What might stay standing, and why:',
            'reversed' => 'What would go back:',
            'none_reversed' => 'Nothing would go back',
        ],
        // It goes back, and going back still leaves something behind.
        'noted' => 'Going back also means:',
        // What putting one change back does, in the stack's word for it.
        'does' => [
            'remove' => 'What it created is removed',
            'restore' => 'The setting is put back to what it held',
            'delete' => 'What it made is deleted',
            'withdraw' => 'What lemonfiber wrote into the file is taken back out',
            'repin' => 'Pinned back to the version it was on',
            'reconfigure' => 'The service\'s own setting is put back',
        ],
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

    // The credentials the machine holds to let services in. No line here
    // offers to set, change or show a value.
    'credentials' => [
        'road_in' => 'What it holds to let services in',
        'heading' => 'Credentials',
        'state' => [
            'absent' => 'Missing: something here needs it and it was never supplied',
            'active' => 'Working',
            // Never drawn as broken: it has not been proven, which is different.
            'stale' => 'Not proven since it was last written',
            'invalid' => 'Refused the last time it was used',
            // Mid-change, and the old value still works.
            'rotating' => 'Being replaced; the current one still works',
            'superseded' => 'Replaced; the old one is waiting to be destroyed',
        ],
        'origin' => [
            'operator' => 'You supplied it, from an account elsewhere',
            'service' => 'The service made it for itself',
            'lemonfiber' => 'lemonfiber made it',
        ],
        'used_by' => 'Used by',
        'used_by_nothing' => 'Nothing uses it',
        'none' => 'This machine holds no credentials',
        'protection' => [
            'heading' => 'How they are kept',
            'against' => 'This protects against:',
            'not_against' => 'This does not protect against:',
            'nothing_listed' => 'Nothing listed',
        ],
        'at_the_machine' => 'A credential is set or replaced at the machine, not from here.',
    ],

    // Which app to watch on, device by device.
    'clients' => [
        'road_in' => 'Which app to watch on',
        'heading' => 'What to watch on',
        'support' => [
            'good' => 'Well served',
            'workable' => 'Works, with something to know first',
            'poor' => 'Poorly served',
            // An answer, not the absence of one.
            'fallback' => 'Works anywhere, nothing to install',
        ],
        'instead' => 'Instead: :instead',
        'straining' => 'Playback may struggle here with the :preset preset',
        'no_devices' => 'No devices are listed',
        'trouble' => 'When it does not work',
        'no_causes' => 'Nothing is listed behind this',
        'no_trouble' => 'Nothing is listed for when it does not work',
    ],

    // Where the household comes in, and what else they can reach.
    'front_door' => [
        'road_in' => 'Where the household comes in',
        'standing' => [
            'established' => 'The front door is open',
            'library-only' => 'The library is the front door; there is nothing here to ask for',
            'unreachable' => 'The front door is not answering',
            'stranded' => 'The front door is answering, and no other device can be told where it is',
            'none' => 'Nothing here is open to the household',
        ],
        'chosen' => [
            // Never drawn as anybody's decision.
            'derived' => 'Worked out from what this stack runs; nobody chose it',
            'named' => 'You chose it: :named',
            'refused' => 'You chose :named, and it was refused: :because',
        ],
        'facing' => [
            'asking' => 'Where asking for something begins',
            'watching' => 'The library, where what arrived is watched',
            'shelf' => 'One kind of media, reached from the library',
            'operators' => 'An index of every service, including ones the household should not see',
            'carriage' => 'How the others are reached',
            'unstated' => 'Open to the household, and nothing says what it is to them',
        ],
        'no_address' => 'This machine did not say where it is reached',
        'beside' => 'What else they can reach',
        'nothing_beside' => 'Nothing else is open to the household',
    ],

    // Asking somebody in: what an invitation grants, sending it, handing it
    // over, and letting somebody choose a new password.
    'invitation' => [
        'road_in' => 'Ask somebody in',
        'name' => 'Their name',
        'name_is' => 'The name they will sign in as',
        'libraries' => 'Libraries',
        'libraries_are' => 'Separated by commas, as this stack names them. Leave it empty for every library',
        'age' => 'Age limit',
        'age_is' => 'Things rated above this age are held back. Leave it empty for no limit',
        'unrated_is' => 'Anything with no rating: :choice',
        'unrated' => [
            'held-back' => 'held back from them',
            'let-through' => 'let through to them',
            'left_to_the_stack' => 'left to this stack to decide',
        ],
        'hold_unrated_back' => 'Hold anything with no rating back',
        'let_unrated_through' => 'Let anything with no rating through',
        'leave_unrated_to_the_stack' => 'Leave unrated material to this stack',
        'needs_a_name' => 'Say who this is for: an invitation needs a name',
        'age_is_a_number' => 'An age limit is a whole number of years',
        'what_would_it_grant' => 'See what inviting them would do',
        'working' => 'This stack is working on it',
        'no_outcome' => 'This stack no longer has an answer for what was asked about :name. The household screen says who is in',
        'refused' => 'This stack would not do this for :name',
        'rehearsed' => 'This is what inviting :name would do. Nothing has been made yet',
        'standing' => [
            'made' => 'A new account for :name',
            'waiting' => 'An invitation for :name already stands. It is the one to send, and no second one is made',
            'joined' => ':name is already in the household. Nothing is sent',
            'reset' => 'The password on the account of :name is taken off. They choose a new one at the address',
        ],
        'grants' => 'What it lets them do',
        'grants_nothing' => 'The stack did not say what it grants',
        'every_library' => 'Every library',
        'limited_to' => 'Up to :limit',
        'no_limit' => 'No age limit',
        'asking' => [
            'made' => 'They can ask for things as well as watch',
            'not-yet' => 'They can watch, and cannot ask for anything yet: the request service has not been told, and the next run tells it',
            'not-tried' => 'The request service was not asked: this is a rehearsal, or this stack has none',
        ],
        'lapses' => '{1} It stands for :count hour. If nobody takes it up by then, it is withdrawn and the account with it|[0,*] It stands for :count hours. If nobody takes it up by then, it is withdrawn and the account with it',
        'send' => 'Invite :name as shown',
        'to_hand_over' => 'The address to hand over',
        'code' => 'The address as a code another phone can scan',
        'no_code' => 'This address could not be drawn as a code. Hand it over as text',
        'pass_on' => 'Hand it over',
        'passed_on' => 'Handed to the sharing on this phone. Where it goes from there is up to you',
        'not_passed_on' => 'This phone would not offer a way to pass it on. Nothing was sent, and the address is above to hand over another way',
        'covering' => '{1} You are invited into the household on :stack, as :name. Open this address to choose your password. It stands for :count hour.|[0,*] You are invited into the household on :stack, as :name. Open this address to choose your password. It stands for :count hours.',
        'would_withdraw' => 'Invitations nobody took up, which this would take back',
        'withdrew' => 'Invitations nobody took up, taken back on the way',
        'nobody_withdrawn' => 'None',
        'would_switch_off' => 'Resets nobody took up, which this would switch off and keep',
        'switched_off' => 'Resets nobody took up, switched off on the way and kept',
        'nobody_switched_off' => 'None',
        'start_again' => 'Start again',
        'who_is_in' => 'Who is in already',
        'ask_who_is_in_again' => 'See who is in again',
        'member' => [
            'joined' => 'In the household',
            'still_invited' => 'Invited, and has not taken it up yet',
        ],
        'nobody_in' => 'Nobody has an account on the media server yet',
        'would_take_it_off' => 'Let :name choose a new password',
        'taking_it_off_means' => 'The password :name has now stops working, and they choose a new one at the address this gives you. You never see or set it',
        'take_it_off' => 'Take the password off for :name',
        'never_mind' => 'Leave it as it is',
    ],

    // What is already on the machine, before anything is moved in.
    'already_here' => [
        'road_in' => 'What is already on this machine',
        'found' => 'What lemonfiber found already running here',
        // Never the same screen as a machine with nothing on it.
        'could_not_look' => 'lemonfiber could not look at what is running here, so this is not an empty machine',
        'nothing_found' => 'Nothing else is set up on this machine',
        'project' => 'Project :project',
        'running' => 'Running',
        'stopped' => 'Present, and stopped',
        'adoptable' => 'lemonfiber could take it over as it stands',
        'not_adoptable' => 'lemonfiber cannot take it over as it stands',
        'ports' => 'Publishes :ports',
        'no_ports' => 'Publishes no port',
        'no_services' => 'It has no services',
        'conflicts' => 'Ports already taken',
        'no_conflicts' => 'No port lemonfiber wants is taken',
        'conflict' => 'Port :port is held by :held_by, and lemonfiber\'s :wanted_by wants it',
        'beside' => 'Where each service would be reached, beside what is here',
        'moved' => ':service would be on port :to instead of :from',
        'none_moved' => 'No service would move to another port',
        'cannot_take' => 'What cannot be taken over',
        'unsupported' => ':what: :because',
        'nothing_unsupported' => 'Nothing found is out of lemonfiber\'s reach',
        'cannot_link' => 'This layout cannot hold a hardlink',
        'filesystems' => 'The filesystems it is about: :filesystems',
        'remedy_is_yours' => 'Nothing here does this for you: it is yours to do, on your own disks',
        'modes' => 'What may be done about it',
        'no_modes' => 'This machine offered nothing to do about it',
        'disturbs' => 'Stops or alters what is already running',
        'disturbs_nothing' => 'Leaves what is already running as it is',
        'preselected' => 'Offered already chosen',
        'look_again' => 'Look at what is on this machine again',
    ],

    // Moving in beside what is already on the machine: asking what a mode
    // would come to, and agreeing to it.
    'moving_in' => [
        'ask' => [
            'adopt' => 'What would taking it over come to?',
            'import' => 'What would importing its records come to?',
            'beside' => 'What would standing beside it come to?',
            'replace' => 'What would standing in its place come to?',
        ],
        'about' => 'Moving in: :mode',
        'working' => 'The machine is working out where this stands',
        'no_outcome' => 'The machine no longer has an answer for this',
        'refused' => 'The machine turned this down',
        'leave_it' => 'Leave it',
        // Where a move stands, as the stack gave it. Only applied says
        // anything was done.
        'stance' => [
            'unchanged' => 'Nothing needed doing, and nothing was done',
            'pending' => 'Nothing has been done yet',
            'blocked' => 'The machine turned this away, and nothing was done',
            'applied' => 'Done',
        ],
        // An import that carried nothing and one that has not run are both
        // quiet, and mean opposite things.
        'import' => [
            'not_run' => 'Nothing has been carried across yet: this is what importing would do',
            'nothing_to_carry' => 'It ran, and there was nothing to carry: lemonfiber already holds what the old setup held',
            'carried_nothing' => 'It ran, and carried nothing across',
        ],
        'left_behind' => 'What did not come across',
        'would_leave_behind' => 'What would not come across',
        'not_carried' => ':what: :because',
        'nothing_left_behind' => 'Nothing is left behind',
        'nothing_listed' => 'The machine listed nothing more about it',
        'upgrade' => ':service is :existing here and :ours in lemonfiber (:verdict). :because',
        'upgrade_refused' => ':service will not be taken over: it is :existing here and :ours in lemonfiber (:verdict). :because',
        'backed_up' => 'A copy was written to :path before anything was opened',
        'would_carry' => ':service would take :name (:kind)',
        'carried' => ':service took :name (:kind)',
        'listens' => ':service is on port :to instead of :from',
        'written' => 'Where each service listens is written in :path',
        'would_stop' => ':service would be stopped, and not deleted',
        'stopped' => ':service was stopped, and not deleted',
        'still_running' => ':service would not stop, and is still running',
        // Said with the decision rather than after it.
        'before_you_agree' => 'Before you agree',
        'copy_first' => ':service\'s database is upgraded when lemonfiber opens it, so it wants a copy first: :because',
        'copies' => 'A copy is taken of :path before anything is opened',
        'nothing_copied_first' => 'Nothing here wants a copy first',
        'agree' => [
            'adopt' => 'Take it over',
            'import' => 'Carry these across',
            'beside' => 'Stand beside it',
            'replace' => 'Stop it and stand in its place',
        ],
    ],

    // Wiring the services to each other, and how each connection turned out.
    'wiring' => [
        'road_in' => 'How the services are wired to each other',
        'what_a_run_does' => 'A run changes nothing that is already right, and keeps what you changed by hand',
        'wire' => 'Wire the services',
        'services' => 'The services a run wires to each other',
        'no_services' => 'This machine runs no service to wire',
        'working' => 'The machine is wiring the services',
        'no_outcome' => 'The machine no longer has an answer for this run',
        'refused' => 'The machine turned this down',
        // Said before anything else, and never drawn as a run that wrote.
        'rehearsed' => 'A rehearsal: this run only said what it would do, and nothing was written',
        'assessed' => 'Each connection was judged against what lemonfiber last wrote',
        'unassessable' => 'Whether anything was changed by hand could not be judged this time: the record of what lemonfiber last wrote could not be read',
        // One sentence per state. Skipped is not failed, and a value changed
        // by hand is kept, not wired and not something to put back.
        'state' => [
            'wired' => 'Wired, and read back',
            'already_wired' => 'Already wired; nothing was done',
            'drifted' => 'Kept as you changed it',
            'stale' => 'Still lemonfiber\'s own value, behind what it would write now; left as it is',
            'conflicted' => 'You and lemonfiber both changed it; left as it is',
            'adopted' => 'Your change is kept as the way it is meant to be',
            'unmanaged' => 'A value lemonfiber never wrote; kept as it was',
            'would_wire' => 'A run that writes would wire this',
            'would_adopt' => 'A run that writes would keep your value as the way it is meant to be',
            'observed' => 'Left alone, because you said to',
            'skipped' => 'Not wired yet: something it needs is not there, and a later run finishes it',
            'failed' => 'The service rejected it',
            'refused' => 'lemonfiber will not do this',
        ],
        'yours' => 'The service holds: :value',
        'ours' => 'lemonfiber would write: :value',
        'breaks' => 'This breaks something: :breakage',
        'remedy' => 'To put it right: :remediation',
        'no_connections' => 'This run attempted no connection',
        'cannot_wire' => 'What this run cannot wire',
        'nothing_unsupported' => 'Every service here is one this run can speak to',
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
        'only' => 'Only :name',
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

    // A stack's refusal in its own words, on whichever screen asked.
    'refusal' => [
        'named' => 'It names :named',
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

    // Asking for help: a support bundle, described before it is written.
    'help' => [
        'road_in' => 'Ask for help',
        'what_goes_in' => 'What goes in the bundle',
        'nothing_leaves' => 'The stack describes the bundle first. Nothing is written until you agree to that description, and this app adds nothing to the bundle and sends it nowhere: a written bundle is yours to hand over, through your phone\'s own sharing.',
        'lines' => '{1} The last line of each service\'s logs|[2,*] The last :count lines of each service\'s logs',
        'take_lines' => '{1} Take the last line|[2,*] Take the last :count lines',
        'filenames_shown' => 'Media filenames are shown as they are',
        'filenames_replaced' => 'Media filenames are replaced',
        'show_filenames' => 'Show media filenames',
        'replace_filenames' => 'Replace media filenames',
        'revealing' => 'Settings shown as they are',
        'revealing_is_publishing' => 'Every setting that holds a secret is redacted. A setting shown as it is will be read by whoever you give the bundle to, so each one is named and agreed to on its own.',
        'reveals' => 'Shows :name as it is',
        'take_back' => 'Stop showing :name',
        'reveals_nothing' => 'Shows no setting as it is',
        'reveal_this' => 'Show :name as it is? Its value will be in the bundle for anyone who reads it.',
        'reveal' => 'Show :name',
        'keep_it_hidden' => 'Keep it redacted',
        'setting_label' => 'A setting to show as it is',
        'setting_placeholder' => 'The setting\'s name, as the bundle names it',
        'name_it' => 'Ask about this setting',
        'describe' => 'Describe the bundle',
        'gathering' => 'The stack is gathering the bundle.',
        'no_outcome' => 'The stack no longer says what became of this bundle.',
        'start_over' => 'Change what goes in',
        'refused' => 'The stack refused this bundle',
        'refused_named' => 'It names :named',
        'refused_wrote_nothing' => 'Nothing was written. The stack gives the same answer until what it names has changed.',
        'written' => 'The bundle is written',
        'described' => 'Nothing has been written yet',
        'would_go' => 'It would be written to :path',
        'written_at' => 'It is on the machine at :path',
        'would_go_unsaid' => 'The stack does not say where it would be written',
        'bytes' => '{1} One byte|[0,*] :count bytes',
        'taken' => 'Taken at :at, by lemonfiber :lemonfiber, from stack :stack',
        'missing' => 'Not collected: :what',
        'nothing_missing' => 'Everything was collected',
        'holds_no_files' => 'It holds no files',
        'write' => 'Write this bundle',
        'hand_over' => 'Hand it over',
        'handed_over' => 'The bundle is in your phone\'s sharing.',
        'yours_to_send' => 'Where it goes is yours to choose. This app sent it nowhere.',
        'not_held_here' => 'The bundle could not be put on this phone to hand over. A full phone is the commonest reason.',
        'not_offered' => 'This phone would not offer a way to hand it over.',
        'still_on_the_machine' => 'Nothing left this phone. The bundle is still on the machine.',
    ],
];
