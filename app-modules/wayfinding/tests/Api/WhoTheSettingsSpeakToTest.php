<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Tests\Api;

use function expect;
use function it;

use Modules\Wayfinding\Api\WhoTheSettingsSpeakTo;

it('names the stacks and the readings in the settings\' own words for anyone', function (): void {
    $anyone = WhoTheSettingsSpeakTo::Anyone;

    expect([$anyone->kept(), $anyone->keepFor(), $anyone->keptExplained(), $anyone->theStacks(), $anyone->theirOrder(), $anyone->clearing()])
        ->toBe(['settings.readings', 'settings.keep_readings', 'settings.keep_readings_is', 'settings.stacks', 'settings.stack_order', 'settings.clear_confirm']);
});

it('names the houses and what the app saved in household words for a member', function (): void {
    $member = WhoTheSettingsSpeakTo::AMember;

    expect([$member->kept(), $member->keepFor(), $member->keptExplained(), $member->theStacks(), $member->theirOrder(), $member->clearing()])
        ->toBe(['settings.household.readings', 'settings.household.keep_readings', 'settings.household.keep_readings_is', 'settings.household.stacks', 'settings.household.stack_order', 'settings.household.clear_confirm']);
});
