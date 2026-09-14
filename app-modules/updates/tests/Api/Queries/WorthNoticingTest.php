<?php

declare(strict_types=1);

use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Updates\Api\Queries\WorthNoticing;

/**
 * The versions a reading comes out with, as words a case can compare.
 *
 * @return list<string>
 */
function versionsIn(Releases $releases): array
{
    $named = [];

    foreach ($releases as $release) {
        $named[] = $release->version();
    }

    return $named;
}

/** Three releases, one of which nobody in the house would notice. */
function whatIsWaiting(): Releases
{
    return Releases::these(
        Release::called('4.1.0', noticeable: true, withdrawn: false),
        Release::called('4.0.16', noticeable: false, withdrawn: false),
        Release::called('4.0.18', noticeable: true, withdrawn: false),
    );
}

it('N2-R16 — keeps the ones somebody in the house would see the difference from', function (): void {
    expect(versionsIn(new WorthNoticing()->over(whatIsWaiting())))->toBe(['4.1.0', '4.0.18']);
});

it('N2-R16 — narrows without reordering', function (): void {
    // The order the stack listed them in is the order a changelog is read in.
    // Something that both filtered and sorted would make the two impossible to
    // compose.
    $reversed = Releases::these(
        Release::called('4.0.18', noticeable: true, withdrawn: false),
        Release::called('4.1.0', noticeable: true, withdrawn: false),
    );

    expect(versionsIn(new WorthNoticing()->over($reversed)))->toBe(['4.0.18', '4.1.0']);
});

it('answers nothing where a stack has named nothing', function (): void {
    expect(new WorthNoticing()->over(Releases::none())->isEmpty())->toBeTrue();
});

it('answers with nothing where nothing would be noticed', function (): void {
    $chores = Releases::these(Release::called('4.0.16', noticeable: false, withdrawn: false));

    expect(new WorthNoticing()->over($chores)->isEmpty())->toBeTrue();
});

it('answers with the collection rather than an array', function (): void {
    expect(new WorthNoticing()->over(whatIsWaiting()))->toBeInstanceOf(Releases::class);
});
