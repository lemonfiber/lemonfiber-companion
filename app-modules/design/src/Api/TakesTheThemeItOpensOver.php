<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * A screen about no stack that is opened from inside one, and drawn in the theme of the screen it opens over.
 *
 * App settings and What's new belong to nobody's session, yet an operator
 * reaches them from the operator's own screens; switching to the member's theme
 * for the length of a settings page would be the app changing its face under
 * them. Opened with nothing beneath it, such a screen is drawn as any screen
 * for no session is, in the member's theme.
 */
interface TakesTheThemeItOpensOver {}
