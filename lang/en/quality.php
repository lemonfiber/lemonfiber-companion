<?php

declare(strict_types=1);

return [
    'road_in' => 'How good the media should be',

    // What became of the choice. A rehearsal and a held choice each have a
    // sentence of their own, and neither says the choice was recorded.
    'became' => [
        'shown' => 'What is chosen now',
        'recorded' => 'Recorded: this is what is chosen now',
        'rehearsed' => 'A rehearsal: nothing has been recorded',
        'held' => 'Held, not recorded: this machine would have to transcode it in software',
        'reapplied' => 'The preset was put back over the configuration you edited',
        'would-reapply' => 'A rehearsal: putting the preset back would overwrite the configuration you edited, and nothing has been written',
    ],
    'held_unexplained' => 'The stack held it without naming a preset that would transcode here.',
    'confirm' => 'Choose it anyway',

    'customised' => 'The quality configuration was edited by hand. It is left as you set it, and the preset is not in charge of it.',
    'not_put_back' => 'Putting the preset back over your edits is not offered here.',

    'for' => 'For :scope',
    'per_hour' => 'Roughly :size for an hour',
    'transcodes_here' => 'This machine would have to transcode this in software',
    'no_presets' => 'The stack reports no preset in force.',

    // Music has no resolution, and is said to be chosen by its format.
    'music' => [
        'heading' => 'Music, by format rather than resolution',
        'targets' => 'Aims for :targets',
        'unset' => 'No format is chosen for music.',
    ],

    'choose' => [
        'heading' => 'Choose a preset',
        'preset' => 'Preset, or a format for music',
        'preset_help' => 'In the stack\'s words, as it names them above',
        'kind' => 'Kind of media',
        'kind_help' => 'Leave empty to choose for everything',
        'act' => 'Choose',
    ],

    'upgrade' => [
        'heading' => 'Upgrading what is already here',
        'apart' => 'Offered on its own, and described kind by kind before anything is fetched.',
        'describe' => 'What would upgrading come to?',
        'described' => 'What upgrading would come to. Nothing has been fetched.',
        'carried_out' => 'Upgrading: each service was asked to search again',
        'kind' => ':kind, at :preset',
        'nothing' => 'The stack names nothing to upgrade.',
        'agree' => 'Upgrade what is already here',
    ],

    // What became of asking a service, whether to search again or to take a
    // format for music.
    'asked' => [
        'not-asked' => 'Nothing has been asked of its service',
        'started' => 'Its service accepted, and is at it now',
        'not-started' => 'Its service had not finished starting, so nothing was asked of it',
        'failed' => 'Its service refused, or could not be reached',
    ],
];
