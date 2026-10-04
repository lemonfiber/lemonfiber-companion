<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Internal;

use Modules\News\Api\HowMuchIsNew;

use function sprintf;

/**
 * The item every operator's menu opens with after the way to another stack: what is new.
 *
 * It opens What's new on this stack, which the operator can widen to every
 * stack, and counts how much is new here: digits drawn between its label and
 * its chevron, as a tab's badge is, and said with its label to a screen
 * reader. Where nothing is new it carries no count.
 */
final readonly class TheWhatsNewInTheMenu
{
    public function __construct(private HowMuchIsNew $new) {}

    /** The catalogue key of the item's label, which is also the screen's title. */
    public function said(): string
    {
        return 'navigation.menu.whats_new';
    }

    /** The Material icon Android draws beside the label. */
    public function glyph(): string
    {
        return 'new_releases';
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return 'newspaper';
    }

    /** How many new items there are on this stack, of every kind. */
    public function count(): int
    {
        return $this->new->howManyInAll();
    }

    /** The count as the digits the row draws, or nothing where nothing is new. */
    public function badge(): string
    {
        return $this->count() > 0 ? sprintf('%d', $this->count()) : '';
    }
}
