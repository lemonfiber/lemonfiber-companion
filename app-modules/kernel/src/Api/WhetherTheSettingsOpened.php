<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether this app's page in the phone's settings was opened when asked for.
 *
 * Two arms and no third, `Handed`'s shape: a page that opened is the
 * operator's from there, and one that did not is something the screen says,
 * so a button that did nothing does not look like a broken one.
 */
final readonly class WhetherTheSettingsOpened
{
    private function __construct(private bool $opened) {}

    /** The page was asked for, and is the operator's from here. */
    public static function opened(): self
    {
        return new self(opened: true);
    }

    /** The phone would not open it. */
    public static function wouldNot(): self
    {
        return new self(opened: false);
    }

    /** Whether the phone would not open the page, which is the arm a screen says something about. */
    public function wouldNotOpen(): bool
    {
        return ! $this->opened;
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TOpened of object
     * @template TWouldNot of object
     *
     * @param Closure(): TOpened   $opened
     * @param Closure(): TWouldNot $wouldNot
     *
     * @return TOpened|TWouldNot
     */
    public function either(Closure $opened, Closure $wouldNot): object
    {
        return $this->opened ? $opened() : $wouldNot();
    }
}
