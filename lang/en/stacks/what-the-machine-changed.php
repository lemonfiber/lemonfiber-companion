<?php

declare(strict_types=1);

return [
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
            'rewind' => 'The file lemonfiber wrote over is written back to what it held',
            'repin' => 'Pinned back to the version it was on',
            'reconfigure' => 'The service\'s own setting is put back',
            'revoke' => 'The key it made is revoked',
            'reinstate' => 'The key it revoked is made good again',
        ],
    ],
];
