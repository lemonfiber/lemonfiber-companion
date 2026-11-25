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
     * @param string $said  the catalogue key for what is said first: a backup or a restore running, nothing running, or that the app cannot tell
     * @param string $tone  the tone that is said in
     * @param string $part  the area being written now, as the stack named it
     * @param string $moves the catalogue key for whether it is writing, paced or stalled
     * @param int    $count the count that key is said with: how long nothing has moved, in its unit
     */
    public function __construct(
        public string $said,
        public string $tone,
        public ?ASizeAsShown $written = null,
        public ?ASizeAsShown $whole = null,
        public string $part = '',
        public string $moves = '',
        public ?ASizeAsShown $limit = null,
        public int $count = 0,
    ) {}
}
