<?php

declare(strict_types=1);

return [
    'title' => 'Scheduled runs',
    'between_runs' => 'These run at the times their settings name, then stop until the next. Not running between runs is how they should be.',
    'nothing' => 'Nothing on this stack runs on a schedule',
    'ran' => 'Last ran :ago',
    'running' => 'A run is going now',
    'not_fired' => 'This machine\'s scheduler does not hold its job, so its times are not fired',
    'skipped' => '{1} One time fell due while a run was still going, and was skipped|[2,*] :count times fell due while a run was still going, and were skipped',
    'log' => 'Its last lines',
    'no_log' => 'It wrote no lines',
    'health' => [
        'not-run' => 'Not run yet',
        'succeeded' => 'Its last run succeeded',
        'failed' => 'Its last run failed',
    ],
    'exit' => [
        'code' => 'It exited with code :code',
        'none' => 'It ended without an exit code',
    ],
    'took' => [
        'minutes' => '{0} It took under a minute|{1} It took a minute|[2,*] It took :count minutes',
        'hours' => '{1} It took an hour|[2,*] It took :count hours',
        'days' => '{1} It took a day|[2,*] It took :count days',
    ],
];
