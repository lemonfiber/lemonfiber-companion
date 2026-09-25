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

    expect(new HowAGlossReads()->of($glossary, AWordInUse::named('pin'), whereTheGlossedStackIs())->goes)->not->toBe('')
        ->and(new HowAGlossReads()->of($glossary, AWordInUse::named('grab'), whereTheGlossedStackIs())->goes)->toBe('');
});

it('draws a word the glossary does not carry as it came, with no gloss and nowhere to go', function (): void {
    $gloss = new HowAGlossReads()->of(TheGlossary::of(), AWordInUse::named('Grabber'), whereTheGlossedStackIs());

    expect([$gloss->word, $gloss->short, $gloss->goes])->toBe(['Grabber', '', '']);
});
