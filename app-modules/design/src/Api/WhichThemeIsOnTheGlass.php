<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * Whose theme the screen being drawn is painted in.
 *
 * Classes reach the theme through the parser's resolver, and an element handed
 * a colour as a value, such as an icon, reaches it through this, so an icon
 * and the words beside it are painted from the same theme. The composition
 * root answers it with the theme it painted for the screen on view.
 */
interface WhichThemeIsOnTheGlass
{
    public function whose(): WhoseTheme;
}
