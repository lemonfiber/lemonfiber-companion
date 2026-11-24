<?php

declare(strict_types=1);

return [
    // Two wordings per notification, and which one is used is decided by the
    // type rather than at the call site. The guarded pair is what a
    // locked device may show: no stack, no service, no finding detail.
    'plain' => [
        'onset_title' => ':stack needs attention',
        'onset_body' => 'Open lemonfiber to see what it reported. Reference :code.',
        'resolved_title' => ':stack is back to normal',
        'resolved_body' => 'What it reported has stopped. Reference :code.',
        'more_title' => ':stack has more to tell you',
        'more_body' => 'Open lemonfiber to see everything it reported.',
        'missing_title' => 'Some alerts from :stack may be missing',
        'missing_body' => 'Open it to see how it stands now.',
    ],
    'guarded' => [
        'onset_title' => 'A stack needs attention',
        'onset_body' => 'Unlock to see which one, and what it reported.',
        'resolved_title' => 'A stack is back to normal',
        'resolved_body' => 'Unlock to see which one.',
        'more_title' => 'A stack has more to tell you',
        'more_body' => 'Unlock to see which one.',
        'missing_title' => 'Some alerts from a stack may be missing',
        'missing_body' => 'Unlock to see which one, and how it stands now.',
    ],
    'page' => [
        'title' => 'Notifications',
        'switch_for' => ':stack: :kind',
        'floor_for' => ':stack: problems from :floor',
        'raises_nothing' => 'This stack sends no notifications as it is set up.',
        'not_read' => 'Which notifications this stack sends could not be read: :reason',
        'kept_off' => 'Still off for this stack: :kinds.',
        'kept_floor' => 'Problems are told from :floor.',
        'problems_from' => 'Tell me about problems from',
        'problems_are_off' => 'Problems are off for this stack.',
        'may_be_missing' => 'Missing alerts',
        'may_be_missing_for' => ':stack: missing alerts',
        'turn_on_above' => 'Turn on notifications above to choose.',
        'no_stacks' => 'There are no stacks on this phone yet.',
    ],
    'kind' => [
        'problem' => 'Problems',
        'completion' => 'Work you asked for, finished',
        'update' => 'Updates available',
        'backup' => 'Backups that worked',
        'request' => 'Requests waiting for you',
        'member' => 'Invitations accepted, declined or lapsed',
        'ready' => 'Something a member asked for has arrived',
    ],
    'floor' => [
        'advisory' => 'Advisory',
        'warning' => 'Warning',
        'error' => 'Error',
        'critical' => 'Critical',
    ],
];
