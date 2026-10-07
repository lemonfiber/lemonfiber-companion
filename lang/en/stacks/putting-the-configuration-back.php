<?php

declare(strict_types=1);

return [
    // The exact command behind a verb, as the stack's terminal prints it: what
    // it ran, or for a rehearsal what it would run.
    'command' => [
        'ran' => 'The command it ran',
        'will_run' => 'The command it will run',
        'unread' => 'The stack could not say which command it will run',
    ],

    // Stack files the operator edited, wherever the stack reports them. Each
    // file's lines are marked as the stack marks them: the operator's `-`,
    // lemonfiber's `+`, and the legend names the same marks.
    'edits' => [
        'heading' => 'Files you edited',
        'kept' => 'You edited :path, so it is kept as you left it.',
        'would_change' => 'What lemonfiber would change in it',
        'legend' => 'Lines marked - are yours, and lines marked + are what lemonfiber would write.',
        'theirs' => '- :line',
        'lemonfibers' => '+ :line',
    ],

    // Putting the configuration back. A preview is worded in the conditional
    // and never as having happened; only a report the stack says it carried
    // out is worded in the past.
    'reset' => [
        'heading' => 'Putting the configuration back',
        'a_preview' => 'A preview. Nothing has been put back.',
        'put_back' => 'The configuration was put back.',
        'would_revert_files' => 'These files would go back to lemonfiber\'s own. Lines marked - are edits that would be lost; lines marked + are what lemonfiber would write.',
        'reverted_files' => 'These files went back to lemonfiber\'s own. Lines marked - are edits that were lost; lines marked + are what lemonfiber wrote.',
        'would_revert_no_file' => 'No file would go back.',
        'reverted_no_file' => 'No file went back.',
        'differs_in_no_line' => 'It differs from lemonfiber\'s own in no line that can be shown.',
        'differed_in_no_line' => 'It differed from lemonfiber\'s own in no line that can be shown.',
        'would_revert_connections' => 'These connections would go back to lemonfiber\'s own with them:',
        'reverted_connections' => 'These connections went back to lemonfiber\'s own with them:',
        'would_revert_no_connection' => 'No connection would go back.',
        'reverted_no_connection' => 'No connection went back.',
        'would_change_nothing' => 'Nothing would change. No file and no connection differs from lemonfiber\'s own.',
        'changed_nothing' => 'Nothing changed. No file and no connection differed from lemonfiber\'s own.',
        'put_them_back' => 'Put these back',
        'asking' => 'The stack is working out what putting the configuration back would change.',
        'putting_back' => 'The stack is putting the configuration back.',
        'no_preview' => 'The stack no longer says what putting the configuration back would change.',
        'no_outcome' => 'The stack no longer says what became of putting the configuration back. It may have been done. Asking again opens a fresh preview of what still differs.',
        'refused_preview' => 'The stack would not say what putting the configuration back would change:',
        'refused' => 'The stack would not put the configuration back:',
        'see_the_settings' => 'See the settings',
    ],
];
