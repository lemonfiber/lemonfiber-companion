<?php

declare(strict_types=1);

return [
    // A stack's refusal in its own words, on whichever screen asked.
    'refusal' => [
        'named' => 'It names :named',
    ],

    // How the machine shares its line with the household.
    'line' => [
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
