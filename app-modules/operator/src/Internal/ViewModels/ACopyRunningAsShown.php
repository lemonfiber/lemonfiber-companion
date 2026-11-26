<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The backup or restore running on a stack, flattened for a template: what the stack's reading says, or that there is none to go by.
 *
 * Every field past `$said` is filled only while a copy runs.
 */
final readonly class ACopyRunningAsShown
{
    /**
     * @param string $said    the catalogue key for what is said first: a backup or a restore running and of what, nothing running, or that the app cannot tell
     * @param string $tone    the tone that is said in
     * @param string $part    the area being written now, as the stack named it
     * @param string $moves   the catalogue key for whether it is writing, paced or stalled
     * @param int    $count   the count that key is said with: how long nothing has moved, in its unit
     * @param string $started the catalogue key for how long ago it started
     * @param int    $since   the count that key is said with, in its unit
     * @param float  $done    how much of the whole is written, from 0 to 1
     * @param ?int   $percent the same in whole percent, or nothing where the whole is nothing
     */
    public function __construct(
        public string $said,
        public string $tone,
        public ?AScopeAsShown $scope = null,
        public ?ASizeAsShown $written = null,
        public ?ASizeAsShown $whole = null,
        public string $part = '',
        public string $moves = '',
        public ?ASizeAsShown $limit = null,
        public int $count = 0,
        public string $started = '',
        public int $since = 0,
        public float $done = 0.0,
        public ?int $percent = null,
    ) {}
}
