<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a screen has to put on the frame: something, or nothing yet.
 *
 * An indeterminate progress indicator is allowed **only** where the app
 * holds nothing to show, and forbids it replacing a retained reading that could
 * be shown with its age. {@see Reading} already keeps a retained value from
 * passing as a live one; this keeps a spinner from covering either.
 *
 * **The two rules fail in opposite directions and the second is the common
 * one.** A screen that has a number from yesterday and paints a spinner over it
 * while refreshing is not being cautious — it is throwing away the only thing it
 * had, and the operator watches an empty screen for as long as a stack takes to
 * answer. `ADR-0019` has the frame published before the read is issued, which
 * means the value is there at paint time: the spinner is a decision, not a
 * consequence.
 *
 * So `waiting()` is the *only* thing that renders one, and a caller reaches a
 * `Reading` by saying what happens in both cases.
 *
 *     $showing->either(
 *         waiting: fn (): Screen => $this->spinner(),
 *         holding: fn (Reading $reading): Screen => $this->paint($reading),
 *     );
 *
 * `waiting()` means the app holds nothing at all — a first read of a screen
 * never opened before. It does not mean a read is in flight: a read is almost
 * always in flight over something already held, and that case is `holding`.
 */
final readonly class Showing
{
    private function __construct(private ?Reading $reading) {}

    /**
     * The app holds nothing for this screen, which is the one case that
     * lets a progress indicator answer.
     */
    public static function waiting(): self
    {
        return new self(null);
    }

    /** The app holds something, live or retained, and it is shown. */
    public static function holding(Reading $reading): self
    {
        return new self($reading);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * There is no `isWaiting()` and no `reading()`, for the reason `Reading`
     * itself gives: a check-then-get pair puts the check where it can be
     * forgotten, and the forgotten one here is a spinner painted over a value.
     *
     * @template TWaiting of object
     * @template THolding of object
     *
     * @param Closure(): TWaiting        $waiting
     * @param Closure(Reading): THolding $holding
     *
     * @return TWaiting|THolding
     */
    public function either(Closure $waiting, Closure $holding): object
    {
        return $this->reading instanceof Reading
            ? $holding($this->reading)
            : $waiting();
    }
}
