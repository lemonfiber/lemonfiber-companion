<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Operator\Internal\Presenters\HowAListingReads;

/**
 * A device with no session is not a stack running nothing.
 *
 * The two read alike on a screen and are not alike at all: one is *we could not
 * ask*, and the other is *we asked and the machine is running nothing*. A fold
 * that carried half a reading into the first would be the screen saying the
 * second, quietly, with fields left over from whatever it had before.
 *
 * So every field is asserted rather than the one the template branches on. A
 * value nothing reads is a value nothing can tell from any other, and the day a
 * screen starts drawing the verdict on the signed-out frame is the day this
 * says whether there was one.
 */
it('N1-R44 — a session that has ended carries no reading at all', function (): void {
    $signedOut = new HowAListingReads()->signedOut();

    expect($signedOut->went->isSignedIn)->toBeFalse()
        ->and($signedOut->went->cameBack())->toBeFalse()
        ->and($signedOut->services)->toBe([])
        ->and($signedOut->forms)->toBe([])
        ->and($signedOut->overall)->toBe('')
        ->and($signedOut->isSettling)->toBeFalse()
        ->and($signedOut->disturbs)->toBeNull();
});
