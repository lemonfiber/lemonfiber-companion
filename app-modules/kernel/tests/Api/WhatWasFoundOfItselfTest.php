<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItWouldBeUpdated;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowThisCopyGotThere;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Kernel\Api\WhatAnUpdateWouldBring;
use Modules\Kernel\Api\WhatIsReleased;
use Modules\Kernel\Api\WhatWasFoundOfItself;
use Modules\Kernel\Api\WhereThisCopyStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

it('a copy that could not be asked about is never one that is up to date', function (): void {
    $fold = static fn(WhatWasFoundOfItself $answer): string => $answer->either(
        found: static fn(ThisCopyOfLemonfiber $copy): TheWordCarriedOut => new TheWordCarriedOut(sprintf('found:%s', $copy->stands()->value)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
    $copy = ThisCopyOfLemonfiber::reported('0.15.0', HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Installer, ''), WhereThisCopyStands::Current, WhatIsReleased::nothing(), '', HowItWouldBeUpdated::notSaid(), WhatAnUpdateWouldBring::said('The program', 'Settings are kept'));

    expect($fold(WhatWasFoundOfItself::found($copy)))->toBe('found:current')
        ->and($fold(WhatWasFoundOfItself::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
