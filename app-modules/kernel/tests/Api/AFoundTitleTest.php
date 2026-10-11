<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\FoundTitleSaysNothing;
use Modules\Kernel\Api\HowAnAskWent;
use Modules\Kernel\Api\HowASearchWent;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatAnAskBecame;
use Modules\Kernel\Api\WhatAnAskCameTo;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** A film found by a search. */
function aFilmFound(WhereAFoundTitleStands $stands = WhereAFoundTitleStands::Askable): AFoundTitle
{
    return AFoundTitle::listed('438631', KindOfTitle::Film, 'Dune', WhenItCameOut::in(2021), $stands);
}

it('keeps what the core listed a found title as', function (): void {
    $title = aFilmFound();

    expect($title->id())->toBe('438631')
        ->and($title->kind())->toBe(KindOfTitle::Film)
        ->and($title->title())->toBe('Dune')
        ->and($title->stands())->toBe(WhereAFoundTitleStands::Askable)
        ->and($title->year()->either(dated: static fn(int $year): TheWordCarriedOut => new TheWordCarriedOut((string) $year), unstated: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'))->said)->toBe('2021');
});

it('refuses a found title with no id or no name, naming which', function (string $id, string $title, string $named): void {
    expect(fn(): AFoundTitle => AFoundTitle::listed($id, KindOfTitle::Series, $title, WhenItCameOut::unstated(), WhereAFoundTitleStands::Askable))
        ->toThrow(FoundTitleSaysNothing::class, sprintf('`%s`', $named));
})->with([
    'a blank id' => [' ', 'Dune', 'id'],
    'a blank name' => ['1', "\t", 'title'],
]);

it('lets only a title nobody has asked for be asked for', function (): void {
    expect(array_map(static fn(WhereAFoundTitleStands $stands): bool => $stands->mayBeAskedFor(), WhereAFoundTitleStands::cases()))
        ->toBe([false, false, false, true]);
});

it('trims the words searched for, and holds fewer than the core searches for as not enough', function (): void {
    expect(WhatToLookFor::typed('  dune ')->said())->toBe('dune')
        ->and(WhatToLookFor::typed(' du ')->isEnough())->toBeTrue()
        ->and(WhatToLookFor::typed(' d ')->isEnough())->toBeFalse()
        ->and(WhatToLookFor::typed('é')->isEnough())->toBeFalse()
        ->and(WhatToLookFor::typed('éé')->isEnough())->toBeTrue();
});

it('refuses a blank place for the next page', function (): void {
    expect(fn(): WhereTheNextPageBegins => WhereTheNextPageBegins::at(' '))->toThrow(FoundTitleSaysNothing::class, '`next`')
        ->and(WhereTheNextPageBegins::at('page-2')->said())->toBe('page-2');
});

/** Where a page says the next one begins, or that it is the last. */
function whatFollows(WhatASearchFound $page): string
{
    return $page->followedBy(more: static fn(WhereTheNextPageBegins $next): TheWordCarriedOut => new TheWordCarriedOut($next->said()), last: static fn(): TheWordCarriedOut => new TheWordCarriedOut('last'))->said;
}

it('keeps a page\'s titles in order, its sentence, and what follows it', function (): void {
    $page = WhatASearchFound::before(WhereTheNextPageBegins::at('p2'), 'It waits for a yes.', aFilmFound(), aFilmFound(WhereAFoundTitleStands::Here));
    $last = WhatASearchFound::lastly('It waits for a yes.');

    expect(array_map(static fn(AFoundTitle $title): string => $title->stands()->value, [...$page]))->toBe(['askable', 'here'])
        ->and($page->approval())->toBe('It waits for a yes.')
        ->and($page->isEmpty())->toBeFalse()
        ->and(whatFollows($page))->toBe('p2')
        ->and($last->isEmpty())->toBeTrue()
        ->and(whatFollows($last))->toBe('last');
});

it('takes the arm a search and an ask went down, and carries what each held', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $asked = WhatAnAskCameTo::answered(WhatAnAskBecame::OnItsWay, WhetherItWasRehearsed::CarriedOut, 'Somebody already asked for Dune.');

    expect(HowASearchWent::found(WhatASearchFound::lastly('a'))->either(found: static fn(WhatASearchFound $page): TheWordCarriedOut => new TheWordCarriedOut($page->approval()), refused: static fn(): TheWordCarriedOut => new TheWordCarriedOut('refused'))->said)->toBe('a')
        ->and(HowASearchWent::refused($why)->either(found: static fn(): TheWordCarriedOut => new TheWordCarriedOut('found'), refused: static fn(Obstacle $met): TheWordCarriedOut => new TheWordCarriedOut($met->kind()->value))->said)->toBe($why->kind()->value)
        ->and(HowAnAskWent::answered($asked)->either(answered: static fn(WhatAnAskCameTo $said): TheWordCarriedOut => new TheWordCarriedOut($said->said()), refused: static fn(): TheWordCarriedOut => new TheWordCarriedOut('refused'))->said)->toBe('Somebody already asked for Dune.')
        ->and(HowAnAskWent::refused($why)->either(answered: static fn(): TheWordCarriedOut => new TheWordCarriedOut('answered'), refused: static fn(Obstacle $met): TheWordCarriedOut => new TheWordCarriedOut($met->kind()->value))->said)->toBe($why->kind()->value);
});

it('keeps what an ask became and whether it was a rehearsal, and refuses one with no sentence', function (): void {
    $rehearsal = WhatAnAskCameTo::answered(WhatAnAskBecame::Waiting, WhetherItWasRehearsed::Rehearsed, 'It would wait.');
    $asked = WhatAnAskCameTo::answered(WhatAnAskBecame::Asked, WhetherItWasRehearsed::CarriedOut, 'Asked.');

    expect($rehearsal->became())->toBe(WhatAnAskBecame::Waiting)
        ->and($rehearsal->wasRehearsed())->toBeTrue()
        ->and($asked->wasRehearsed())->toBeFalse()
        ->and(fn(): WhatAnAskCameTo => WhatAnAskCameTo::answered(WhatAnAskBecame::Asked, WhetherItWasRehearsed::CarriedOut, ' '))->toThrow(FoundTitleSaysNothing::class, '`said`');
});
