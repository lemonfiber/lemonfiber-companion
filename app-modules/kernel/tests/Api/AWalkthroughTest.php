<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\TheLinesItSaid;
use Modules\Kernel\Api\TheWalkthroughSaysNothing;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatComesNext;
use Modules\Kernel\Api\WhatCouldBeWalkedInstead;
use Modules\Kernel\Api\WhatTheServicesWereSaying;
use Modules\Kernel\Api\WhatToDoNext;
use Modules\Kernel\Api\WhatWasWalked;
use Modules\Kernel\Api\WhereItStopped;
use Modules\Kernel\Api\WhereTheWalkthroughIs;
use Modules\Kernel\Api\WhichWalk;
use Modules\Kernel\Api\WhyTheWalkthroughStopped;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichWalkthroughArm
{
    public function __construct(public string $said) {}
}

/** A walk with only what is required, and nothing optional said. */
function aBareWalk(string $proves = 'That it works.', ?WhatWasWalked $item = null): AWalkthrough
{
    return AWalkthrough::reported(
        WhichWalk::Pipeline,
        WhereTheWalkthroughIs::Searching,
        $proves,
        $item ?? WhatWasWalked::nothingChosen(),
        TheLinesItSaid::of(),
        inBackground: false,
        alreadyHere: false,
    );
}

/**
 * What it walked, what the import did and where it stopped, each folded to a word.
 *
 * @return array{item: string, link: string, stopped: string}
 */
function theOptionalPartsOf(AWalkthrough $walk): array
{
    return [
        'item' => $walk->item()->either(
            named: static fn(string $item): WhichWalkthroughArm => new WhichWalkthroughArm($item),
            nothingChosen: static fn(): WhichWalkthroughArm => new WhichWalkthroughArm('(none)'),
        )->said,
        'link' => $walk->link(
            linked: static fn(HowTheImportLinked $link): WhichWalkthroughArm => new WhichWalkthroughArm($link->value),
            notImported: static fn(): WhichWalkthroughArm => new WhichWalkthroughArm('(none)'),
        )->said,
        'stopped' => $walk->stopped(
            at: static fn(WhereItStopped $stopped): WhichWalkthroughArm => new WhichWalkthroughArm($stopped->remedy()),
            didNotStop: static fn(): WhichWalkthroughArm => new WhichWalkthroughArm('(none)'),
        )->said,
    ];
}

it('carries everything it was reported with, and what it was then told', function (): void {
    $lines = TheLinesItSaid::of(ALineItSaid::withoutDetail(WalkthroughStep::Available, 'Available'));
    $suggestions = WhatCouldBeWalkedInstead::of('Sintel');
    $handover = WhatComesNext::of(WhatToDoNext::Household);
    $stopped = WhereItStopped::at(WalkthroughStep::Scanning, WhyTheWalkthroughStopped::NotVisible, 'Scan again.', WhatTheServicesWereSaying::of());

    $reported = AWalkthrough::reported(
        WhichWalk::LibraryOnly,
        WhereTheWalkthroughIs::Abandoned,
        'That it can be seen.',
        WhatWasWalked::called('Sintel'),
        $lines,
        inBackground: true,
        alreadyHere: true,
    )->offering($suggestions)->handingOnTo($handover);
    $walk = $reported->linked(HowTheImportLinked::Hardlinked)->stoppedAt($stopped);
    $otherWay = $reported->stoppedAt($stopped)->linked(HowTheImportLinked::Copied);

    expect($walk->shape())->toBe(WhichWalk::LibraryOnly)
        ->and($walk->state())->toBe(WhereTheWalkthroughIs::Abandoned)
        ->and($walk->proves())->toBe('That it can be seen.')
        ->and($walk->lines())->toBe($lines)
        ->and($walk->suggestions())->toBe($suggestions)
        ->and($walk->handover())->toBe($handover)
        ->and($walk->wentOnInTheBackground())->toBeTrue()
        ->and($walk->wasAlreadyHere())->toBeTrue()
        ->and(theOptionalPartsOf($walk))->toBe(['item' => 'Sintel', 'link' => 'hardlinked', 'stopped' => 'Scan again.'])
        ->and(theOptionalPartsOf($otherWay))->toBe(['item' => 'Sintel', 'link' => 'copied', 'stopped' => 'Scan again.'])
        ->and(theOptionalPartsOf($reported))->toBe(['item' => 'Sintel', 'link' => '(none)', 'stopped' => '(none)'])
        ->and([$otherWay->shape(), $otherWay->state(), $otherWay->proves(), $otherWay->lines(), $otherWay->suggestions(), $otherWay->handover(), $otherWay->wentOnInTheBackground(), $otherWay->wasAlreadyHere()])
        ->toBe([WhichWalk::LibraryOnly, WhereTheWalkthroughIs::Abandoned, 'That it can be seen.', $lines, $suggestions, $handover, true, true]);
});

it('keeps the import and the stop when told its suggestions and handover afterwards', function (): void {
    $suggestions = WhatCouldBeWalkedInstead::of('Sintel');
    $handover = WhatComesNext::of(WhatToDoNext::ClientApps);
    $stopped = WhereItStopped::at(WalkthroughStep::Searching, WhyTheWalkthroughStopped::NothingMatched, 'Try another.', WhatTheServicesWereSaying::of());

    $walk = aBareWalk()->linked(HowTheImportLinked::Copied)->stoppedAt($stopped)->handingOnTo($handover)->offering($suggestions);

    expect(theOptionalPartsOf($walk))->toBe(['item' => '(none)', 'link' => 'copied', 'stopped' => 'Try another.'])
        ->and($walk->suggestions())->toBe($suggestions)
        ->and($walk->handover())->toBe($handover)
        ->and([$walk->shape(), $walk->state(), $walk->proves(), $walk->wentOnInTheBackground(), $walk->wasAlreadyHere()])
        ->toBe([WhichWalk::Pipeline, WhereTheWalkthroughIs::Searching, 'That it works.', false, false]);
});

it('carries nothing for what the stack did not say', function (): void {
    $walk = aBareWalk();

    expect(theOptionalPartsOf($walk))->toBe(['item' => '(none)', 'link' => '(none)', 'stopped' => '(none)'])
        ->and($walk->suggestions()->count())->toBe(0)
        ->and($walk->handover()->count())->toBe(0)
        ->and($walk->wentOnInTheBackground())->toBeFalse()
        ->and($walk->wasAlreadyHere())->toBeFalse();
});

it('keeps what it proves and what it walked as they were said, spaces and all', function (): void {
    $walk = aBareWalk(' That it works. ', WhatWasWalked::called(' Sintel '));

    expect($walk->proves())->toBe(' That it works. ')
        ->and(theOptionalPartsOf($walk)['item'])->toBe(' Sintel ');
});

it('refuses a walk that says nothing about what it proves', function (string $proves): void {
    expect(fn(): AWalkthrough => aBareWalk($proves))
        ->toThrow(TheWalkthroughSaysNothing::class, 'its `proves` blank');
})->with(['empty' => [''], 'only spaces' => ['  ']]);

it('refuses a title that says nothing', function (string $item): void {
    expect(fn(): WhatWasWalked => WhatWasWalked::called($item))
        ->toThrow(TheWalkthroughSaysNothing::class, 'its `item` blank');
})->with(['empty' => [''], 'only spaces' => ['  ']]);

it('carries a line as it was said, with its detail or without one', function (): void {
    $with = ALineItSaid::withDetail(WalkthroughStep::Grabbing, ' Sending… ', ' SABnzbd ');
    $without = ALineItSaid::withoutDetail(WalkthroughStep::Importing, 'Importing…');

    $detail = static fn(ALineItSaid $line): string => $line->detail(
        said: static fn(string $detail): WhichWalkthroughArm => new WhichWalkthroughArm($detail),
        nothing: static fn(): WhichWalkthroughArm => new WhichWalkthroughArm('(none)'),
    )->said;

    expect($with->step())->toBe(WalkthroughStep::Grabbing)
        ->and($with->said())->toBe(' Sending… ')
        ->and($detail($with))->toBe(' SABnzbd ')
        ->and($without->step())->toBe(WalkthroughStep::Importing)
        ->and($without->said())->toBe('Importing…')
        ->and($detail($without))->toBe('(none)');
});

it('refuses a line that says nothing, or a detail that says nothing', function (callable $line, string $field): void {
    expect($line)->toThrow(TheWalkthroughSaysNothing::class, sprintf('`%s`', $field));
})->with([
    'said empty, with a detail' => [static fn(): ALineItSaid => ALineItSaid::withDetail(WalkthroughStep::Searching, '', 'three'), 'said'],
    'said spaces, with a detail' => [static fn(): ALineItSaid => ALineItSaid::withDetail(WalkthroughStep::Searching, ' ', 'three'), 'said'],
    'said empty, without one' => [static fn(): ALineItSaid => ALineItSaid::withoutDetail(WalkthroughStep::Searching, ''), 'said'],
    'said spaces, without one' => [static fn(): ALineItSaid => ALineItSaid::withoutDetail(WalkthroughStep::Searching, '   '), 'said'],
    'a detail that is empty' => [static fn(): ALineItSaid => ALineItSaid::withDetail(WalkthroughStep::Searching, 'Searching…', ''), 'detail'],
    'a detail of only spaces' => [static fn(): ALineItSaid => ALineItSaid::withDetail(WalkthroughStep::Searching, 'Searching…', '  '), 'detail'],
]);

it('keeps the lines in the order they were said, whatever order the steps come in', function (): void {
    $lines = TheLinesItSaid::of(
        ALineItSaid::withoutDetail(WalkthroughStep::Scanning, 'Scanning…'),
        ALineItSaid::withoutDetail(WalkthroughStep::Choosing, 'Choosing…'),
    );
    $said = [];

    foreach ($lines as $line) {
        $said[] = $line->said();
    }

    expect($said)->toBe(['Scanning…', 'Choosing…'])
        ->and($lines->count())->toBe(2)
        ->and(TheLinesItSaid::of()->count())->toBe(0);
});

it('carries where it stopped, why, the remedy and the logs', function (): void {
    $logs = WhatTheServicesWereSaying::of('radarr: import refused', '', 'radarr: permission denied');
    $stopped = WhereItStopped::at(WalkthroughStep::Importing, WhyTheWalkthroughStopped::ImportFailed, ' Fix the permissions. ', $logs);

    expect($stopped->step())->toBe(WalkthroughStep::Importing)
        ->and($stopped->why())->toBe(WhyTheWalkthroughStopped::ImportFailed)
        ->and($stopped->remedy())->toBe(' Fix the permissions. ')
        ->and($stopped->logs())->toBe($logs)
        ->and(iterator_to_array($logs, preserve_keys: false))->toBe(['radarr: import refused', '', 'radarr: permission denied'])
        ->and($logs->count())->toBe(3)
        ->and(WhatTheServicesWereSaying::of()->count())->toBe(0);
});

it('refuses a stop with nothing to try', function (string $remedy): void {
    expect(fn(): WhereItStopped => WhereItStopped::at(WalkthroughStep::Searching, WhyTheWalkthroughStopped::Stalled, $remedy, WhatTheServicesWereSaying::of()))
        ->toThrow(TheWalkthroughSaysNothing::class, '`remedy`');
})->with(['empty' => [''], 'only spaces' => ['  ']]);

it('keeps what it suggests and what comes next in the stack\'s order', function (): void {
    $suggestions = WhatCouldBeWalkedInstead::of('Sintel', 'Big Buck Bunny');
    $next = WhatComesNext::of(WhatToDoNext::ClientApps, WhatToDoNext::MoreContent);

    expect(iterator_to_array($suggestions, preserve_keys: false))->toBe(['Sintel', 'Big Buck Bunny'])
        ->and($suggestions->count())->toBe(2)
        ->and(iterator_to_array($next, preserve_keys: false))->toBe([WhatToDoNext::ClientApps, WhatToDoNext::MoreContent])
        ->and($next->count())->toBe(2)
        ->and(WhatComesNext::of()->count())->toBe(0)
        ->and(WhatCouldBeWalkedInstead::of()->count())->toBe(0);
});

it('refuses a blank suggestion wherever it sits in the list', function (string ...$suggestions): void {
    expect(fn(): WhatCouldBeWalkedInstead => WhatCouldBeWalkedInstead::of(...$suggestions))
        ->toThrow(TheWalkthroughSaysNothing::class, '`suggestions`');
})->with([
    'first, and empty' => ['', 'Sintel'],
    'first, and only spaces' => [' ', 'Sintel'],
    'later, and only spaces' => ['Sintel', 'Big Buck Bunny', '  '],
]);

it('says why a narration with a line left out is refused', function (): void {
    expect(TheWalkthroughSaysNothing::about('said')->getMessage())
        ->toBe('A walkthrough arrived with its `said` blank, and a narration that leaves a line out is not the one the operator watched.');
});

it('keys each sentence under the walkthrough, by the word the stack writes', function (): void {
    expect(WhereTheWalkthroughIs::Complete->saidOnTheScreen())->toBe('health.walkthrough.state.complete')
        ->and(WhichWalk::LibraryOnly->saidOnTheScreen())->toBe('health.walkthrough.shape.library-only')
        ->and(HowTheImportLinked::Copied->saidOnTheScreen())->toBe('health.walkthrough.link.copied')
        ->and(WhatToDoNext::ClientApps->saidOnTheScreen())->toBe('health.walkthrough.next.client-apps')
        ->and(WhyTheWalkthroughStopped::NoneMetThePreset->saidOnTheScreen())->toBe('health.walkthrough.stopped.none-met-the-preset');
});

it('numbers what each list holds from nought, whatever it was handed under', function (): void {
    $line = ALineItSaid::withoutDetail(WalkthroughStep::Available, 'Available');

    expect(iterator_to_array(TheLinesItSaid::of(...['x' => $line]), preserve_keys: true))->toBe([$line])
        ->and(iterator_to_array(WhatTheServicesWereSaying::of(...['x' => 'a log line']), preserve_keys: true))->toBe(['a log line'])
        ->and(iterator_to_array(WhatComesNext::of(...['x' => WhatToDoNext::Household]), preserve_keys: true))->toBe([WhatToDoNext::Household])
        ->and(iterator_to_array(WhatCouldBeWalkedInstead::of(...['x' => 'Sintel']), preserve_keys: true))->toBe(['Sintel']);
});
