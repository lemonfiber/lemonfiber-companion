<?php

declare(strict_types=1);

return [
    'how' => [
        'current' => 'Up to date',
        'pending' => 'An update is waiting',
        'stale' => 'Not checked recently',
    ],
    'ended' => [
        'updated' => 'Updated',
        'not-fetched' => 'The download never arrived',
        'not-started' => 'It would not start on the new version',
        'not-reached' => 'Started, but it has not answered',
    ],
    'undo' => [
        'rollback' => 'Go back to the previous version',
        'restore' => 'Restore the snapshot taken first',
    ],
    'about_to_take' => 'About to take :version',
    'would_change' => '{1} One service will stop and start again|[2,*] :count services will stop and start again',
    'changes_nothing' => 'This release changes no service on this machine.',

    // Said before the yes rather than after it, and against the services it is
    // true of. Undoing the update puts the rest back and not these, so an
    // operator reading this is deciding something different from the rest of
    // the evening.
    'cannot_be_put_back' => '{1} One of these cannot be put back|[2,*] :count of these cannot be put back',
    'cannot_be_put_back_after' => 'Undoing the update afterwards will not reverse this.',
    'something_worth_noticing' => 'One of these is a change the household will see',
    'take_this_one' => 'Take this one',
    'take_that_one' => 'Take :version',
    'last_update' => 'The last update',
    'did_not_arrive' => '{1} One service is not where you wanted it|[2,*] :count services are not where you wanted it',
    'unanswered' => 'Some services started and have not answered, so the stack cannot say what they are doing.',
    'undo_carries_data' => 'Undoing this puts the data back as well',
    'nothing_applied' => 'No update has been taken on this machine yet.',
    'running_on' => 'Running :version',
    'running_withdrawn' => 'This version has been withdrawn',
    'would_be_noticed' => 'The household will see the difference',
    'would_not_be_noticed' => 'Nobody will notice this one',
    'nothing_waiting' => 'There is nothing waiting.',
];
