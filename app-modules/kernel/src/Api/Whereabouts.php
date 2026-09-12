<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The screen the operator is on.
 *
 * This exists for one clause of `N1-R44` — "MUST restore that screen once one
 * is established" — and it exists as a type because that clause is the one a
 * screen loses by accident. The interruption arrives, the app has a perfectly
 * good error to show, and where the operator *was* is a local variable in a
 * frame that has already gone. Nothing fails; the operator signs in again and
 * arrives at the start.
 *
 * So {@see Interrupted} cannot be constructed without one, and the only way to
 * resolve one hands the same value back. Carrying it is not something a screen
 * remembers to do — it is the shape of the type it is already holding.
 *
 * **Opaque on purpose.** The kernel does not know this application's screens
 * and must not learn them: a closed set here would have to be edited by anybody
 * adding a screen, in a module that has no reason to care, and the edit that
 * gets forgotten produces exactly the bounce-to-login this prevents. What is
 * checked is the one property the kernel can check — that it is not blank.
 */
final readonly class Whereabouts
{
    private function __construct(private string $screen) {}

    /**
     * Where the operator is now.
     *
     * The name is the application's to choose and the kernel never reads it for
     * meaning; it is compared and handed back, and that is all.
     */
    public static function onTheScreen(string $screen): self
    {
        $named = trim($screen);

        if ($named === '') {
            throw WhereaboutsIsNowhere::named();
        }

        return new self($named);
    }

    /** Which screen to return to. Read by the app, never shown to anybody. */
    public function screen(): string
    {
        return $this->screen;
    }

    /** Whether this is the same place. */
    public function is(self $other): bool
    {
        return $this->screen === $other->screen;
    }
}
