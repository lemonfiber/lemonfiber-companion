<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AnInstallAStartOffers;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheInstallsAStartOffers;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\WhatBecameOfTheMovesInPlace;
use Modules\Kernel\Api\WhatElseAVerbSaid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\TheThingScreenOfTheShed;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;

/** A start that wrote and started nothing for want of a yes to these installs, under the offer named. */
function aStartWaitingOnAYes(AnOffer $offer, AnInstallAStartOffers ...$installs): WhatTheVerbCameTo
{
    return WhatTheVerbCameTo::unstarted(WhetherItWasRehearsed::CarriedOut, WhereTheServicesEndedUp::of(), TheServicesLeftOut::of(), ThePortsHeld::of(), TheStackEdits::these())
        ->offering($offer)
        ->saying(WhatElseAVerbSaid::these(WhatBecameOfTheMovesInPlace::none(), TheInstallsAStartOffers::these(...$installs), PluginLines::none(), ''));
}

/** A stack declaring the library form, whose every start comes to `$report`. */
function aStackWhoseStartsComeTo(WhatTheVerbCameTo $report): AStackThatSupervises
{
    return AStackThatSupervises::with(WhatAMachineRuns::twoThings())
        ->declaring(WhatAMachineRuns::libraryAndFull())
        ->whichCameTo(HowAgreedWorkIsGoing::done($report));
}

/** Start the library form from its screen, and the screen as the second frame left it. */
function startingTheLibrary(AStackThatSupervises $stack): WhatToDoWithThis
{
    $screen = TheThingScreenOfTheShed::theScreen($stack, 'library');
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->whileItSettles();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    return $screen;
}

/**
 * What each start told the stack carried as its yes.
 *
 * @return list<string>
 */
function theYesesEachStartCarried(AStackThatSupervises $stack): array
{
    return array_map(
        static fn(AgreedTo $told): string => sprintf('%s %s %s', $told->doing()->value, $told->named(), $told->offer()->either(
            named: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut($named),
            none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no-offer'),
        )->said),
        $stack->whatItWasToldToDo(),
    );
}

it('offers a yes to the installs a start of a form named, and the yes carries their offer back', function (): void {
    $stack = aStackWhoseStartsComeTo(aStartWaitingOnAYes(AnOffer::named('the-installs-offer'), AnInstallAStartOffers::of('plugin-jellyfin', '')));
    $screen = startingTheLibrary($stack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('health.installs.offline'))
        ->and($drawn->offers())->toContain(__('health.installs.install_and_start'));

    $screen->installAndStart();

    expect(theYesesEachStartCarried($stack))->toBe(['start library no-offer', 'start library the-installs-offer']);
});

it('offers no yes where an install a start named could not be read, and says so beside it', function (): void {
    $stack = aStackWhoseStartsComeTo(aStartWaitingOnAYes(AnOffer::none(), AnInstallAStartOffers::of('plugin-sonarr', 'Its manifest could not be read')));
    $screen = startingTheLibrary($stack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('health.installs.unreadable'), 'Its manifest could not be read')
        ->and($drawn->offers())->not->toContain(__('health.installs.install_and_start'));

    $screen->installAndStart();

    expect(theYesesEachStartCarried($stack))->toBe(['start library no-offer']);
});

it('says a start of one service is not where its installs are agreed, and offers no yes there', function (): void {
    $stack = TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(aStartWaitingOnAYes(AnOffer::named('the-installs-offer'), AnInstallAStartOffers::of('plugin-jellyfin', ''))));
    $screen = TheThingScreenOfTheShed::theScreen($stack);
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew($screen);

    expect($drawn)->toContain(__('health.installs.by_its_form'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('health.installs.install_and_start'));

    $screen->installAndStart();

    expect($stack->whatItWasToldToDo())->toHaveCount(1);
});

it('starts the form again with no yes where the installs it offered have moved, so it names them as they are now', function (): void {
    $moved = ARefusalInItsWords::said('The installs on offer changed since they were read', '', WhatTheRefusalNamed::nothing(), 'Start it again to read them afresh');
    $stack = aStackWhoseStartsComeTo(aStartWaitingOnAYes(AnOffer::named('the-installs-offer'), AnInstallAStartOffers::of('plugin-jellyfin', '')));
    $screen = startingTheLibrary($stack);
    $stack->fromNowOnComingTo(HowAgreedWorkIsGoing::moved($moved));

    $screen->installAndStart();
    $screen->whileItSettles();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect(array_slice(theYesesEachStartCarried($stack), 0, 3))->toBe(['start library no-offer', 'start library the-installs-offer', 'start library no-offer']);
});
