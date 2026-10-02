<?php

declare(strict_types=1);

namespace Modules\News\Api;

use Modules\Kernel\Api\StackId;
use Modules\News\Internal\NewsOfAStack;

/**
 * Which kinds a stack marks as new, as the operator chose in its settings.
 *
 * All three until the operator says otherwise. A kind switched off forgets the
 * newest of it that was seen, so its marks go at once and, switched on again,
 * it starts from what is current rather than from everything since.
 */
final readonly class MarkingAsNew
{
    public function __construct(private NewsOfAStack $news) {}

    /** Every kind the stack marks as new, in the order the kinds are declared. */
    public function marked(StackId $stack): TheKindsMarked
    {
        $kept = $this->news->of($stack);
        $marked = [];

        foreach (KindOfNews::cases() as $kind) {
            if ($kept->isMarked($kind)) {
                $marked[] = $kind;
            }
        }

        return TheKindsMarked::these(...$marked);
    }

    /** Whether the stack marks this kind as new. */
    public function isMarked(StackId $stack, KindOfNews $kind): bool
    {
        return $this->news->of($stack)->isMarked($kind);
    }

    /** Mark this kind as new on the stack, starting from what is current; whether the choice was kept. */
    public function mark(StackId $stack, KindOfNews $kind): bool
    {
        return $this->news->keep($stack, $this->news->of($stack)->marking($kind));
    }

    /** Stop marking this kind as new on the stack, and forget the newest of it seen; whether the choice was kept. */
    public function markNoLonger(StackId $stack, KindOfNews $kind): bool
    {
        return $this->news->keep($stack, $this->news->of($stack)->notMarking($kind));
    }
}
