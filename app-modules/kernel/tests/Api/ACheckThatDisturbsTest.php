<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\ADisturbanceSaysNothing;
use Modules\Kernel\Api\CheckingWhatDisturbs;
use Modules\Kernel\Api\WhetherCheckingDisturbs;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** A check's words and bound, as one line. */
function everythingTheCheckSays(ACheckThatDisturbs $check): string
{
    return sprintf('%s|%s|%s', $check->disturbs(), $check->narrowedTo(), $check->either(
        bounded: static fn(int $seconds): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d', $seconds)),
        unbounded: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said);
}

it('carries what it disturbs and the check it runs under, trimmed, with its bound or none', function (): void {
    expect(everythingTheCheckSays(ACheckThatDisturbs::boundedTo(' The tunnel goes down ', ' vpn.killswitch ', 30)))->toBe('The tunnel goes down|vpn.killswitch|30')
        ->and(everythingTheCheckSays(ACheckThatDisturbs::boundedTo('The tunnel goes down', 'vpn.killswitch', 0)))->toBe('The tunnel goes down|vpn.killswitch|0')
        ->and(everythingTheCheckSays(ACheckThatDisturbs::unbounded('One query of each indexer', 'indexers')))->toBe('One query of each indexer|indexers|none');
});

it('refuses a blank word, naming it, and a bound below none', function (): void {
    expect(static fn(): ACheckThatDisturbs => ACheckThatDisturbs::unbounded(' ', 'indexers'))->toThrow(ADisturbanceSaysNothing::class, 'its `disturbs` blank')
        ->and(static fn(): ACheckThatDisturbs => ACheckThatDisturbs::boundedTo('The tunnel goes down', '', 30))->toThrow(ADisturbanceSaysNothing::class, 'its `only` blank')
        ->and(static fn(): ACheckThatDisturbs => ACheckThatDisturbs::boundedTo('The tunnel goes down', 'vpn.killswitch', -1))->toThrow(ADisturbanceSaysNothing::class, 'bounded to -1 seconds');
});

it('takes the arm it was made on', function (): void {
    $check = ACheckThatDisturbs::unbounded('One query of each indexer', 'indexers');
    $arm = static fn(WhetherCheckingDisturbs $whether): string => $whether->either(
        so: static fn(ACheckThatDisturbs $so): TheWordCarriedOut => new TheWordCarriedOut($so->narrowedTo()),
        not: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not'),
    )->said;

    expect($arm(WhetherCheckingDisturbs::so($check)))->toBe('indexers')
        ->and($arm(WhetherCheckingDisturbs::not()))->toBe('not');
});

it('asks for a run by lemonfiber\'s name for it', function (): void {
    expect(CheckingWhatDisturbs::Run->asked())->toBe('diagnose');
});
