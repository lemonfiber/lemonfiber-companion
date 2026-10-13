<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheAllowanceChange;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

function whichArmTheAllowanceChangeTook(WhatBecameOfTheAllowanceChange $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway:%s', $job->shown())),
        answered: static function (TheAllowanceAsChanged $report): TheWordCarriedOut {
            $said = [];

            foreach ($report->whatItSays() as $sentence) {
                $said[] = $sentence->shown();
            }

            return new TheWordCarriedOut(sprintf('answered:%s:%s', $report->did()->value, implode('|', $said)));
        },
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries what each holds', function (): void {
    $report = TheAllowanceAsChanged::said(WhetherItWasRehearsed::Rehearsed, Sentences::of(Sentence::of('the household now waits for you'), Sentence::of('robin has never asked here')));

    expect(whichArmTheAllowanceChangeTook(WhatBecameOfTheAllowanceChange::underway(Job::named('a-job'))))->toBe('underway:a-job')
        ->and(whichArmTheAllowanceChangeTook(WhatBecameOfTheAllowanceChange::answered($report)))->toBe('answered:rehearsed:the household now waits for you|robin has never asked here')
        ->and(whichArmTheAllowanceChangeTook(WhatBecameOfTheAllowanceChange::ended()))->toBe('ended')
        ->and(whichArmTheAllowanceChangeTook(WhatBecameOfTheAllowanceChange::refused(ARefusalInItsWords::said('nobody called sam is in the household', '', WhatTheRefusalNamed::nothing()))))->toBe('refused:nobody called sam is in the household')
        ->and(whichArmTheAllowanceChangeTook(WhatBecameOfTheAllowanceChange::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});
