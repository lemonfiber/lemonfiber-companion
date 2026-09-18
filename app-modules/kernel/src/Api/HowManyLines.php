<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How much of a service's scrollback to ask for.
 *
 * A log read is bounded, and a bound is only a bound if something
 * holds it. The SDK refuses a window of no lines and names a ceiling of its own
 * in the refusal; this is the app's side of the same sentence — the number said
 * once, where a screen can read it back and put it on the page.
 *
 * **The figure is not a default hidden in a port.** How many lines is a decision
 * about somebody's phone screen, and a port carrying a default is a port making
 * that decision for every screen that ever calls it. The value is named at the
 * call site or the call does not compile, which is the arrangement the SDK's own
 * `Logs` insists on and the reason this type is a parameter rather than a
 * constant.
 *
 * **There is no upper bound here.** The stack holds one and names it when it
 * refuses, and copying the figure into this app would be a second copy that
 * goes stale with nothing to notice — the failure {@see Size} avoids by
 * carrying whether anybody measured, applied to a limit instead of a figure.
 */
final readonly class HowManyLines
{
    /** The fewest a window can be cut to and still be a window. */
    public const int AT_LEAST = 1;

    /**
     * What a phone asks for when nothing else decided.
     *
     * A screen's decision, made once and here rather than in each screen: two
     * screens showing different amounts of the same scrollback would have an
     * operator believe one of them was hiding something. Two hundred is about
     * what a thumb scrolls through before giving up and opening a terminal,
     * which is the honest ceiling on how much of this anybody reads.
     */
    public const int ON_A_PHONE = 200;

    private function __construct(private int $lines) {}

    /** A window of this many lines. */
    public static function of(int $lines): self
    {
        if ($lines < self::AT_LEAST) {
            throw WindowHoldsNoLines::of($lines);
        }

        return new self($lines);
    }

    /** As much as a phone screen is worth asking for. */
    public static function asMuchAsAPhoneShows(): self
    {
        return new self(self::ON_A_PHONE);
    }

    /** The figure, for asking with and for saying on the screen. */
    public function figure(): int
    {
        return $this->lines;
    }
}
