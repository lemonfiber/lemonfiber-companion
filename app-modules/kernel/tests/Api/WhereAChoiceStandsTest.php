<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhereAChoiceStands;
use Modules\Kernel\Api\WhoPutItThere;

function aFindingThatSays(Conclusion $conclusion, WhatTheCheckSaid $said): Finding
{
    return Finding::of(Check::of('vpn.contained'), Category::Vpn, 'Torrents run inside a VPN', $conclusion, $said, WhoPutItThere::bundled());
}

function aCheckThatWentWrong(Standing $standing): WhatTheCheckSaid
{
    return WhatTheCheckSaid::wentWrong(Code::of('VPN-9'), 'Your address is visible.', Remedies::none(), Severity::Warning, $standing, WhatItSaysUnderneath::none());
}

it('reads a warning as a choice, answered once suppressed, and anything else as no choice', function (Conclusion $conclusion, WhatTheCheckSaid $said, WhereAChoiceStands $stands): void {
    expect(WhereAChoiceStands::of(aFindingThatSays($conclusion, $said)))->toBe($stands);
})->with([
    'a warning' => [Conclusion::Warned, aCheckThatWentWrong(Standing::Guided), WhereAChoiceStands::Unanswered],
    'a warning accepted' => [Conclusion::Warned, aCheckThatWentWrong(Standing::Suppressed), WhereAChoiceStands::Accepted],
    'a failure' => [Conclusion::Failed, aCheckThatWentWrong(Standing::Suppressed), WhereAChoiceStands::NotAChoice],
    'a warning that said nothing' => [Conclusion::Warned, WhatTheCheckSaid::nothingWrong(), WhereAChoiceStands::NotAChoice],
    'a warning that could not say' => [Conclusion::Warned, WhatTheCheckSaid::couldNotSay('no answer', Remedies::none()), WhereAChoiceStands::NotAChoice],
]);
