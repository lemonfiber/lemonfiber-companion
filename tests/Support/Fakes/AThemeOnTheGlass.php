<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\Api\WhoseTheme;

/** Whose theme is on the glass, as the test says, with nothing painted. */
final readonly class AThemeOnTheGlass implements WhichThemeIsOnTheGlass
{
    private function __construct(private WhoseTheme $theme) {}

    /** The glass before any screen is drawn, which shows the member's theme. */
    public static function beforeAnyScreen(): self
    {
        return new self(WhoseTheme::Member);
    }

    /** The glass with one theme on it. */
    public static function showing(WhoseTheme $theme): self
    {
        return new self($theme);
    }

    public function whose(): WhoseTheme
    {
        return $this->theme;
    }
}
