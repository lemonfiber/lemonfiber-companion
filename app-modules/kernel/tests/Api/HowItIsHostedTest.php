<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\HowItIsHosted;

use function sprintf;

/**
 * The three questions a screen asks of one standing, folded to a word.
 *
 * Named for this file (`G10`). Asking them one at a time and comparing booleans
 * would let a case answer *yes* to two of them and still pass, because nothing
 * in three separate assertions says they are exclusive. Folded, a case that
 * claimed two comes out as neither word and the expectation names it.
 */
function whatAScreenWouldDrawFor(HowItIsHosted $standing): string
{
    $said = [];

    if ($standing->comesBackOnItsOwn()) {
        $said[] = 'comes back';
    }

    if ($standing->didNotComeBack()) {
        $said[] = 'did not come back';
    }

    if ($standing->cannotBePromisedHere()) {
        $said[] = 'not available here';
    }

    return $said === [] ? 'says something else' : implode(' and ', $said);
}

it('N16-R5 — only a confirmed one comes back on its own', function (): void {
    // `InstalledUnverified` is installed and unconfirmed, which is the answer
    // that looks most like this one and is not it. A screen treating *the
    // manager would not say* as *yes* is the silence this area exists to refuse.
    expect(whatAScreenWouldDrawFor(HowItIsHosted::Hosted))->toBe('comes back')
        ->and(whatAScreenWouldDrawFor(HowItIsHosted::InstalledUnverified))->toBe('says something else');
});

it('N16-R5 — a platform with no manager says so rather than reading as off', function (): void {
    // *Off* invites switching it on, and there is nothing here to switch. The
    // sentence that follows this is the contract's `instruction` — what to do
    // instead — rather than a control nobody can use.
    expect(whatAScreenWouldDrawFor(HowItIsHosted::Unsupported))->toBe('not available here')
        ->and(whatAScreenWouldDrawFor(HowItIsHosted::Stopped))->toBe('did not come back');
});

it('N16-R6 — an orphan is named among what did not come back', function (): void {
    // It cannot run: the definition is installed and names a program that is
    // not there any more. A screen listing only `Stopped` would show an
    // operator a shorter list than the truth.
    expect(whatAScreenWouldDrawFor(HowItIsHosted::Orphaned))->toBe('did not come back');
});

it('N16-R6 — a command nothing installed did not fail to come back', function (): void {
    // Nothing was installed, so nothing failed to start. Reporting it beside a
    // service that died would be inventing a failure out of a command that only
    // ever ran while a terminal held it.
    expect(whatAScreenWouldDrawFor(HowItIsHosted::NotHosted))->toBe('says something else');
});

it('N16-R5 — no standing answers two of the three questions at once', function (): void {
    // The fold above would say so by naming both, and this is what makes that
    // true of every case rather than of the four a test happened to name.
    foreach (HowItIsHosted::cases() as $standing) {
        expect(whatAScreenWouldDrawFor($standing))
            ->not->toContain(' and ', $standing->name);
    }
});

it('N16-R13 — two of the six are neither running nor stopped, and a screen has to say so', function (): void {
    // The register this file is really about. `NotHosted` and
    // `InstalledUnverified` fall through every question deliberately, so a
    // screen cannot draw them from a boolean and has to have a sentence for
    // each. A future case quietly joining them fails here.
    $neither = [];

    foreach (HowItIsHosted::cases() as $standing) {
        if (whatAScreenWouldDrawFor($standing) === 'says something else') {
            $neither[] = $standing->value;
        }
    }

    expect($neither)->toBe(['not-hosted', 'installed-unverified']);
});

it('L7 — every standing names a line, built from the case', function (): void {
    foreach (HowItIsHosted::cases() as $standing) {
        expect($standing->saidOnTheScreen())
            ->toBe(sprintf('stacks.hosting.%s', $standing->value), $standing->name);
    }
});
