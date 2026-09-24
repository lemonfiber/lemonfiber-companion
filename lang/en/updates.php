<?php

declare(strict_types=1);

return [
    // Where the services stand against the versions the stack's build pins.
    // Only the first two answer a reading; the other three describe a run
    // that was agreed to.
    'pins' => [
        'current' => 'Up to date',
        'updates-available' => 'An update is waiting',
        'updated' => 'Updated',
        'partial' => 'Part of the update went through',
        'failed' => 'The update did not go through',
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
    'about_to_take' => 'About to update the services',
    'would_change' => '{1} One service will stop and start again|[2,*] :count services will stop and start again',
    'changes_nothing' => 'This release changes no service on this machine.',

    // Said before the yes rather than after it, and against the services it is
    // true of. Undoing the update puts the rest back and not these, so an
    // operator reading this is deciding something different from the rest of
    // the evening.
    'cannot_be_put_back' => '{1} One of these cannot be put back|[2,*] :count of these cannot be put back',
    'cannot_be_put_back_after' => 'Undoing the update afterwards will not reverse this.',
    'take_it' => 'Update the services',
    'last_update' => 'The last update',
    'did_not_arrive' => '{1} One service is not where you wanted it|[2,*] :count services are not where you wanted it',
    'unanswered' => 'Some services started and have not answered, so the stack cannot say what they are doing.',
    'undo_carries_data' => 'Undoing this puts the data back as well',
    'nothing_applied' => 'No update has been taken on this machine yet.',
    'running_on' => 'Running :version',
    'running_withdrawn' => 'This version has been withdrawn',
    'what_it_changed' => 'What :version changed',
    'history' => 'Release history',
    'withdrawn' => 'Withdrawn',
    'no_history' => 'The stack listed no releases.',
    // Where a release carries no prose. Said rather than left blank: an empty
    // line reads as a screen that failed to load something, and this is a
    // release the stack has nothing to say about.
    'delivers_unsaid' => 'The stack did not say what this one changes',

    'would_be_noticed' => 'The household will see the difference',
    'would_not_be_noticed' => 'Nobody will notice this one',
];
