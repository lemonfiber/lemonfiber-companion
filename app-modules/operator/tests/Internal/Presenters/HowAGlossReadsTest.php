<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheGlossary;
use Modules\Operator\Internal\Presenters\HowAGlossReads;
use Modules\Operator\Internal\WhereAStackIs;

use function str_repeat;

function whereTheGlossedStackIs(): WhereAStackIs
{
    return WhereAStackIs::of(StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))));
}

it('offers the way to the longer gloss only where the glossary has one', function (): void {
    $glossary = TheGlossary::of(
        AWord::explained('pin', 'Held at', 'A service runs the version it is pinned to.'),
        AWord::explained('grab', 'Taking a release', ''),
    );

    expect(new HowAGlossReads()->of($glossary, AWordInUse::named('pin'), whereTheGlossedStackIs())->goes)->toBe(whereTheGlossedStackIs()->wordAbout(AWordInUse::named('pin')))
        ->and(new HowAGlossReads()->of($glossary, AWordInUse::named('grab'), whereTheGlossedStackIs())->goes)->toBeNull();
});

it('draws a word the glossary does not carry as it came, with no gloss, and the way to ask the stack for it', function (): void {
    $gloss = new HowAGlossReads()->of(TheGlossary::of(), AWordInUse::named('Grabber'), whereTheGlossedStackIs());

    expect([$gloss->word, $gloss->short, $gloss->goes, $gloss->waits])->toBe(['Grabber', null, null, false])
        ->and($gloss->asks)->toBe(whereTheGlossedStackIs()->wordAbout(AWordInUse::named('Grabber')));
});

it('offers no asking for a word the glossary carries', function (): void {
    $glossary = TheGlossary::of(AWord::explained('grab', 'Taking a release', ''));

    expect(new HowAGlossReads()->of($glossary, AWordInUse::named('grab'), whereTheGlossedStackIs())->asks)->toBeNull();
});

it('draws a word as it came while the glossary waits for the next frame, and says it waits only then', function (): void {
    $waiting = new HowAGlossReads()->waiting(AWordInUse::named('grab'));
    $read = new HowAGlossReads()->of(TheGlossary::of(AWord::explained('grab', 'Taking a release', '')), AWordInUse::named('grab'), whereTheGlossedStackIs());

    expect([$waiting->word, $waiting->short, $waiting->asks, $waiting->waits])->toBe(['grab', null, null, true])
        ->and([$read->short, $read->waits])->toBe(['Taking a release', false]);
});
