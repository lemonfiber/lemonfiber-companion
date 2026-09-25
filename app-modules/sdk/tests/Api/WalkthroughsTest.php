<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\TheWalkthroughSaysNothing;
use Modules\Kernel\Api\WhereItStopped;
use Modules\Sdk\Api\WalkthroughIsUnreadable;
use Modules\Sdk\Api\Walkthroughs;

use function sprintf;

use Tests\Support\WalkthroughsToFollow;
use Tests\Support\WhatTheContractAccepts;

/**
 * A `walkthrough` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function aWalkSaying(mixed $data): Envelope
{
    return new Envelope(1, 'walkthrough', $data);
}

/**
 * The walk that worked, as data, with whatever a case changes.
 *
 * @param array<string, mixed> $changed
 *
 * @return array<string, mixed>
 */
function theWalkThatWorkedWith(array $changed): array
{
    return [...WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt(), ...$changed];
}

/**
 * The walk that stopped, as data, with its stop changed.
 *
 * @param array<string, mixed> $changed
 *
 * @return array<string, mixed>
 */
function theWalkThatStoppedWith(array $changed): array
{
    return [
        ...WalkthroughsToFollow::theWalkThatMatchedNothingAsAStackSendsIt(),
        'stopped' => [
            'step' => 'searching',
            'reason' => 'nothing-matched',
            'remedy' => 'Try one of the suggestions, which are well seeded.',
            'logs' => ['prowlarr: query returned 0 results'],
            ...$changed,
        ],
    ];
}

/** @param array<array-key, mixed> $data */
function theWalkRead(array $data): AWalkthrough
{
    return Walkthroughs::in(aWalkSaying($data));
}

/** One line carried out of an arm. */
final readonly class WhatTheWalkCarried
{
    public function __construct(public string $said) {}
}

/**
 * The optional parts of a walk, each folded to a word: what it walked, what the
 * import did, what comes next, and where it stopped.
 *
 * @return array{item: string, link: string, next: int, stopped: string}
 */
function whatTheWalkLeftOptional(AWalkthrough $walk): array
{
    return [
        'item' => $walk->item()->either(
            named: static fn(string $item): WhatTheWalkCarried => new WhatTheWalkCarried($item),
            nothingChosen: static fn(): WhatTheWalkCarried => new WhatTheWalkCarried('(none)'),
        )->said,
        'link' => $walk->link(
            linked: static fn(HowTheImportLinked $link): WhatTheWalkCarried => new WhatTheWalkCarried($link->value),
            notImported: static fn(): WhatTheWalkCarried => new WhatTheWalkCarried('(none)'),
        )->said,
        'next' => $walk->handover()->count(),
        'stopped' => $walk->stopped(
            at: static fn(WhereItStopped $stopped): WhatTheWalkCarried => new WhatTheWalkCarried(sprintf('%s:%d logs', $stopped->why()->value, $stopped->logs()->count())),
            didNotStop: static fn(): WhatTheWalkCarried => new WhatTheWalkCarried('(none)'),
        )->said,
    ];
}

it('reads a walk that worked with everything it carries', function (): void {
    $walk = theWalkRead(WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt());
    $details = [];

    foreach ($walk->lines() as $line) {
        $details[] = $line->detail(
            said: static fn(string $detail): WhatTheWalkCarried => new WhatTheWalkCarried($detail),
            nothing: static fn(): WhatTheWalkCarried => new WhatTheWalkCarried('(none)'),
        )->said;
    }

    expect($walk->lines()->count())->toBe(6)
        ->and($details)->toBe(['3 indexers, 47 results', '1080p, matches your Balanced preset', 'SABnzbd, via usenet', '2.1 GB · 14 MB/s · ~2m', 'copied to /data/media/movies', '(none)'])
        ->and(whatTheWalkLeftOptional($walk))->toBe(['item' => 'Big Buck Bunny', 'link' => 'copied', 'next' => 3, 'stopped' => '(none)']);
});

it('reads a walk that stopped with the stop and every log line', function (): void {
    expect(whatTheWalkLeftOptional(theWalkRead(WalkthroughsToFollow::theWalkThatMatchedNothingAsAStackSendsIt())))
        ->toBe(['item' => 'A Film Nobody Seeded', 'link' => '(none)', 'next' => 0, 'stopped' => 'nothing-matched:2 logs']);
});

it('reads an absent optional field and a null one as the same nothing', function (string $field, string $part): void {
    $absent = theWalkRead(array_diff_key(WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt(), [$field => true]));
    $null = theWalkRead(theWalkThatWorkedWith([$field => null]));
    $nothing = ['item' => '(none)', 'link' => '(none)', 'next' => 0, 'stopped' => '(none)'];
    $otherwise = ['item' => 'Big Buck Bunny', 'link' => 'copied', 'next' => 3, 'stopped' => '(none)'];

    expect(whatTheWalkLeftOptional($absent))->toBe([...$otherwise, $part => $nothing[$part]])
        ->and(whatTheWalkLeftOptional($null))->toBe([...$otherwise, $part => $nothing[$part]]);
})->with([['item', 'item'], ['link', 'link'], ['handover', 'next'], ['stopped', 'stopped']]);

it('reads a log line as it came, blank included', function (): void {
    $logs = theWalkRead(WalkthroughsToFollow::theWalkThatMatchedNothingAsAStackSendsIt())->stopped(
        at: static fn(WhereItStopped $stopped): WhatTheWalkCarried => new WhatTheWalkCarried(implode('|', iterator_to_array($stopped->logs(), preserve_keys: false))),
        didNotStop: static fn(): WhatTheWalkCarried => new WhatTheWalkCarried('(none)'),
    )->said;

    expect($logs)->toBe('prowlarr: query returned 0 results|');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    foreach ([
        WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt(),
        WalkthroughsToFollow::theWalkThatMatchedNothingAsAStackSendsIt(),
        theWalkThatStoppedWith([]),
    ] as $data) {
        expect(WhatTheContractAccepts::complaintsAbout('WalkthroughEnvelope', ['api_version' => 1, 'kind' => 'walkthrough', 'data' => $data]))->toBe([]);
    }
});

it('refuses a payload that is not a table', function (): void {
    expect(fn(): AWalkthrough => Walkthroughs::in(aWalkSaying('nothing')))->toThrow(WalkthroughIsUnreadable::class, '`data`');
});

it('refuses a field that is missing or not what the contract says, naming it', function (string $field, mixed $said, string $named): void {
    $data = $said === 'absent'
        ? array_diff_key(WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt(), [$field => true])
        : theWalkThatWorkedWith([$field => $said]);

    expect(fn(): AWalkthrough => theWalkRead($data))->toThrow(WalkthroughIsUnreadable::class, sprintf('no readable `%s`', $named));
})->with([
    'no shape' => ['shape', 'absent', 'shape'],
    'a shape that is not text' => ['shape', 7, 'shape'],
    'no state' => ['state', 'absent', 'state'],
    'no proves' => ['proves', 'absent', 'proves'],
    'a blank proves' => ['proves', '', 'proves'],
    'a proves of only spaces' => ['proves', '  ', 'proves'],
    'no lines' => ['lines', 'absent', 'lines'],
    'lines that are a table' => ['lines', ['a' => []], 'lines'],
    'lines that are text' => ['lines', 'all of them', 'lines'],
    'a line that is not a table' => ['lines', ['Searching…'], 'lines'],
    'a line with no step' => ['lines', [['said' => 'Searching…', 'detail' => '']], 'step'],
    'a line with no said' => ['lines', [['step' => 'searching', 'detail' => '']], 'said'],
    'a line with a blank said' => ['lines', [['step' => 'searching', 'said' => ' ', 'detail' => '']], 'said'],
    'a line with no detail' => ['lines', [['step' => 'searching', 'said' => 'Searching…']], 'detail'],
    'a line whose detail is not text' => ['lines', [['step' => 'searching', 'said' => 'Searching…', 'detail' => null]], 'detail'],
    'no suggestions' => ['suggestions', 'absent', 'suggestions'],
    'a suggestion that is not text' => ['suggestions', [7], 'suggestions'],
    'no in_background' => ['in_background', 'absent', 'in_background'],
    'an in_background that is not a flag' => ['in_background', 'yes', 'in_background'],
    'no already_here' => ['already_here', 'absent', 'already_here'],
    'an already_here that is not a flag' => ['already_here', 1, 'already_here'],
    'a blank item' => ['item', ' ', 'item'],
    'an item that is not text' => ['item', 7, 'item'],
    'a link that is not text' => ['link', 7, 'link'],
    'a handover that is not a table' => ['handover', 'more-content', 'handover'],
    'a handover with no next' => ['handover', [], 'next'],
    'a next that is not a list' => ['handover', ['next' => 'household'], 'next'],
    'a next that is not text' => ['handover', ['next' => [7]], 'next'],
    'a stop that is not a table' => ['stopped', 'searching', 'stopped'],
]);

it('refuses a stop missing any of what it carries, naming it', function (string $field, mixed $said): void {
    expect(fn(): AWalkthrough => theWalkRead(theWalkThatStoppedWith([$field => $said])))
        ->toThrow(WalkthroughIsUnreadable::class, sprintf('no readable `%s`', $field));
})->with([
    ['step', null],
    ['reason', 7],
    ['remedy', ' '],
    ['logs', 'nothing'],
    ['logs', [7]],
]);

it('refuses a word it has no case for, naming the field and every word it reads', function (array $data, string $field, string $said, string $accepts): void {
    expect(fn(): AWalkthrough => theWalkRead($data))
        ->toThrow(WalkthroughIsUnreadable::class, sprintf('`%s` is `%s`, and this app reads %s', $field, $said, $accepts));
})->with([
    'a shape' => [theWalkThatWorkedWith(['shape' => 'partial']), 'shape', 'partial', '`pipeline`, `library-only`.'],
    'a state' => [theWalkThatWorkedWith(['state' => 'wandering']), 'state', 'wandering', '`offered`, `skipped`, `searching`, `grabbing`, `downloading`, `importing`, `complete`, `failed`, `abandoned`.'],
    'a step' => [theWalkThatWorkedWith(['lines' => [['step' => 'watching', 'said' => 'Watching…', 'detail' => '']]]), 'step', 'watching', '`choosing`, `searching`, `grabbing`, `downloading`, `importing`, `scanning`, `available`.'],
    'a link' => [theWalkThatWorkedWith(['link' => 'symlinked']), 'link', 'symlinked', '`hardlinked`, `copied`.'],
    'a next' => [theWalkThatWorkedWith(['handover' => ['next' => ['more-content', 'party']]]), 'next', 'party', '`more-content`, `household`, `client-apps`.'],
    'a reason' => [theWalkThatStoppedWith(['reason' => 'bored']), 'reason', 'bored', '`no-indexers`, `indexers-failed`, `nothing-matched`, `none-met-the-preset`, `tunnel-down`, `not-grabbed`, `stalled`, `import-failed`, `no-media-server`, `not-visible`.'],
    'a step it stopped at' => [theWalkThatStoppedWith(['step' => 'resting']), 'step', 'resting', '`choosing`, `searching`, `grabbing`, `downloading`, `importing`, `scanning`, `available`.'],
]);

it('refuses what the kernel refuses, keeping the refusal underneath', function (array $data, string $field): void {
    try {
        theWalkRead($data);
        $underneath = null;
    } catch (WalkthroughIsUnreadable $why) {
        $underneath = $why->getPrevious();
    }

    expect($underneath)->toBeInstanceOf(TheWalkthroughSaysNothing::class)
        ->and($underneath?->getMessage())->toContain(sprintf('`%s`', $field));
})->with([
    'a detail of only spaces' => [theWalkThatWorkedWith(['lines' => [['step' => 'searching', 'said' => 'Searching…', 'detail' => '  ']]]), 'detail'],
    'a blank suggestion' => [theWalkThatWorkedWith(['suggestions' => ['Sintel', ' ']]), 'suggestions'],
]);
