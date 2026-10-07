<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;

it('is asked for by the name lemonfiber gives the action, before and after there is one', function (): void {
    $taking = TakingAnUpdate::offeredBy(Upkeep::reported(
        AgainstThePins::UpdatesAvailable,
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    ));

    expect($taking->asked())->toBe('update')
        ->and(TakingAnUpdate::named())->toBe('update');
});
