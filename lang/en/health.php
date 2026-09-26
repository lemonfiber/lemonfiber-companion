<?php

declare(strict_types=1);

return [
    'category' => [
        'environment' => 'Environment',
        'storage' => 'Storage',
        'network' => 'Network',
        'vpn' => 'VPN',
        'credentials' => 'Credentials',
        'services' => 'Services',
        'providers' => 'Providers',
        'queue' => 'Queue',
        'config' => 'Configuration',
    ],
    // Who put a check in the report. Drawn beside a check that is not the
    // stack's own, with the legend said once under a report that has one.
    'origin' => [
        'bundled' => 'One of the stack\'s own checks',
        'operator' => 'A check added here',
        'plugin' => 'A check from the :named plugin',
        'unknown' => 'Nobody could say where this check came from — :why',
        'overridden' => 'A check the :named plugin changed',
        'orphaned' => 'A check from the :named plugin, which is no longer installed',
        'legend' => 'A check marked with where it came from is not one of the stack\'s own; every other is.',
    ],
    'conclusion' => [
        'fail' => 'Failed',
        'unverified' => 'Could not be checked',
        'warn' => 'Needs attention',
        'pass' => 'Passed',
        'skipped' => 'Skipped',
    ],
    'severity' => [
        'critical' => 'Data or something outside this machine is at risk',
        'error' => 'Broken',
        'warning' => 'Degraded',
        'advisory' => 'Worth knowing',
    ],
    'undoing' => [
        'permanent' => 'This cannot be undone',
        'possible' => 'This can be undone afterwards',
    ],
    'overall' => [
        'broken' => 'Something is broken',
        'unknown' => 'Health could not be determined',
        'degraded' => 'Some services need attention',
        'healthy' => 'Everything is running',
    ],
    'standing' => [
        'healthy' => 'Everything is fine.',
        'stopped' => 'Not running, because it was stopped. That is not a fault.',
        'unconfigured' => 'Not set up yet.',
        'advisory' => 'Everything is working, with notes.',
        'degraded' => 'Working, but not as it should.',
        'broken' => 'Something is broken.',
        'critical' => 'Urgent: something needs attention now.',
        'unknown' => 'How this stack is cannot be told right now.',
    ],
    'summary' => [
        'waiting' => 'Waiting to hear from this stack.',
        'as_of' => 'Last heard :ago. Nothing said since is known.',
        'notes' => '{1} One note|[2,*] :count notes',
        'wanting' => '{1} One thing needs attention|[2,*] :count things need attention',
        'reported' => '{1} One thing reported|[2,*] :count things reported',
        'also' => 'Also because of this: :what',
    ],
    'because_of' => 'Because of: :title',
    'ago' => [
        'minutes' => '{0} moments ago|{1} a minute ago|[2,*] :count minutes ago',
        'hours' => '{1} an hour ago|[2,*] :count hours ago',
        'days' => '{1} a day ago|[2,*] :count days ago',
    ],
    'stale' => 'Last checked :ago',
    'family_and_count' => ':family (:count)',
    'no_findings' => 'Nothing needs attention.',
    'repair_refused' => 'The stack refused that repair: :reason',
    'what_it_says_underneath' => 'What the check reported',
    'nothing_to_do_with_it' => 'There is nothing to do with it from here.',
    'nothing_to_try' => 'The stack did not suggest anything to try for this.',
    'ask_again' => 'Check again',
    'see_how_it_is' => 'See how this stack is doing',
    'working_it_out' => 'This stack is working out what it could put right.',
    'working_it_out_action' => 'It takes a moment. Ask again shortly.',
    'nothing_came_back' => 'That question has expired.',
    'nothing_came_back_action' => 'Nothing was carried out. Ask again.',
    'nothing_to_put_right' => 'This stack has nothing to put right.',
    'affects_nothing_else' => 'Affects nothing else.',
    'carrying_it_out' => 'This stack is doing it.',
    'carrying_it_out_action' => 'It takes a moment. Ask again shortly.',
    'nobody_knows_what_happened' => 'What became of this is not known.',
    'nobody_knows_what_happened_action' => 'Something may well have happened. Look at how this stack is doing now rather than asking for it again.',
    'changed_count' => '{0} Nothing was changed|{1} One thing was put right|[2,*] :count things were put right',
    'worth_another_go' => 'Trying again could give a different answer.',
    'agree_to_it' => 'Do this',
    'agree_to_that' => 'Do this: :repair',
    'mended' => [
        'fixed' => 'Put right',
        'fix_failed' => 'Could not be put right',
        'stopped' => 'Stopped part-way',
        'declined' => 'Left alone: it was not agreed to',
        'would_overwrite' => 'Not done: it would overwrite something',
        // Apart from the line above: not something changed by hand, but an
        // area the operator told the stack to leave alone.
        'unmanaged' => 'Left alone: you declared this unmanaged',
    ],
    'nothing_was_carried_out' => 'There turned out to be nothing to do.',
    'look_again' => 'Look again at what could be put right',
    // What has stopped coming in. The stage is where it stopped, and it
    // is the whole difference between an indexer with nothing and a file the
    // library never picked up.
    //
    // `:stage` is the stack's own word, put in untranslated. The sentences
    // under `stage` sit beside it and say where that leaves the item; they
    // are not the word, and they do not swap one of its words for a word of
    // this app's — `grab` is lemonfiber's, so the sentences say what a grab
    // did rather than calling it something else.
    // Where one item got to. `:stage` and `:item` are the stack's words,
    // put in as they came.
    'trace' => [
        'road_in' => 'Where did “:item” get to?',
        'nothing_named' => 'Type a title to follow it through the services',
        'nothing_asked_for' => 'Nothing being watched for is called “:item”: nobody has asked for it',
        'following' => 'Following “:item”',
        'confidence' => [
            'certain' => 'Matched on what the services agree identifies it',
            'uncertain' => 'Matched by a guess from its name: this may not be the item you meant',
        ],
        'outcome' => [
            'grabbed' => 'Sent to the download client',
            'download-failed' => 'The download client failed it',
            'imported' => 'Imported to the library',
            'removed' => 'Its file was removed',
        ],
        'furthest' => 'Furthest: :stage',
        'stopped' => 'Why it stopped: :why',
        'series_here' => ':have of :wanted wanted episodes are here',
        'nobody_asked_for' => '{1} One episode nobody asked for is not counted|[2,*] :count episodes nobody asked for are not counted',
        'season' => 'Season :season: :have of :wanted here',
        'episode' => 'Episode :number, “:title”: :stage',
        'season_all_here' => 'Every wanted episode is here',
        'no_seasons' => 'No season of it is counted',
        'the_way' => 'The way it came',
        'recorded_at' => ':service, :at',
        'recorded_untimed' => ':service, with no time given',
        'no_stages' => 'It has not reached a stage yet',
        'tried' => 'What has been tried',
        'nothing_tried' => 'Nothing has been tried yet',
        'disagree' => 'Where the services disagree',
        'agree' => 'The services agree about it',
        'search_label' => 'Follow something else',
        'search_placeholder' => 'A title',
        'follow' => 'Follow it',
    ],
    'at_stage' => 'Stage: :stage',
    'stage' => [
        'not-monitored' => 'Nothing is watching for this',
        'monitored' => 'Being watched for, not looked for yet',
        'searching' => 'Being looked for',
        'found' => 'A release exists and has not been sent to the download client',
        'grabbed' => 'Sent to the download client, not started coming down',
        'downloading' => 'Coming down now',
        'downloaded' => 'Down, not handed to the library yet',
        'importing' => 'Being handed over',
        'imported' => 'Handed over, not playable yet',
        'available' => 'There and playable',
    ],
    'shown' => [
        'all-of-it' => 'This is everything the stack has stopped on.',
        'some-of-it' => 'The stack has more than this; it sent part of the list.',
    ],
    'stuck_count' => '{0} Nothing has stopped coming in|{1} One thing has stopped coming in|[2,*] :count things have stopped coming in',
    'stuck_in' => 'In :service',
    'stuck_for_good' => 'Nothing will move this by itself.',
    'undeclared_count' => '{0} Nothing else is running here|{1} One other thing is running here|[2,*] :count other things are running here',
    'undeclared_explained' => 'These are running on the machine, and this stack\'s own configuration does not mention them. lemonfiber did not start them and will not stop them.',
    'nothing_undeclared' => 'Nothing else is running here.',
    'nothing_undeclared_action' => 'Everything on this machine is something this stack declared.',
    'what_else_is_running' => 'What else is running here',

    'nothing_stopped' => 'Nothing has stopped coming in.',
    'nothing_stopped_action' => 'Everything the house asked for is on its way or already here.',
    'what_stopped' => 'What stopped coming in',

    // A bounded, searchable read that names the service and says the
    // view is a window rather than the whole.
    'stream' => [
        'stdout' => 'Output',
        'stderr' => 'Noticed',
    ],
    'what_a_service_said' => 'See what this service said',
    'what_that_service_said' => 'See what :service said',
    'logs_for' => 'What :service has been saying',
    'window_of' => 'The last :count lines this stack kept. There may be more behind them.',
    'the_whole_of_it' => 'All :count lines this stack has for this service.',
    'search_label' => 'Find in these lines',
    'search_placeholder' => 'A word from the line you want',
    'search_is_over_the_window' => 'Searching what is shown above, not the whole scrollback.',
    'matched_count' => '{0} Nothing here matches|{1} One line matches|[2,*] :count lines match',
    'nothing_matched' => 'No line in this window holds that.',
    'nothing_matched_action' => 'It may be further back than this window reaches. Clear the search to see the whole window again.',
    'service_said_nothing' => 'This service has said nothing.',
    'service_said_nothing_action' => 'It is running quietly, or it has only just started.',
    'no_moment' => 'No time given',
    'every' => [
        'while_work_runs' => 'Looking again every :count seconds while this runs.',
        'while_listening' => 'Listening to this stack, and looking at what it said every :count seconds.',
        'after_a_break' => 'This stack could not be heard. Listening again every :count seconds.',
    ],

    // What is running, and how much each one matters. The state is
    // where a service stands now; how much it matters is what it would cost if
    // that went wrong, which is a property of the machine's design.
    'service' => [
        'failed' => 'Fell over',
        'crash-looping' => 'Falling over and starting again',
        'unhealthy' => 'Up, and answering badly',
        'absent' => 'Expected, and not there',
        'stopped' => 'Turned off',
        'starting' => 'Starting',
        'running' => 'Running',
        'healthy' => 'Running well',
        'host-managed' => 'Run by the machine, not by this stack',
    ],
    'matters' => [
        'critical' => 'Nothing else works without it',
        'core' => 'Part of the stack itself',
        'important' => 'The house would notice today',
        'enhancing' => 'The house would notice eventually',
        'optional' => 'Nobody would notice',
    ],
    'running' => [
        'inactive' => 'Nothing is running',
        'degraded' => 'Running, with something wrong',
        'partial' => 'Some of it is running',
        'active' => 'Everything is running',
    ],
    'do' => [
        'start' => 'Start it',
        'stop' => 'Stop it',
        'restart' => 'Restart it',
    ],



    // The supervising screen. The verbs above are the buttons; these are the
    // sentences around them — what a row says about itself, and what a stop is stated to
    // disturb before anybody agrees to it.
    'it_exited' => 'It ended with :code',
    'host_runs_it' => 'This machine runs it, not the stack',
    'read_its_logs' => 'Read what it has been saying',
    'nothing_is_running' => 'Nothing is running on this machine',
    'no_forms_at_all' => 'This stack declares no forms',
    'by_form' => 'Or a whole form at once',

    // The second granularity, on the screen about one of them. A route
    // can name something the machine has since stopped running, which is an
    // answer rather than a blank frame.
    'nothing_of_that_name' => 'This machine is not running anything called :name',
    'back_to_what_runs' => 'Back to what is running',
    'open_service' => 'Open :name',
    'open_form' => 'Open the :name form',
    'a_whole_form' => 'A form is every service in it. The verbs below reach all of them.',
    'leaned_on_by' => ':name will not work without it',

    // What a stop disturbs. Said before the yes and not after it.
    'about_to' => 'About to change :what',
    'about_to_form' => 'This is every service in that form, not just one of them.',
    'would_not_help' => 'It is already restarting over and over. Another restart joins the queue.',
    'leaning_on_it' => 'These will not work while it is off:',
    'nothing_leans_on_it' => 'Nothing else in the stack depends on it.',
    'go_ahead' => 'Go ahead',
    'never_mind' => 'Never mind',
    'awaiting' => [
        'downloads' => 'Until everything still coming down has finished',
    ],
    'for_at_most' => 'For up to :seconds seconds',
    'forms_running' => 'Running for :forms',
    'no_form_running' => 'No form is running.',
    'runs_for' => 'For :forms',
    'runs_for_no_form' => 'No running form asked for it',
    'left_out_heading' => 'Left out on purpose',
    'left_out' => ':name, asked for by :forms: :needs',
    'nothing_left_out' => 'The forms running left nothing out.',
    'rehearsal' => [
        'heading' => 'What starting it would do',
        'nothing_started' => 'Nothing has started. This is what the stack says starting it would do.',
        'would_start' => ':name would start',
        'would_start_nothing' => 'No service would start.',
        'left_out' => ':name would be left out: :needs',
        'estimate' => 'The stack estimates about :mib MiB of memory. That is its estimate, not a measurement.',
        'unestimated' => ':services declare no estimate, so the real figure is higher.',
        'nothing_left_out' => 'Nothing would be left out.',
        'needs' => [
            'usenet' => 'it needs Usenet credentials, which this stack does not have',
            'torrent' => 'it needs torrent credentials, which this stack does not have',
        ],
    ],
];
