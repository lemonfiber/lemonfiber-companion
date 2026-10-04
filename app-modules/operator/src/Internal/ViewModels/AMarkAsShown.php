<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The mark one tab carries, as the bar draws it.
 *
 * The count is what the sentence a screen reader says is chosen by; the badge
 * is the same count written out, because a badge is text on each platform and
 * a number handed to it draws nothing. Both are empty for a tab with nothing
 * new, which carries no mark.
 */
final readonly class AMarkAsShown
{
    /**
     * @param int    $count how many new items the tab holds
     * @param string $badge that count, as the text the badge draws, or empty for none
     */
    public function __construct(
        public int $count = 0,
        public string $badge = '',
    ) {}
}
