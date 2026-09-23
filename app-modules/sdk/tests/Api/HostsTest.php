<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Sdk\Api\HostingIsUnreadable;
use Modules\Sdk\Api\Hosts;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `hosting` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see stuckSaying()}'s reason: what is
 * under test is what happens when the wire says something the contract does not
 * allow, which a client honouring the contract could never produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function hostingSaying(array $data): Envelope
{
    return new Envelope(1, 'hosting', $data);
}

/**
 * One command, with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function aHostedRow(string $name, string $standing): array
{
    return [
        'name' => $name,
        'command' => 'lemonfiber watch',
        'guarantees' => 'Files are noticed',
        'standing' => $standing,
    ];
}

/** Every command, folded to one string, so the order can be read. */
function everyCommandIn(WhatRunsUnattended $running): string
{
    $rows = [];

    foreach ($running as $one) {
        $rows[] = sprintf('%s/%s', $one->name(), $one->standing()->value);
    }

    return implode(' | ', $rows);
}

/** The program a row says is gone, or the word for nothing being. */
function whatIsGoneIn(Unattended $command): string
{
    return $command->missing(
        gone: static fn(string $where): WhatOneHostedRowSaid => new WhatOneHostedRowSaid($where),
        nothing: static fn(): WhatOneHostedRowSaid => new WhatOneHostedRowSaid('nothing'),
    )->said;
}

/** One word carried out of an arm, since a fold must hand back an object. */
final readonly class WhatOneHostedRowSaid
{
    public function __construct(public string $said) {}
}

it('N16-R5 — reads a listing, keeping the machine\'s order', function (): void {
    $running = Hosts::in(hostingSaying([
        'manager' => 'launchd',
        'commands' => [aHostedRow('Watching', 'hosted'), aHostedRow('Seeding', 'stopped')],
    ]));

    expect(everyCommandIn($running))->toBe('Watching/hosted | Seeding/stopped')
        ->and($running->whatKeepsThem())->toBe(WhatKeepsItRunning::Launchd);
});

it('N16-R5 — a machine with no manager carries what to do instead', function (): void {
    $running = Hosts::in(hostingSaying([
        'manager' => 'unsupported',
        'instruction' => 'Add it to your own login items.',
        'commands' => [aHostedRow('Watching', 'unsupported')],
    ]));

    expect($running->whereItCannot(
        instead: static fn(string $what): WhatOneHostedRowSaid => new WhatOneHostedRowSaid($what),
        itself: static fn(): WhatOneHostedRowSaid => new WhatOneHostedRowSaid('itself'),
    )->said)->toBe('Add it to your own login items.');
});

it('a machine that hosts nothing is an answer rather than a gap', function (): void {
    expect(Hosts::in(hostingSaying(['manager' => 'systemd', 'commands' => []]))->count())->toBe(0);
});

it('N16-R6 — an orphan carries the program, and nothing else does', function (): void {
    $running = Hosts::in(hostingSaying([
        'manager' => 'launchd',
        'commands' => [
            [...aHostedRow('Seeding', 'orphaned'), 'missing' => '/usr/local/bin/lemonfiber'],
            // The same key on a row that is not an orphan. It is dropped rather
            // than carried, because the type it would go into cannot hold one
            // at this standing — a row saying it is running and naming a
            // missing program is a contradiction the wire can spell and this
            // app cannot.
            [...aHostedRow('Watching', 'hosted'), 'missing' => '/usr/local/bin/lemonfiber'],
        ],
    ]));

    $rows = [];

    foreach ($running as $one) {
        $rows[] = whatIsGoneIn($one);
    }

    expect($rows)->toBe(['/usr/local/bin/lemonfiber', 'nothing']);
});

it('refuses an envelope whose payload is not a payload at all', function (): void {
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([])))
        ->toThrow(HostingIsUnreadable::class, 'manager');
});

it('refuses a listing that will not say what keeps it running', function (): void {
    // Refused rather than defaulted to `unsupported`. The contract's own
    // default is the stack's to apply; this app applying it too would turn a
    // field that failed to arrive into a platform claim.
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying(['commands' => []])))
        ->toThrow(HostingIsUnreadable::class, 'manager')
        ->and(fn(): WhatRunsUnattended => Hosts::in(hostingSaying(['manager' => 7, 'commands' => []])))
        ->toThrow(HostingIsUnreadable::class, 'manager');
});

it('a manager this app does not recognise names what it does read', function (): void {
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
        'manager' => 'runit',
        'commands' => [],
    ])))->toThrow(HostingIsUnreadable::class, '`launchd`, `systemd`, `unsupported`');
});

it('refuses a listing with no commands key, and one whose commands are not commands', function (): void {
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying(['manager' => 'launchd'])))
        ->toThrow(HostingIsUnreadable::class, 'commands')
        ->and(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
            'manager' => 'launchd',
            'commands' => 'all of them',
        ])))->toThrow(HostingIsUnreadable::class, 'commands');
});

it('names the position of the row it refused rather than always the first', function (): void {
    // A refusal naming row 0 about a fault in row 1 sends somebody to read the
    // wrong row, which on a listing of nine is most of the work.
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
        'manager' => 'launchd',
        'commands' => [aHostedRow('Watching', 'hosted'), 'not a row at all'],
    ])))->toThrow(HostingIsUnreadable::class, 'Command 1');
});

it('refuses a row missing any of the three words it must carry', function (): void {
    foreach (['name', 'command', 'guarantees'] as $field) {
        $row = aHostedRow('Watching', 'hosted');
        unset($row[$field]);

        expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
            'manager' => 'launchd',
            'commands' => [$row],
        ])))->toThrow(HostingIsUnreadable::class, $field);
    }
});

it('refuses a row whose word is there and blank, which is the same fault', function (): void {
    // A blank reaches a screen as a row with a control and no label, which is
    // the shape the type one layer down refuses outright.
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
        'manager' => 'launchd',
        'commands' => [[...aHostedRow('Watching', 'hosted'), 'guarantees' => '   ']],
    ])))->toThrow(HostingIsUnreadable::class, 'guarantees');
});

it('N16-R6 — refuses an orphan that names no program', function (): void {
    // The one row that carries extra information is not the one row allowed to
    // arrive without it: *installed against a program that is gone*, naming no
    // program, tells an operator less than the standing alone already did.
    $row = aHostedRow('Seeding', 'orphaned');

    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
        'manager' => 'launchd',
        'commands' => [$row],
    ])))->toThrow(HostingIsUnreadable::class, 'missing');
});

it('N16-R5 — refuses an unsupported machine that says nothing to do instead', function (): void {
    // *Not available here* with no sentence beside it is the empty box that
    // reads as *off*. Both shapes: the field absent, and the field blank.
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
        'manager' => 'unsupported',
        'commands' => [],
    ])))->toThrow(HostingIsUnreadable::class, 'what to do instead')
        ->and(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
            'manager' => 'unsupported',
            'instruction' => '   ',
            'commands' => [],
        ])))->toThrow(HostingIsUnreadable::class, 'what to do instead');
});

it('a standing this app does not recognise names what it does read', function (): void {
    expect(fn(): WhatRunsUnattended => Hosts::in(hostingSaying([
        'manager' => 'launchd',
        'commands' => [aHostedRow('Watching', 'kept-somehow')],
    ])))->toThrow(
        HostingIsUnreadable::class,
        implode(', ', array_map(
            static fn(HowItIsHosted $standing): string => sprintf('`%s`', $standing->value),
            HowItIsHosted::cases(),
        )),
    );
});

it('stands in for a machine with a payload the contract would accept', function (): void {
    // The valid body, judged by the contract rather than by whoever wrote the
    // reader — `G12`. The refusals above are deliberately not bodies a stack
    // sends, which is the whole of what they test; this is the one that says
    // the shape they are departing from is real.
    expect(WhatTheContractAccepts::complaintsAbout('HostingEnvelope', [
        'api_version' => 1,
        'kind' => 'hosting',
        'data' => [
            'manager' => 'launchd',
            'commands' => [aHostedRow('Watching', 'hosted')],
        ],
    ]))->toBe([], "The payload this suite reads is not one a stack would send.\n");
});
