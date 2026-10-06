<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Wayfinding\Api\AStackToChooseAsShown;

use function sprintf;

/**
 * One house on a member's list of houses: its name, and whether it is the one they are in.
 *
 * Names only. How a house's machine stands is the operator's to read, so the
 * list carries no standing word and no standing glyph; the house a member is
 * in carries the check the operator's list of stacks marks its current one
 * with, and choosing it only closes the list.
 */
final readonly class AHouseToChooseAsShown
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $current,
    ) {}

    /** The glyph Android draws at the start of the house the member is in, and none for the others. */
    public function icon(): string
    {
        return $this->current ? AStackToChooseAsShown::CHECK : '';
    }

    /** The glyph iOS draws at the start of the house the member is in, and none for the others. */
    public function iosIcon(): string
    {
        return $this->current ? AStackToChooseAsShown::IOS_CHECK : '';
    }

    /** What choosing this house does: the house they are in only closes the list. */
    public function pressed(): string
    {
        return $this->current ? 'stayInThisHouse()' : sprintf("openTheHouse('%s')", $this->id);
    }
}
