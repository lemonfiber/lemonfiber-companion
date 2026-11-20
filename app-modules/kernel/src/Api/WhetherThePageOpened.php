<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether a page outside the app opened in the phone's browser.
 */
final readonly class WhetherThePageOpened
{
    private function __construct(private bool $opened) {}

    /** The phone opened the page. */
    public static function opened(): self
    {
        return new self(opened: true);
    }

    /** The phone would not open it. */
    public static function wouldNot(): self
    {
        return new self(opened: false);
    }

    /** Whether the screen has to say the page did not open. */
    public function wouldNotOpen(): bool
    {
        return ! $this->opened;
    }

    /**
     * @template T of object
     *
     * @param Closure(): T $opened
     * @param Closure(): T $wouldNot
     *
     * @return T
     */
    public function either(Closure $opened, Closure $wouldNot): object
    {
        return $this->opened ? $opened() : $wouldNot();
    }
}
