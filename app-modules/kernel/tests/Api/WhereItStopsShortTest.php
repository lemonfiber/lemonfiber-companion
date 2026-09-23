<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ChangeSaysNothing;
use Modules\Kernel\Api\WhereItStopsShort;

/** One line carried out of the suggestion arm. */
final readonly class WhatTheLimitSuggested
{
    public function __construct(public string $said) {}
}

/** What to do instead, or the word for nothing. Named for this file (`G10`). */
function whatTheLimitSuggests(WhereItStopsShort $where): string
{
    return $where->instead(
        said: static fn(string $what): WhatTheLimitSuggested => new WhatTheLimitSuggested($what),
        nothing: static fn(): WhatTheLimitSuggested => new WhatTheLimitSuggested('nothing'),
    )->said;
}

it('N11-R2 — carries why putting a change back stops short', function (): void {
    expect(WhereItStopsShort::because('The old library was deleted')->why())->toBe('The old library was deleted');
});

it('suggests nothing unless told what to do instead', function (): void {
    expect(whatTheLimitSuggests(WhereItStopsShort::because('Gone')))->toBe('nothing');
});

it('N11-R2 — carries what to do instead beside the reason, never without it', function (): void {
    // There is no constructor for a suggestion alone. The stack builds both
    // from one refusal to go further, and that refusal always has a reason.
    $where = WhereItStopsShort::suggesting('The old library was deleted', 'Restore it from the last backup first');

    expect($where->why())->toBe('The old library was deleted')
        ->and(whatTheLimitSuggests($where))->toBe('Restore it from the last backup first');
});

it('takes its sentences as the stack wrote them, less the space around them', function (): void {
    $where = WhereItStopsShort::suggesting("  Gone \n", '  Restore it  ');

    expect($where->why())->toBe('Gone')
        ->and(whatTheLimitSuggests($where))->toBe('Restore it');
});

it('refuses a reason or a suggestion that is there and blank, naming which', function (): void {
    // Blank is not *nothing*: nothing is an arm, and blank is a sentence that
    // arrived empty. Drawn, it is a heading with no text under it.
    expect(fn(): WhereItStopsShort => WhereItStopsShort::because('  '))->toThrow(ChangeSaysNothing::class, '`because`')
        ->and(fn(): WhereItStopsShort => WhereItStopsShort::suggesting('  ', 'Restore it'))->toThrow(ChangeSaysNothing::class, '`because`')
        ->and(fn(): WhereItStopsShort => WhereItStopsShort::suggesting('Gone', '  '))->toThrow(ChangeSaysNothing::class, '`instead`');
});
