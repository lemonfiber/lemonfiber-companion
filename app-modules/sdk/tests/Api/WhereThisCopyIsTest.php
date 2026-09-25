<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Sdk\Api\SelfUpdateIsUnreadable;
use Modules\Sdk\Api\WhereThisCopyIs;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `self-update` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function selfUpdateSaying(mixed $data): Envelope
{
    return new Envelope(1, 'self-update', $data);
}

/**
 * A copy the installer put there, with nothing optional said.
 *
 * @return array<string, mixed>
 */
function aPlainCopy(): array
{
    return [
        'standing' => 'current',
        'running' => '0.15.0',
        'installed' => 'installer',
        'afterwards' => 'Settings are kept',
        'carries' => 'The program',
    ];
}

/** One line carried out of an arm. */
final readonly class WhatTheCopyCarried
{
    public function __construct(public string $said) {}
}

/**
 * Everything a payload says, as one line.
 *
 * @param array<string, mixed> $data
 */
function theCopyRead(array $data): string
{
    $copy = WhereThisCopyIs::in(selfUpdateSaying($data));

    return sprintf(
        '%s|%s|%s|%s|%s|%s|%s|%s|%s|%s',
        $copy->running(),
        $copy->gotThere()->installed()->value,
        $copy->gotThere()->owner(),
        $copy->stands()->value,
        $copy->released()->version(),
        $copy->released()->changed(),
        $copy->untold(),
        $copy->updatedBy()->either(
            byRunning: static fn(string $command): WhatTheCopyCarried => new WhatTheCopyCarried(sprintf('run %s', $command)),
            instead: static fn(string $why): WhatTheCopyCarried => new WhatTheCopyCarried(sprintf('instead %s', $why)),
            notSaid: static fn(): WhatTheCopyCarried => new WhatTheCopyCarried('-'),
        )->said,
        $copy->brings()->carries(),
        $copy->brings()->afterwards(),
    );
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SelfUpdateEnvelope', ['api_version' => 1, 'kind' => 'self-update', 'data' => aPlainCopy()]))->toBe([]);
});

it('reads a plain copy, with every optional sentence empty', function (): void {
    expect(theCopyRead(aPlainCopy()))->toBe('0.15.0|installer||current||||-|The program|Settings are kept');
});

it('reads optional sentences where they are said, and absent or null as empty', function (): void {
    expect(theCopyRead([...aPlainCopy(), 'owner' => 'brew', 'offered' => '0.16.0', 'changed' => '- Plugins', 'untold' => 'Checked an hour ago']))->toBe('0.15.0|installer|brew|current|0.16.0|- Plugins|Checked an hour ago|-|The program|Settings are kept')
        ->and(theCopyRead([...aPlainCopy(), 'owner' => null, 'offered' => null, 'changed' => null, 'untold' => null]))->toBe('0.15.0|installer||current||||-|The program|Settings are kept');
});

it('reads the command where there is one, the reason where there is not, and the command where both came', function (): void {
    expect(theCopyRead([...aPlainCopy(), 'command' => 'lemonfiber update self']))->toContain('|run lemonfiber update self|')
        ->and(theCopyRead([...aPlainCopy(), 'instead' => 'A distribution owns it']))->toContain('|instead A distribution owns it|')
        ->and(theCopyRead([...aPlainCopy(), 'command' => 'lemonfiber update self', 'instead' => 'A distribution owns it']))->toContain('|run lemonfiber update self|')
        ->and(theCopyRead([...aPlainCopy(), 'command' => null, 'instead' => null]))->toContain('|-|');
});

it('reads every installation and every standing the contract names', function (string $installed, string $standing): void {
    expect(theCopyRead([...aPlainCopy(), 'installed' => $installed, 'standing' => $standing]))->toContain(sprintf('|%s|', $installed))
        ->and(theCopyRead([...aPlainCopy(), 'installed' => $installed, 'standing' => $standing]))->toContain(sprintf('|%s|', $standing));
})->with([
    ['homebrew', 'update-available'], ['scoop', 'managed-externally'], ['winget', 'check-failed'], ['cargo', 'current'],
    ['distribution', 'current'], ['elsewhere', 'current'], ['untellable', 'check-failed'],
]);

it('refuses a payload with no data', function (): void {
    expect(fn(): ThisCopyOfLemonfiber => WhereThisCopyIs::in(selfUpdateSaying('nothing')))->toThrow(SelfUpdateIsUnreadable::class, '`data`');
});

it('refuses a word it has no case for, naming the field and every word it reads', function (string $field, string $said, string $accepts): void {
    expect(fn(): ThisCopyOfLemonfiber => WhereThisCopyIs::in(selfUpdateSaying([...aPlainCopy(), $field => $said])))
        ->toThrow(SelfUpdateIsUnreadable::class, sprintf('`%s` is `%s`, and this app reads %s', $field, $said, $accepts));
})->with([
    ['installed', 'snap', '`homebrew`, `scoop`, `winget`, `cargo`, `distribution`, `installer`, `elsewhere`, `untellable`.'],
    ['standing', 'fine', '`current`, `update-available`, `managed-externally`, `check-failed`.'],
]);

it('refuses a required sentence that is missing, blank or not text, and an optional one that is blank or not text', function (string $field, mixed $said): void {
    $data = $said === 'absent'
        ? array_diff_key(aPlainCopy(), [$field => true])
        : [...aPlainCopy(), $field => $said];

    expect(fn(): ThisCopyOfLemonfiber => WhereThisCopyIs::in(selfUpdateSaying($data)))->toThrow(SelfUpdateIsUnreadable::class, sprintf('no readable `%s`', $field));
})->with([
    ['running', 'absent'], ['running', ' '], ['installed', 'absent'], ['standing', 7],
    ['carries', 'absent'], ['afterwards', ''],
    ['owner', ' '], ['offered', 7], ['changed', ' '], ['untold', ' '], ['command', ' '], ['instead', false],
]);
