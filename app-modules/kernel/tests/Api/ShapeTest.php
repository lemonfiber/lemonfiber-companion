<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Shape;

it('N1-R32 — names the shape this build writes', function (): void {
    expect(Shape::current())->toBe(Shape::One);
});

it('is one shape per layout, numbered from one with no gaps', function (): void {
    // A gap or a duplicate is invisible reading the enum — PHP is perfectly
    // happy with `case Two = 3;` — and it breaks the only thing the numbers are
    // for. The comparison that will rely on it is not written yet, which makes
    // this the moment the ordering is cheapest to get right: nothing depends on
    // it, so nothing has to be unpicked.
    foreach (Shape::cases() as $at => $shape) {
        expect($shape->value)->toBe($at + 1);
    }
});

it('N1-R33 — is a marker today and a decision the day a second shape exists', function (): void {
    // This test is a note to whoever adds `case Two = 2;`, placed where they
    // cannot miss it: it fails the moment they do, and here is what it is
    // asking for.
    //
    // The marker is what ships now, because state written without one
    // can never be migrated afterwards — there is nothing to tell it apart by.
    // Migration is what the marker is *for*, and its machinery is deliberately
    // absent: with one case, "is this current", "does this need migrating" and
    // "is this from a newer build" are branches nothing can reach and no test
    // can kill. Writing them now would mean shipping untestable code and
    // calling it a rule.
    //
    // The second case is the commit that adds them, and it needs all of:
    //
    //   - a migration from One to Two, and a test that state written in One
    //     comes back out in Two with nothing lost;
    //   - a refusal to read a shape *above* the current one — migration runs
    //     forwards only, this build cannot know what a later one wrote, and
    //     guessing produces a record that looks right and holds somebody else's
    //     fields. An operator who has downgraded is better told their state was
    //     set aside than shown a stack that half-works;
    //   - a check that `current()` is the highest case, since "older" is
    //     decided by number and a `current()` that was not the highest would
    //     run a forward migration over fields it has never seen;
    //   - and a discard may not take the pairing or its pinned
    //     fingerprint with it, so whatever discards has to say what it keeps.
    expect(Shape::cases())->toBe([Shape::One]);
});
