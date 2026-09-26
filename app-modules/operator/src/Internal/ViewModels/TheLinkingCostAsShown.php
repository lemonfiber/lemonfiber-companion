<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a layout that cannot hold a hardlink costs, flattened for a template.
 *
 * Every field is empty where the layout links. The remedy is words: nothing
 * the stack offers carries it out, so the template draws it with no control.
 */
final readonly class TheLinkingCostAsShown
{
    /**
     * @param string $because     why it cannot link, in the stack's words, or empty
     * @param string $cost        what that costs in room, or empty
     * @param string $remedy      what would fix it, or empty
     * @param string $filesystems the filesystems it is about, as one line, or empty
     */
    public function __construct(
        public string $because,
        public string $cost,
        public string $remedy,
        public string $filesystems,
    ) {}

    /** A layout that links, which costs nothing. */
    public static function nothing(): self
    {
        return new self(because: '', cost: '', remedy: '', filesystems: '');
    }
}
