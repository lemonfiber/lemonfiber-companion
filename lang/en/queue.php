<?php

declare(strict_types=1);

return [
    'title' => 'How the queue heals itself',
    'open' => 'How the queue heals itself',
    'rehearsal' => 'Rehearsal: nothing was cleared',
    'strikes_needed' => '{0} It acts on an item as soon as a pass flags it|{1} It acts on an item once a pass has flagged it|[2,*] It acts on an item once :count passes in a row have flagged it for the same reason',
    'classified' => 'The latest pass classified the queue :ago',
    'never_classified' => 'No pass has classified the queue yet',
    'incomplete' => 'Something could not be read, so this list may be short',
    'mismatch' => 'A download client files a curator\'s downloads outside the curator\'s folder, so nothing is acted on past it',
    'wedged' => 'What it would act on',
    'nothing_wedged' => 'Nothing in the queue is unhealthy',
    'held_by' => 'Held by :service',
    'release' => 'Release :release',
    'strikes' => 'Strikes: :strikes of :needed',
    'added' => 'Added :ago',
    'in_grace' => 'Newly added, so it is left alone; that ends :in',
    'next' => 'The next run would, one after the other:',
    'next_none' => 'The next run would not act on it',
    'unreachable' => 'What it could not reach',
    'nothing_unreachable' => 'It reached every curator and download client',
    'runs' => 'Every run, newest first',
    'no_runs' => 'No run has been kept yet',
    'ran' => 'Ran :ago',
    'run_acted_on_nothing' => 'It acted on nothing',
    'act_came_to' => ':act: :outcome',
    'no_acts' => 'No act was taken on it',
    'instead' => 'Instead: :act',
    'healing' => [
        'on' => 'Healing is on: it acts on what strikes out',
        'off' => 'Healing is off: it only classifies, and acts on nothing',
    ],
    'grace' => [
        'minutes' => '{0} A newly added download is left alone for under a minute|{1} A newly added download is left alone for a minute|[2,*] A newly added download is left alone for :count minutes',
        'hours' => '{1} A newly added download is left alone for an hour|[2,*] A newly added download is left alone for :count hours',
        'days' => '{1} A newly added download is left alone for a day|[2,*] A newly added download is left alone for :count days',
    ],
    'class' => [
        'stalled' => 'Stalled: nothing is moving and no source can move it',
        'slow' => 'Slow: it would take longer than allowed',
        'failed' => 'Failed: the client or the curator gave up on it',
        'stuck-on-metadata' => 'Stuck on metadata: the torrent has not resolved what it is',
        'orphaned' => 'Orphaned: in a client under a curator\'s category, and in no curator\'s queue',
    ],
    'act' => [
        'remove' => 'Remove it from its download client',
        'blocklist' => 'Blocklist its release, so it is not grabbed again',
        'search' => 'Search for the title again',
    ],
    'outcome' => [
        'done' => 'done',
        'would' => 'would have been done',
        'refused' => 'not done',
        'fell-back' => 'something weaker was done instead',
    ],
];
