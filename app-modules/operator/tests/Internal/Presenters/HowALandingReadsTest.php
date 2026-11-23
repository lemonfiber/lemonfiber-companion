<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\HowALandingReads;

it('says a waiting change waits on its own row and on no other', function (): void {
    $waiting = new HowALandingReads()->waiting('LIBRARY_PATH');

    expect($waiting->waitsToChange('LIBRARY_PATH'))->toBeTrue()
        ->and($waiting->waitsToChange('USENET_HOST'))->toBeFalse()
        ->and($waiting->saidAbout('LIBRARY_PATH'))->toBeNull()
        ->and($waiting->came)->toBeNull();
});

it('draws what a made change came to on its own row, waiting no longer', function (): void {
    $made = new HowALandingReads()->made('LIBRARY_PATH', WhereTheChangeStands::at(
        ProposedChange::of('LIBRARY_PATH', '/data/films', WhatItHoldsNow::shown('/data/media'), Cost::Cheap),
        Stance::Applied,
        WhatAChangeComesTo::nothing(),
    ));

    expect($made->waitsToChange('LIBRARY_PATH'))->toBeFalse()
        ->and($made->came?->key)->toBe('LIBRARY_PATH')
        ->and($made->came?->went->cameBack())->toBeTrue()
        ->and($made->saidAbout('LIBRARY_PATH'))->toBeNull();
});

it('says why no change was made on the row it was for, and nothing on another', function (): void {
    $ended = new HowALandingReads()->ended('LIBRARY_PATH', WhyTheWorkEnded::LetGo);
    $moved = new HowALandingReads()->moved('LIBRARY_PATH');

    expect($ended->saidAbout('LIBRARY_PATH'))->toBe(HowALandingReads::ENDED)
        ->and($ended->saidAbout('USENET_HOST'))->toBeNull()
        ->and($ended->stands->hasEnded())->toBeTrue()
        ->and($moved->saidAbout('LIBRARY_PATH'))->toBe(HowALandingReads::MOVED)
        ->and($moved->came)->toBeNull();
});

it('puts what stood in the way of asking after the waiting in the place of the review on its row', function (): void {
    $met = new HowALandingReads()->met('LIBRARY_PATH', Obstacle::of(KindOfObstacle::StackDidNotAnswer));

    expect($met->stands->went->cameBack())->toBeFalse()
        ->and($met->waitsToChange('LIBRARY_PATH'))->toBeFalse()
        ->and($met->came?->key)->toBe('')
        ->and($met->came?->went->cameBack())->toBeFalse()
        ->and($met->saidAbout('LIBRARY_PATH'))->toBeNull();
});

it('waits on no row where nothing waits or no session is held', function (): void {
    $nothing = new HowALandingReads()->nothingWaits();
    $signedOut = new HowALandingReads()->signedOut();

    expect($nothing->key)->toBe('')
        ->and($nothing->stands->wasAsked())->toBeFalse()
        ->and($signedOut->key)->toBe('')
        ->and($signedOut->stands->went->isSignedIn)->toBeFalse();
});
