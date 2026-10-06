<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use function sprintf;

/**
 * One stack in the list the top bar's name opens, flattened for a template.
 *
 * The current stack is marked by a check where the others carry the glyph of
 * how they stand, and pressing it only closes the list: it is where the
 * operator already is.
 */
final readonly class AStackToChooseAsShown
{
    /** The glyph Android marks the current stack with, on either surface's list. */
    public const string CHECK = 'check_circle';

    /** The glyph iOS marks the current stack with, on either surface's list. */
    public const string IOS_CHECK = 'checkmark.circle.fill';

    /**
     * @param string $id      what this phone calls the stack, which choosing it hands back
     * @param string $name    the name its owner gave it
     * @param string $word    the catalogue key for how it stands, in a word
     * @param string $tone    the tone of that word, which picks the glyph of a stack that is not the current one
     * @param bool   $current whether it is the stack the screen underneath is about
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $word,
        private string $tone,
        public bool $current,
    ) {}

    /** The tone whose glyph the row carries, or none where the check marks it. */
    public function tone(): string
    {
        return $this->current ? '' : $this->tone;
    }

    public function icon(): string
    {
        return $this->current ? self::CHECK : '';
    }

    public function iosIcon(): string
    {
        return $this->current ? self::IOS_CHECK : '';
    }

    /** What pressing the row calls on the screen. */
    public function pressed(): string
    {
        return $this->current ? 'stopChoosingAStack()' : sprintf("openTheStack('%s')", $this->id);
    }
}
