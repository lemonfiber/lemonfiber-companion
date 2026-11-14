<?php

declare(strict_types=1);

return [
    'where_alerts_go' => 'Where alerts go',
    'where_alerts_go_for' => 'Where :stack\'s alerts go',
    'none_readable' => 'No back-end could be read.',
    'all_read' => 'Every back-end was read.',
    'title' => 'Where alerts go',
    'state' => [
        'unconfigured' => 'Not set up',
        'ready' => 'Delivering',
        'degraded' => 'Some back-ends are failing',
        'unreachable' => 'Nothing can be delivered',
    ],
    'kind' => [
        'ntfy' => 'ntfy',
        'gotify' => 'Gotify',
        'webhook' => 'Webhook',
        'apprise' => 'Apprise',
        'unifiedpush' => 'UnifiedPush',
    ],
    'chosen' => [
        'you' => 'Chosen by you',
        'member' => 'Chosen by :member',
    ],
    'hosted_at' => 'Hosted at :host. Sent to use it:',
    'proof' => [
        'unproven' => 'Never delivered',
        'proven' => 'Last proven :ago',
        'held' => 'Held: :why',
    ],
    'ended' => [
        'none' => 'No delivery yet',
        'confirmed' => 'Confirmed :ago',
        'sent' => 'Sent, not confirmed, :ago',
        'failed' => 'Failed :ago: :reason',
    ],
    'failed_since' => '{1} Failed once since the last confirmed delivery|[2,*] Failed :count times since the last confirmed delivery',
    'retry' => 'Next attempt :in, attempt :attempt of :of',
    'open' => 'Everything about :where',
    'unread' => 'Could not be read',
    'failures' => 'Failed since the last confirmed delivery',
    'no_failures' => 'Nothing has failed since the last confirmed delivery.',
    'no_longer_listed' => 'This machine no longer lists this back-end.',
    'set_up_at_the_machine' => 'Back-ends are set up at the machine, in its web console or at its terminal.',
    'changed_at_the_machine' => 'Back-ends and their credentials are changed at the machine, never from this phone.',
    'hosted' => [
        'heading' => 'Sent by services you chose',
        'none' => 'No back-end your alerts go to is hosted elsewhere.',
        'sends_nothing' => 'Nothing is listed as sent to use it.',
        'not_read' => 'Where alerts go could not be read: :reason',
    ],
];
