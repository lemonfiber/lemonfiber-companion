<?php

declare(strict_types=1);

use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stuck;
use Modules\Kernel\Api\StuckSaysNothing;

/** One word carried out of `stated()`, since it must hand back an object. */
final readonly class WhatOneStalledItemSaid
{
    public function __construct(public string $said) {}
}

/** All three facts of a stalled item, folded into one string. */
function whatTheStalledItemSaid(Stuck $stuck): string
{
    return $stuck->stated(
        static fn(string $title, string $service, Stage $stage): WhatOneStalledItemSaid
            => new WhatOneStalledItemSaid(sprintf('%s/%s/%s', $title, $service, $stage->value)),
    )->said;
}

it('N2-R9 — hands over what stopped, where it stopped and who has it, together', function (): void {
    // One closure rather than three accessors, because the three are one fact
    // and three getters are three chances to call two of them.
    $stuck = Stuck::at('A film nobody has seen', 'radarr', Stage::Searching);

    expect(whatTheStalledItemSaid($stuck))->toBe('A film nobody has seen/radarr/searching');
});

it('refuses a stalled item with no title', function (): void {
    // It renders as an empty row somebody is asked to act on.
    expect(fn(): Stuck => Stuck::at('   ', 'radarr', Stage::Searching))
        ->toThrow(StuckSaysNothing::class, 'no title');
});

it('refuses a stalled item naming no service', function (): void {
    // A row with nowhere to go: the operator is told something stopped and not
    // where they would go to look at it.
    expect(fn(): Stuck => Stuck::at('A film nobody has seen', '  ', Stage::Searching))
        ->toThrow(StuckSaysNothing::class, 'no service');
});

it('keeps the words it was given, less the whitespace around them', function (): void {
    $stuck = Stuck::at('  A film nobody has seen  ', "\tradarr\n", Stage::Downloaded);

    expect(whatTheStalledItemSaid($stuck))->toBe('A film nobody has seen/radarr/downloaded');
});

it('says whether anything is still going to happen to it by itself', function (): void {
    // Published on its own where the three facts are not, because it is not
    // something the operator reads: it is how a screen decides which rows go
    // under which heading.
    expect(Stuck::at('A film', 'radarr', Stage::NotMonitored)->stillMoving())->toBeFalse()
        ->and(Stuck::at('A film', 'radarr', Stage::Downloading)->stillMoving())->toBeTrue();
});
