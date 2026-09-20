<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Illuminate\Http\RedirectResponse;

use function redirect;

/**
 * What a route answers once the screen it ran has been left.
 *
 * The one decision entering the runloop involves, and it is here rather than
 * there because {@see TheRunloop} is the single file in this application a
 * suite cannot run: it blocks against the real bridge, so a branch written
 * inside it is a branch nothing ever takes both ways. These two arms swapped
 * would answer a blank page where the operator navigated somewhere and send
 * them away where they simply left the screen — which a test can notice here
 * and could not notice there.
 *
 * NativePHP's loop answers a URI where the operator navigated off every native
 * screen — a link out to a web route — and null where the stack emptied on its
 * own. A route closure has to answer something either way, so the translation
 * is what this is, on the side of the {@see Runloop} seam a test can drive.
 */
final readonly class WhereAScreenLeavesYou
{
    /**
     * What a route answers where the screen was simply left.
     *
     * Empty rather than a status or a view: the stack emptied, so there is no
     * screen left to be on and nothing to draw. What a device is showing at
     * this point was painted by the native renderer and never came through
     * the route at all.
     */
    private const string NOTHING_TO_GO_TO = '';

    public static function after(?string $exit): RedirectResponse|string
    {
        return $exit === null ? self::NOTHING_TO_GO_TO : redirect($exit);
    }
}
