<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\UnattendedIsUnnamed;

/** One command as it arrives, with whatever a case wants to change. */
function aCommand(
    string $name = 'Watching the library',
    string $command = 'lemonfiber watch --all',
    string $guarantees = 'New files are noticed as they land',
    HowItIsHosted $standing = HowItIsHosted::Hosted,
): Unattended {
    return Unattended::called($name, $command, $guarantees, $standing);
}

/** One word carried out of the missing arm. */
final readonly class WhatTheCommandSaidAboutWhatIsGone
{
    public function __construct(public string $said) {}
}

/**
 * The program that is gone, or the word for nothing being.
 *
 * Named for this file (`G10`). Read through the fold rather than off a getter,
 * because what the requirement is about is what reaches a screen: a row that
 * held the path and handed over nothing would satisfy every other assertion
 * here and fail the one that matters.
 */
function whatIsGoneFrom(Unattended $command): string
{
    return $command->missing(
        gone: static fn(string $where): WhatTheCommandSaidAboutWhatIsGone
            => new WhatTheCommandSaidAboutWhatIsGone($where),
        nothing: static fn(): WhatTheCommandSaidAboutWhatIsGone
            => new WhatTheCommandSaidAboutWhatIsGone('nothing is missing'),
    )->said;
}

it('N16-R5 — carries the three words a decision about a reboot is made from', function (): void {
    $command = aCommand();

    expect($command->name())->toBe('Watching the library')
        ->and($command->command())->toBe('lemonfiber watch --all')
        ->and($command->guarantees())->toBe('New files are noticed as they land')
        ->and($command->standing())->toBe(HowItIsHosted::Hosted);
});

it('takes the words as the stack wrote them, less the space around them', function (): void {
    $command = aCommand(name: "  Watching \n", command: '  lemonfiber watch  ', guarantees: '  Files  ');

    expect($command->name())->toBe('Watching')
        ->and($command->command())->toBe('lemonfiber watch')
        ->and($command->guarantees())->toBe('Files');
});

it('N16-R5 — refuses a row whose label is blank, naming what it sat beside', function (): void {
    // A row carrying a control and no label is the worst version of this
    // screen, and a refusal naming nothing leaves somebody reading all nine.
    expect(fn(): Unattended => aCommand(name: '   '))
        ->toThrow(UnattendedIsUnnamed::class, 'lemonfiber watch --all');
});

it('N16-R5 — refuses a row that names no command, naming the label instead', function (): void {
    expect(fn(): Unattended => aCommand(command: '   '))
        ->toThrow(UnattendedIsUnnamed::class, 'Watching the library');
});

it('N16-R5 — refuses a row that says nothing about what it guarantees', function (): void {
    // Without it an operator is asked to decide whether something should
    // survive every reboot, knowing only what it is called.
    expect(fn(): Unattended => aCommand(guarantees: '   '))
        ->toThrow(UnattendedIsUnnamed::class, 'Watching the library');
});

it('N16-R6 — an orphan names the program that is gone, not the service', function (): void {
    // The difference between *Sonarr is not running* and *the file Sonarr's
    // service definition runs is not there any more*: the same row, and
    // different work.
    $command = Unattended::orphaned(
        'Watching the library',
        'lemonfiber watch --all',
        'New files are noticed as they land',
        '/usr/local/bin/lemonfiber',
    );

    expect(whatIsGoneFrom($command))->toBe('/usr/local/bin/lemonfiber');
});

it('N16-R6 — an orphan is `orphaned` by construction', function (): void {
    // The standing is not a parameter, which makes a row naming a missing
    // program while claiming to be running unspellable.
    $command = Unattended::orphaned('Watching', 'lemonfiber watch', 'Files are noticed', '/gone');

    expect($command->standing())->toBe(HowItIsHosted::Orphaned);
});

it('a command with nothing missing says so rather than answering with nothing', function (): void {
    // A screen handed a null would print an empty column where a path belongs,
    // and an operator would read that as *nothing is missing* on the one row
    // where something is.
    expect(whatIsGoneFrom(aCommand(standing: HowItIsHosted::Stopped)))->toBe('nothing is missing');
});

it('an orphan is refused for the reasons every row is', function (): void {
    // `orphaned()` goes through `called()`, so the blank-word refusals reach it
    // too — which is what keeps the one row carrying extra information from
    // being the one row that can arrive unreadable.
    expect(fn(): Unattended => Unattended::orphaned('  ', 'lemonfiber watch', 'Files', '/gone'))
        ->toThrow(UnattendedIsUnnamed::class);
});

it('N16-R6 — an orphan whose missing program is blank is refused', function (): void {
    // The whole of what this arm adds. A row reaching a screen as *installed
    // against a program that is gone* and naming no program tells an operator
    // less than the standing alone already did.
    expect(fn(): Unattended => Unattended::orphaned('Watching', 'lemonfiber watch', 'Files', '   '))
        ->toThrow(UnattendedIsUnnamed::class, 'Watching');
});
