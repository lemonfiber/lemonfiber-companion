<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ALift;
use Modules\Kernel\Api\ALineChange;
use Modules\Kernel\Api\APartOfTheLine;
use Modules\Kernel\Api\APartWritten;
use Modules\Kernel\Api\LineChangeAsksNothing;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/**
 * Every part a change wrote, as `part=text`.
 *
 * @return list<string>
 */
function everyPartTheLineChangeWrote(ALineChange $change): array
{
    $written = [];

    foreach ($change as $part) {
        $written[] = sprintf('%s=%s', $part->part()->value, $part->text());
    }

    return $written;
}

/** The lift a change asks for, as a line. */
function theLiftAChangeAsks(ALineChange $change): string
{
    return $change->lift()->either(
        for: static fn(int $minutes): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d minutes', $minutes)),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no lift'),
    )->said;
}

it('carries each part written, less the space around it, in the order the line is read, and leaves the rest', function (): void {
    $change = ALineChange::asked(ALift::none(), down: ' 50% ', active: '07:00-23:00', exceeded: 'pause');
    $every = ALineChange::asked(ALift::forMinutes(60), '50%', '1MiB', '07:00-23:00', '60MiB/6MiB', '500GiB', 'throttle');

    expect(everyPartTheLineChangeWrote($change))->toBe(['down=50%', 'active=07:00-23:00', 'exceeded=pause'])
        ->and(theLiftAChangeAsks($change))->toBe('no lift')
        ->and(everyPartTheLineChangeWrote($every))->toBe(['down=50%', 'up=1MiB', 'active=07:00-23:00', 'line=60MiB/6MiB', 'cap=500GiB', 'exceeded=throttle'])
        ->and(theLiftAChangeAsks($every))->toBe('60 minutes');
});

it('asks a lift alone, even one of no minutes, for the stack to judge', function (): void {
    $change = ALineChange::asked(ALift::forMinutes(0));

    expect(everyPartTheLineChangeWrote($change))->toBe([])
        ->and(theLiftAChangeAsks($change))->toBe('0 minutes');
});

it('refuses a change that asks nothing', function (): void {
    expect(static fn(): ALineChange => ALineChange::asked(ALift::none(), down: ' ', cap: ''))
        ->toThrow(LineChangeAsksNothing::class, 'every part left as it is');
});

it('refuses a lift below nothing, naming it', function (): void {
    expect(static fn(): ALift => ALift::forMinutes(-5))
        ->toThrow(LineChangeAsksNothing::class, '-5 minutes');
});

it('refuses a part written as nothing, naming it', function (): void {
    expect(static fn(): APartWritten => APartWritten::of(APartOfTheLine::Cap, ' '))
        ->toThrow(LineChangeAsksNothing::class, '`cap`');
});

it('says whether a lift is asked for', function (): void {
    expect(ALift::none()->isNone())->toBeTrue()
        ->and(ALift::forMinutes(15)->isNone())->toBeFalse();
});
