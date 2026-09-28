<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One row of what stopped moving, flattened for a template to read.
 *
 * The name and what blocked it are the stack's words and the service's, drawn
 * as they came; the kind and how long are keys, because those are this app's
 * sentences about a word the contract carries.
 */
final readonly class AStoppageAsShown
{
    /**
     * @param string $kindSaid  the key for which kind of stopped this is
     * @param string $name      the item, or the cause several items share
     * @param int    $items     how many items the row stands for
     * @param string $blocking  what the service said was in the way, or empty where it said nothing
     * @param string $heldSaid  the key for how long it has been this way
     * @param int    $heldCount how many of that key's unit
     * @param string $follows   what a trace follows it by, or empty where the row stands for several
     */
    public function __construct(
        public string $kindSaid,
        public string $name,
        public int $items,
        public string $blocking,
        public string $heldSaid,
        public int $heldCount,
        public string $follows,
    ) {}
}
