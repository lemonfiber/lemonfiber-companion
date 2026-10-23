<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Internal;

use function expect;
use function it;

use Lemonfiber\Native\WhenAValueMayBeRead;
use Modules\Vault\Internal\KeptUnder;

it('names a record beneath its key, a dot before each name', function (): void {
    expect(KeptUnder::WorkLeftRunning->beneath('repair', 'a1b2'))->toBe('lemonfiber.left-running.repair.a1b2')
        ->and(KeptUnder::Session->beneath('a1b2'))->toBe('lemonfiber.session.a1b2');
});

it('keeps readable after the first unlock exactly what a wake while away reads, and the rest only while unlocked', function (): void {
    $readable = [];

    foreach (KeptUnder::cases() as $key) {
        $readable[$key->name] = $key->readable();
    }

    expect($readable)->toBe([
        'Session' => WhenAValueMayBeRead::AfterFirstUnlock,
        'SealKeys' => WhenAValueMayBeRead::AfterFirstUnlock,
        'Stacks' => WhenAValueMayBeRead::AfterFirstUnlock,
        'Standings' => WhenAValueMayBeRead::WhileUnlocked,
        'WorkLeftRunning' => WhenAValueMayBeRead::WhileUnlocked,
        'WhereTheOperatorWas' => WhenAValueMayBeRead::WhileUnlocked,
        'Grant' => WhenAValueMayBeRead::WhileUnlocked,
        'PhoneKeysName' => WhenAValueMayBeRead::WhileUnlocked,
        'WhereHearingLeftOff' => WhenAValueMayBeRead::AfterFirstUnlock,
    ]);
});
