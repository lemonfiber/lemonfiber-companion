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
    'nothing_to_try' => 'The stack did not suggest anything to try for this.',
    'ask_again' => 'Check again',
    'see_how_it_is' => 'See how this stack is doing',
    'working_it_out' => 'This stack is working out what it could put right.',
    'working_it_out_action' => 'It takes a moment. Ask again shortly.',
    'nothing_came_back' => 'That question has expired.',
    'nothing_came_back_action' => 'Nothing was carried out. Ask again.',
    'nothing_to_put_right' => 'This stack has nothing to put right.',
    'affects_nothing_else' => 'Affects nothing else.',
    'would_put_right' => 'See what could be put right',
    'back_to_the_stack' => 'Back to this stack',
    'carrying_it_out' => 'This stack is doing it.',
    'carrying_it_out_action' => 'It takes a moment. Ask again shortly.',
    'nobody_knows_what_happened' => 'What became of this is not known.',
    'nobody_knows_what_happened_action' => 'Something may well have happened. Look at how this stack is doing now rather than asking for it again.',
    'changed_count' => '{0} Nothing was changed|{1} One thing was put right|[2,*] :count things were put right',
    'worth_another_go' => 'Trying again could give a different answer.',
    'agree_to_it' => 'Do this',
    'mended' => [
        'fixed' => 'Put right',
        'fix_failed' => 'Could not be put right',
        'stopped' => 'Stopped part-way',
        'declined' => 'The stack declined it',
        'would_overwrite' => 'Not done: it would overwrite something',
    ],
    'nothing_was_carried_out' => 'There turned out to be nothing to do.',
    'look_again' => 'Look again at what could be put right',
    // N2-R9 — what has stopped coming in. The stage is where it stopped, and it
    // is the whole difference between an indexer with nothing and a file the
    // library never picked up.
    'stage' => [
        'not-monitored' => 'Nothing is watching for this',
        'monitored' => 'Being watched for, not looked for yet',
        'searching' => 'Being looked for',
        'found' => 'A copy exists and has not been taken',
        'grabbed' => 'Taken, not started coming down',
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

    // N2-R10 — a bounded, searchable read that names the service and says the
    // view is a window rather than the whole.
    'stream' => [
        'stdout' => 'Output',
        'stderr' => 'Noticed',
    ],
    'what_a_service_said' => 'See what this service said',
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
    ],

    // N2-R7 — what is running, and how much each one matters. The state is
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

    'what_it_runs' => 'What it is running',

    // N2-R7's screen. The verbs above are the buttons; these are the sentences
    // around them — what a row says about itself, and what a stop is stated to
    // disturb before anybody agrees to it.
    'in_form' => 'Part of :form',
    'it_exited' => 'It ended with :code',
    'host_runs_it' => 'This machine runs it, not the stack',
    'read_its_logs' => 'Read what it has been saying',
    'nothing_is_running' => 'Nothing is running on this machine',
    'no_forms_at_all' => 'Nothing has been set up on this machine yet',
    'by_form' => 'Or a whole form at once',

    // N2-R8. Said before the yes and not after it.
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
];
