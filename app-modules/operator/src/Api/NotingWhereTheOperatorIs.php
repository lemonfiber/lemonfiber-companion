<?php

declare(strict_types=1);

namespace Modules\Operator\Api;

use InvalidArgumentException;

use function is_string;

use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\Operator\Internal\WhereTheTabsAreDrawn;
use Modules\Wayfinding\Api\TheTabs;
use Native\Mobile\Edge\NativeComponent;

/**
 * Notes the stack and tab the operator is on, as each screen comes to the front.
 *
 * Only a screen under one of the four tabs is noted, so a screen the menu opens
 * leaves the tab the operator last used where it was, and the lock, which is
 * under no tab and about no stack, notes nothing.
 */
final readonly class NotingWhereTheOperatorIs
{
    public function __construct(private WhereTheOperatorWas $was) {}

    /** Note where this screen is, where it is under a tab of a stack; say whether anything was kept. */
    public function cameToTheFront(NativeComponent $screen): bool
    {
        $tab = WhereTheTabsAreDrawn::owning($screen::class);
        $stack = $screen->param('stack');

        if (! $tab instanceof TheTabs || ! is_string($stack)) {
            return false;
        }

        try {
            return $this->was->wasOn(StackId::rememberedAs($stack), $tab->word());
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
