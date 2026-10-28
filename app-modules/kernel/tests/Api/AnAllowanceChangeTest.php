<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AllowanceChangeAsksNothing;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ARequestLimit;
use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\WhoAnAllowanceIsFor;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

function whatAnAllowanceChangeAsks(AnAllowanceChange $change): string
{
    return sprintf(
        '%s %s %s',
        $change->for()->either(
            everybody: static fn(): TheWordCarriedOut => new TheWordCarriedOut('everybody'),
            somebody: static fn(SomebodyInTheHousehold $who): TheWordCarriedOut => new TheWordCarriedOut($who->name()),
        )->said,
        $change->policy(
            chosen: static fn(HowTheHouseholdAsks $policy): TheWordCarriedOut => new TheWordCarriedOut($policy->value),
            asItIs: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'),
        )->said,
        $change->limit(
            chosen: static fn(ARequestLimit $limit): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d/%d', $limit->requests(), $limit->days())),
            asItIs: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'),
        )->said,
    );
}

it('carries whom it is about and only what was named', function (): void {
    $alex = WhoAnAllowanceIsFor::somebody(SomebodyInTheHousehold::called('alex'));

    expect(whatAnAllowanceChangeAsks(AnAllowanceChange::ofPolicy(WhoAnAllowanceIsFor::everybody(), HowTheHouseholdAsks::Trusted)))->toBe('everybody trusted -')
        ->and(whatAnAllowanceChangeAsks(AnAllowanceChange::ofLimit($alex, ARequestLimit::of(5, 7))))->toBe('alex - 5/7')
        ->and(whatAnAllowanceChangeAsks(AnAllowanceChange::ofLimit($alex, ARequestLimit::of(5, 0))))->toBe('alex - 5/0')
        ->and(whatAnAllowanceChangeAsks(AnAllowanceChange::ofBoth($alex, HowTheHouseholdAsks::EverythingWaits, ARequestLimit::of(0, 30))))->toBe('alex everything-waits 0/30');
});

it('refuses a limit below none', function (int $requests, int $days, string $said): void {
    expect(static fn(): ARequestLimit => ARequestLimit::of($requests, $days))->toThrow(AllowanceChangeAsksNothing::class, $said);
})->with([
    'requests' => [-1, 7, '-1 requests'],
    'days' => [5, -2, '-2 days'],
]);

it('says each policy with its own catalogue key', function (): void {
    expect(HowTheHouseholdAsks::WithinALimit->saidOnTheScreen())->toBe('stacks.allowance_change.policy.within-a-limit');
});
