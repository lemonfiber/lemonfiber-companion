<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ALineOfADiff;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\ARevertCannotBeShown;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\AStackEditCannotBeShown;
use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\ThereIsNothingToAgreeTo;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\TheStackEdits;

use function sprintf;

/**
 * Every line of one file, as whose it is and what it says.
 *
 * @return list<string>
 */
function everyLineOfTheEdit(AStackEdit $edit): array
{
    return array_map(
        static fn(ALineOfADiff $line): string => sprintf('%s|%s', $line->isTheirs() ? 'theirs' : 'lemonfiber\'s', $line->text()),
        iterator_to_array($edit, preserve_keys: false),
    );
}

/** One file with one line each way, for the cases that need a file at all. */
function anEditOfTheCompose(): AStackEdit
{
    return AStackEdit::at('compose.yaml', "- image: sonarr:4.0.1\n+ image: sonarr:4.0.0\n");
}

it('reads the operator\'s lines and lemonfiber\'s from the diff, in the stack\'s order, without their marks', function (): void {
    $edit = AStackEdit::at('env/sonarr.env', "- TZ=Europe/Amsterdam\n- \n+ TZ=UTC\n+ PUID=1000");

    expect($edit->path())->toBe('env/sonarr.env')
        ->and(everyLineOfTheEdit($edit))->toBe(['theirs|TZ=Europe/Amsterdam', 'theirs|', 'lemonfiber\'s|TZ=UTC', 'lemonfiber\'s|PUID=1000'])
        ->and($edit)->toHaveCount(4);
});

it('keeps a character that is more than one byte whole', function (): void {
    expect(everyLineOfTheEdit(AStackEdit::at('labels.yaml', "- naam: Één\n+ naam: Een\n")))->toBe(['theirs|naam: Één', 'lemonfiber\'s|naam: Een']);
});

it('reads an empty diff as a file whose difference no line shows', function (): void {
    expect(AStackEdit::at('compose.yaml', ''))->toHaveCount(0);
});

it('refuses a file with no path, and a line marked as neither side, naming which line', function (): void {
    expect(static fn(): AStackEdit => AStackEdit::at(' ', ''))->toThrow(AStackEditCannotBeShown::class, 'arrived with no path')
        ->and(static fn(): AStackEdit => AStackEdit::at('compose.yaml', "- a\n  b\n"))->toThrow(AStackEditCannotBeShown::class, 'Line 1 of a diff')
        ->and(static fn(): AStackEdit => AStackEdit::at('compose.yaml', "-a\n"))->toThrow(AStackEditCannotBeShown::class, 'Line 0 of a diff')
        ->and(static fn(): AStackEdit => AStackEdit::at('compose.yaml', "+b\n"))->toThrow(AStackEditCannotBeShown::class, 'Line 0 of a diff');
});

it('keeps the files and the connections in the stack\'s order, whatever keys they came under', function (): void {
    $compose = anEditOfTheCompose();
    $env = AStackEdit::at('env/sonarr.env', '');
    $edits = TheStackEdits::these(...['a' => $compose, 'b' => $env]);
    $connections = ConnectionsReverted::these(...['x' => 'sonarr → qbittorrent', 'y' => 'radarr → qbittorrent']);

    expect(iterator_to_array($edits, preserve_keys: true))->toBe([$compose, $env])
        ->and($edits)->toHaveCount(2)
        ->and(iterator_to_array($connections, preserve_keys: true))->toBe(['sonarr → qbittorrent', 'radarr → qbittorrent'])
        ->and($connections)->toHaveCount(2);
});

it('refuses a connection with no name', function (): void {
    expect(static fn(): ConnectionsReverted => ConnectionsReverted::these('sonarr → qbittorrent', ' '))
        ->toThrow(ARevertCannotBeShown::class, 'connection putting the configuration back reverts arrived with no name');
});

it('says whether it was carried out, what it reverts, and whether that is anything', function (): void {
    $edits = TheStackEdits::these(anEditOfTheCompose());
    $connections = ConnectionsReverted::these('sonarr → qbittorrent');
    $previewed = TheReset::previewed($edits, $connections);
    $carriedOut = TheReset::carriedOut($edits, $connections);

    expect([$previewed->wasCarriedOut(), $previewed->edits(), $previewed->connections(), $previewed->changesNothing()])->toBe([false, $edits, $connections, false])
        ->and([$carriedOut->wasCarriedOut(), $carriedOut->edits(), $carriedOut->connections(), $carriedOut->changesNothing()])->toBe([true, $edits, $connections, false]);
});

it('changes nothing only where it reverts no file and no connection', function (TheStackEdits $edits, ConnectionsReverted $connections, bool $nothing): void {
    expect(TheReset::previewed($edits, $connections)->changesNothing())->toBe($nothing)
        ->and(TheReset::previewed($edits, $connections)->mayBeAgreedTo())->toBe(! $nothing);
})->with([
    'neither' => [TheStackEdits::these(), ConnectionsReverted::these(), true],
    'a file only' => [TheStackEdits::these(AStackEdit::at('compose.yaml', '')), ConnectionsReverted::these(), false],
    'a connection only' => [TheStackEdits::these(), ConnectionsReverted::these('sonarr → qbittorrent'), false],
]);

it('may be agreed to only as a preview that would revert something', function (): void {
    $edits = TheStackEdits::these(anEditOfTheCompose());

    expect(TheReset::carriedOut($edits, ConnectionsReverted::these())->mayBeAgreedTo())->toBeFalse()
        ->and(AResetAgreed::to(TheReset::previewed($edits, ConnectionsReverted::these())))->toBeInstanceOf(AResetAgreed::class)
        ->and(static fn(): AResetAgreed => AResetAgreed::to(TheReset::carriedOut($edits, ConnectionsReverted::these())))
        ->toThrow(ThereIsNothingToAgreeTo::class, 'agreed to against one already carried out')
        ->and(static fn(): AResetAgreed => AResetAgreed::to(TheReset::previewed(TheStackEdits::these(), ConnectionsReverted::these())))
        ->toThrow(ThereIsNothingToAgreeTo::class, 'a preview that reverts nothing');
});
