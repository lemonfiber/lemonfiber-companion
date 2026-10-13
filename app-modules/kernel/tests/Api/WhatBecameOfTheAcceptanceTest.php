<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatBecameOfTheAcceptance;
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereAChoiceStands;
use Modules\Kernel\Api\WhoPutItThere;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

function aFindingThatSays(Conclusion $conclusion, WhatTheCheckSaid $said): Finding
{
    return Finding::of(Check::of('vpn.contained'), Category::Vpn, 'Torrents run inside a VPN', $conclusion, $said, WhoPutItThere::bundled());
}

function aCheckThatWentWrong(Standing $standing): WhatTheCheckSaid
{
    return WhatTheCheckSaid::wentWrong(Code::of('VPN-9'), 'Your address is visible.', Remedies::none(), Severity::Warning, $standing, WhatItSaysUnderneath::none());
}

function whichArmAcceptingTook(WhatBecameOfTheAcceptance $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway:%s', $job->shown())),
        answered: static fn(Report $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered:%d', $report->findings()->count())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries what each holds', function (): void {
    $report = Report::of(Overall::Degraded, Findings::of(aFindingThatSays(Conclusion::Warned, aCheckThatWentWrong(Standing::Suppressed))));

    expect(whichArmAcceptingTook(WhatBecameOfTheAcceptance::underway(Job::named('a-job'))))->toBe('underway:a-job')
        ->and(whichArmAcceptingTook(WhatBecameOfTheAcceptance::answered($report)))->toBe('answered:1')
        ->and(whichArmAcceptingTook(WhatBecameOfTheAcceptance::ended()))->toBe('ended')
        ->and(whichArmAcceptingTook(WhatBecameOfTheAcceptance::refused(ARefusalInItsWords::said('Nothing in this run warns about vpn', '', WhatTheRefusalNamed::nothing()))))->toBe('refused:Nothing in this run warns about vpn')
        ->and(whichArmAcceptingTook(WhatBecameOfTheAcceptance::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});

it('reads a warning as a choice, answered once suppressed, and anything else as no choice', function (Conclusion $conclusion, WhatTheCheckSaid $said, WhereAChoiceStands $stands): void {
    expect(WhereAChoiceStands::of(aFindingThatSays($conclusion, $said)))->toBe($stands);
})->with([
    'a warning' => [Conclusion::Warned, aCheckThatWentWrong(Standing::Guided), WhereAChoiceStands::Unanswered],
    'a warning accepted' => [Conclusion::Warned, aCheckThatWentWrong(Standing::Suppressed), WhereAChoiceStands::Accepted],
    'a failure' => [Conclusion::Failed, aCheckThatWentWrong(Standing::Suppressed), WhereAChoiceStands::NotAChoice],
    'a warning that said nothing' => [Conclusion::Warned, WhatTheCheckSaid::nothingWrong(), WhereAChoiceStands::NotAChoice],
    'a warning that could not say' => [Conclusion::Warned, WhatTheCheckSaid::couldNotSay('no answer', Remedies::none()), WhereAChoiceStands::NotAChoice],
]);
