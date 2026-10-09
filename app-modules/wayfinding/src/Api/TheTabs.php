<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use Modules\Kernel\Api\WhichTab;
use Modules\Stacks\Api\AStacksScreen;

use function sprintf;

/**
 * The four tabs of the operator's bottom bar, in the order it draws them.
 *
 * A tab's screen is the root of what the phone draws for it, so it has no
 * back button; every other screen about a stack is opened on top of one.
 * Choosing a stack opens it on the tab the operator last used there.
 */
enum TheTabs: string
{
    case Health = WhichTab::Health->value;
    case Services = WhichTab::Services->value;
    case Updates = WhichTab::Updates->value;
    case Repairs = WhichTab::Repairs->value;

    /** The tab the phone kept a word for. */
    public static function kept(WhichTab $tab): self
    {
        return self::from($tab->value);
    }

    /** The word the phone keeps for this tab. */
    public function word(): WhichTab
    {
        return WhichTab::from($this->value);
    }

    /** The screen the tab opens. */
    public function screen(): AStacksScreen
    {
        return match ($this) {
            self::Health => AStacksScreen::Health,
            self::Services => AStacksScreen::Services,
            self::Updates => AStacksScreen::Updates,
            self::Repairs => AStacksScreen::Repairs,
        };
    }

    /** The catalogue key of the tab's label. */
    public function said(): string
    {
        return sprintf('navigation.%s', $this->value);
    }

    /** The Material icon Android draws above the label. */
    public function glyph(): string
    {
        return match ($this) {
            self::Health => 'home',
            self::Services => 'apps',
            self::Updates => 'download',
            self::Repairs => 'build',
        };
    }

    /** The SF Symbol iOS draws above the label. */
    public function iosGlyph(): string
    {
        return match ($this) {
            self::Health => 'house',
            self::Services => 'square.grid.2x2',
            self::Updates => 'arrow.down.circle',
            self::Repairs => 'wrench.and.screwdriver',
        };
    }
}
