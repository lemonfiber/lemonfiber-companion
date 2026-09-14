<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * Every screen that is not about one stack, and the one place its path is written.
 *
 * {@see AStacksScreen}'s complement, and between them they are all of them: a
 * screen either names a machine in its path or it does not. These are the ones
 * that do not — the list a device opens on, and the two roads into pairing,
 * which is what happens before there is an identifier to put in a path.
 *
 * Split from `AStacksScreen` rather than folded into it because the two are not
 * the same kind of thing to a caller. A case here *is* a path; a case there is a
 * pattern that still needs a machine before anybody can go to it, which is why
 * only that one carries {@see AStacksScreen::forTheStack()}. Asking this enum
 * for a path and being handed something with `{stack}` still in it would be the
 * defect both enums exist to refuse.
 *
 * The gap this closes is worse here than it was there. A device with nothing
 * paired has exactly these three frames, so a rename that missed the template
 * would leave a new operator on the first screen they ever see, offering two
 * buttons that do nothing, with nowhere else to try.
 */
enum AScreenWithoutAStack: string
{
    /** What the app opens on: the stacks this device has, or the offer to pair one. */
    case TheList = '/';

    /** Reading the code off the stack's own screen with the camera (`N1-R20`). */
    case PairByScanning = '/pair/scanned';

    /** Typing it, for a camera that is refused or absent (`N4-R3`). */
    case PairByTyping = '/pair/typed';
}
