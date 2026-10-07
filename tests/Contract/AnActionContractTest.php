<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\AnAction;
use Modules\Kernel\Api\AskingThemIn;
use Modules\Kernel\Api\ConnectingADevice;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TakingItOff;
use Modules\Kernel\Api\TakingThemOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhatToChange;
use Modules\Kernel\Api\WhatToDoAboutPairing;
use Modules\Kernel\Api\WhatToDoAboutQuality;
use Modules\Kernel\Api\WhatToDoAboutWiring;
use Modules\Kernel\Api\WhatToDoWithACopy;
use Modules\Kernel\Api\WhatToDoWithADownload;
use Modules\Kernel\Api\WhatToDoWithARun;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Kernel\Api\WhatWasDecided;
use Modules\Sdk\Api\EveryRequestThisAppSends;

// The AnAction contract, run against every case and value that names an action.
//
// One contract over more than two implementations, as `NamesAWireFieldContractTest`
// is: each kind is one. What every one of them promises is a name the stack's
// surface serves the action by, which the registry every request is declared
// in holds, so a stack can be asked whether it offers it before its button is
// drawn.

/**
 * One of every action this app names, a closed set by every case and a single
 * action by one value of it.
 *
 * @return list<AnAction>
 */
function everyActionNamed(): array
{
    return [
        ...AskingThemIn::cases(), ...ConnectingADevice::cases(), ...HandingOver::cases(), ...MovingInBy::cases(),
        ...TakingItOff::cases(), ...TakingThemOut::cases(), ...WhatToChange::cases(), ...WhatToDoAboutPairing::cases(),
        ...WhatToDoAboutQuality::cases(), ...WhatToDoAboutWiring::cases(), ...WhatToDoWithACopy::cases(),
        ...WhatToDoWithADownload::cases(), ...WhatToDoWithARun::cases(), ...WhatToDoWithIt::cases(), ...WhatWasDecided::cases(),
        ABundleAsked::described(HowManyLines::asMuchAsAPhoneShows(), WhatFilenamesShow::Replaced, SettingsToReveal::none()),
        AGuardAskedFor::of(Forms::these(Form::called('library'))),
        TakingAnUpdate::offeredBy(Upkeep::reported(
            AgainstThePins::UpdatesAvailable,
            Releases::none(),
            Services::these(ServiceId::called('jellyfin')),
            Services::none(),
            HowServicesTookIt::none(),
            HowTheNotesStand::Current,
            TheStackEdits::none(),
        )),
        WhatToWalk::called(''),
    ];
}

it('names every action as the stack\'s surface does: words in lower case joined by hyphens', function (): void {
    $unspelled = [];

    foreach (everyActionNamed() as $action) {
        if (preg_match('/\A[a-z]+(-[a-z]+)*\z/', $action->asked()) !== 1) {
            $unspelled[] = sprintf('%s says %s', $action::class, $action->asked());
        }
    }

    expect($unspelled)->toBe([]);
});

it('names every action at a path the registry of every request holds', function (): void {
    $declared = [];

    foreach (EveryRequestThisAppSends::listed() as $path) {
        $declared[] = $path->named();
    }

    $undeclared = [];

    foreach (everyActionNamed() as $action) {
        if (! in_array(Api::action($action->asked()), $declared, strict: true)) {
            $undeclared[] = $action->asked();
        }
    }

    expect($undeclared)->toBe([]);
});

it('names a single action the same way before there is one to ask for', function (): void {
    expect(ABundleAsked::named())->toBe(ABundleAsked::described(HowManyLines::asMuchAsAPhoneShows(), WhatFilenamesShow::Replaced, SettingsToReveal::none())->asked())
        ->and(AGuardAskedFor::named())->toBe(AGuardAskedFor::of(Forms::these(Form::called('library')))->asked())
        ->and(WhatToWalk::named())->toBe(WhatToWalk::called('')->asked());
});

it('is every kind the kernel has that names an action', function (): void {
    $named = array_values(array_unique(array_map(static fn(AnAction $action): string => $action::class, everyActionNamed())));
    $found = [];

    $files = glob(sprintf('%s/*.php', dirname((string) new ReflectionClass(AnAction::class)->getFileName())));

    foreach ($files === false ? [] : $files as $file) {
        $class = sprintf('Modules\Kernel\Api\%s', basename($file, '.php'));

        if ($class !== AnAction::class && is_subclass_of($class, AnAction::class)) {
            $found[] = $class;
        }
    }

    sort($named);
    sort($found);

    expect($named)->toBe($found);
});
