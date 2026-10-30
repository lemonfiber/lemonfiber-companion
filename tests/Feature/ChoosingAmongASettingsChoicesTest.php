<?php

declare(strict_types=1);

use Modules\Kernel\Api\AChoice;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\ASettingMove;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheChoices;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingMoves;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatAChoiceNeeds;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Screens\HowThisPluginIsSet;
use Tests\Support\AroundThePhone;
use Tests\Support\ASettingChangeAsReported;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The machine whose guard is set. */
function theMachineWithChoices(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('f', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
    );
}

/** A setting of the guard's that gluetun reads, holding this, among these. */
function aGuardSettingAmong(string $name, string $holding, AChoice ...$choices): ASetting
{
    return ASetting::read($name, WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'VARIABLE', ASettingValue::nothing())), ASettingValue::value($holding))
        ->among(TheChoices::these(...$choices));
}

/**
 * The guard's provider and its protocol, each of whose values hangs on the other, and its countries, which take any value.
 *
 * The provider holds mullvad over wireguard. PIA is a provider only over
 * OpenVPN, and OpenVPN a protocol only with PIA, so neither moves alone. IVPN
 * needs a region, which takes any value. Port 1194 is a port only over OpenVPN.
 */
function theGuardsChoices(): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            aGuardSettingAmong(
                'vpn.provider',
                'mullvad',
                AChoice::of('mullvad', WhatAChoiceNeeds::holding('vpn.type', 'wireguard'), open: true),
                AChoice::of('pia', WhatAChoiceNeeds::holding('vpn.type', 'openvpn'), open: false),
                AChoice::of('ivpn', WhatAChoiceNeeds::holding('vpn.region', 'eu'), open: false),
            ),
            aGuardSettingAmong(
                'vpn.type',
                'wireguard',
                AChoice::of('wireguard', WhatAChoiceNeeds::nothing(), open: true),
                AChoice::of('openvpn', WhatAChoiceNeeds::holding('vpn.provider', 'pia'), open: false),
            ),
            aGuardSettingAmong('vpn.legacy', 'retired', AChoice::of('current', WhatAChoiceNeeds::nothing(), open: true)),
            aGuardSettingAmong('vpn.region', 'us'),
            aGuardSettingAmong(
                'vpn.port',
                '51820',
                AChoice::of('51820', WhatAChoiceNeeds::nothing(), open: true),
                AChoice::of('1194', WhatAChoiceNeeds::holding('vpn.type', 'openvpn'), open: false),
            ),
            ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'SERVER_COUNTRIES', ASettingValue::nothing())), ASettingValue::value('Belgium')),
        ),
        TheSecretsRead::these(),
    );
}

/** The refusal of a change that leaves a setting outside its choices. */
function outsideItsChoices(): ARefusalInItsWords
{
    return ARefusalInItsWords::said('vpn.type cannot hold wireguard; its choices are openvpn', '', WhatTheRefusalNamed::nothing(), 'Change vpn.type in the same change');
}

/** What moving the provider and its protocol together came to, the protocol from its default, starting caddy and leaving qbittorrent waiting on a secret. */
function thePairMoved(WhatTheSettingRunDid $did): TheSettingAsChanged
{
    return TheSettingAsChanged::said(
        TheSettingMoves::these(
            ASettingMove::said('vpn.provider', ASettingValue::value('mullvad'), ASettingValue::value('pia'), 'The tunnel drops for a moment'),
            ASettingMove::said('vpn.type', ASettingValue::nothing(), ASettingValue::value('openvpn'), ''),
        ),
        WhatTheChangeReaches::said(
            Services::these(ServiceId::called('gluetun')),
            Services::these(ServiceId::called('gluetun')),
            Services::these(ServiceId::called('caddy')),
            TheServicesWaiting::these(AServiceWaiting::for(ServiceId::called('qbittorrent'), WhatAServiceWaitsFor::aSecret('vpn.user'))),
            '',
        ),
        $did,
        AnOffer::named('settings-set:vpn.provider,vpn.type:9'),
        theGuardsChoices(),
    );
}

/**
 * A stack listing the guard's choices, saying `$rehearsal` of each rehearsal.
 *
 * @param WhatBecameOfTheWork<TheSettingAsChanged> $rehearsal
 */
function aStackHoldingTheGuardsChoices(WhatBecameOfTheWork $rehearsal): AStackThatTunesItsSettings
{
    return AStackThatTunesItsSettings::answering(theGuardsChoices(), $rehearsal, WhatBecameOfTheWork::answered(thePairMoved(WhatTheSettingRunDid::Applied)));
}

/** The guard's settings screen, read. */
function theGuardsChoicesScreen(AStackThatTunesItsSettings $tuning): HowThisPluginIsSet
{
    $stack = theMachineWithChoices();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowThisPluginIsSet(
        $tuning,
        AStackThatExtendsItself::listing(ThePlugins::listed(TheInstalledPlugins::these(APlugin::named('gluetun', '3.40.0', 'Gluetun', WhatVouchesForAPlugin::recorded('gluetun', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these(), ServiceId::called('gluetun'))), ThePluginSources::these(), TheAnswersOutOfContract::these())),
        FrozenClock::at(Instant::atEpochSeconds(1_761_321_600)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => 'gluetun']);
    $screen->answer();

    return $screen;
}

/** The screen once changing the provider to PIA alone was refused, and the refusal drawn. */
function piaAloneRefused(AStackThatTunesItsSettings $tuning): HowThisPluginIsSet
{
    $screen = theGuardsChoicesScreen($tuning);
    $screen->change('vpn.provider');
    $screen->choose('vpn.provider', 'pia');
    $screen->rehearse();
    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);

    return $screen;
}

/**
 * Each change rehearsed, as the stack was asked it.
 *
 * @return list<string>
 */
function whatWasRehearsedOfTheGuard(AStackThatTunesItsSettings $tuning): array
{
    return array_map(ASettingChangeAsReported::said(...), $tuning->rehearsed());
}

it('draws every value a setting lists, each that is not a choice now with what it needs, and offers only the choices', function (): void {
    $screen = theGuardsChoicesScreen(aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices())));
    $screen->change('vpn.provider');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(
        'mullvad',
        'pia',
        'ivpn',
        __('plugins.settings.not_a_choice_now', ['value' => 'pia']),
        __('plugins.settings.needs', ['setting' => 'vpn.type', 'values' => 'openvpn']),
        __('plugins.settings.not_a_choice_now', ['value' => 'ivpn']),
        __('plugins.settings.needs', ['setting' => 'vpn.region', 'values' => 'eu']),
    )
        ->and($drawn->said())->not->toContain(__('plugins.settings.not_a_choice_now', ['value' => 'mullvad']), __('plugins.settings.needs', ['setting' => 'vpn.type', 'values' => 'wireguard']))
        ->and($drawn->offersThatWait())->toContain('pia', 'ivpn')
        ->and($drawn->offersThatWait())->not->toContain('mullvad')
        ->and($drawn->offers())->not->toContain(__('plugins.settings.value'));
});

it('opens a setting with choices on the value it holds where that is one of them, and on none where it is not', function (): void {
    $screen = theGuardsChoicesScreen(aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices())));
    $screen->change('vpn.provider');
    $held = $screen->typed;
    $screen->change('vpn.legacy');

    expect([$held, $screen->typed])->toBe(['mullvad', '']);
});

it('chooses only a value the setting lists, for a setting the open change moves', function (): void {
    $screen = theGuardsChoicesScreen(aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices())));
    $screen->choose('vpn.provider', 'pia');
    $unopened = $screen->typed;
    $screen->change('vpn.provider');
    $screen->choose('vpn.provider', 'nordvpn');
    $unlisted = $screen->typed;
    $screen->choose('vpn.type', 'openvpn');
    $notMoved = $screen->changing?->alongside;
    $screen->choose('vpn.provider', 'pia');

    expect([$unopened, $unlisted, $notMoved, $screen->typed])->toBe(['', 'mullvad', [], 'pia']);
});

it('rehearses nothing for a value the setting does not list', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = theGuardsChoicesScreen($tuning);
    $screen->change('vpn.provider');
    $screen->typed = 'nordvpn';
    $screen->rehearse();

    expect($tuning->rehearsed())->toBe([]);
});

it('offers each setting that hangs together with the one refused and lists its values, in the same change, only once the stack refused it', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = theGuardsChoicesScreen($tuning);
    $screen->change('vpn.provider');
    $screen->choose('vpn.provider', 'pia');
    $before = $screen->toMoveAlongside();
    $screen->rehearse();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($before)->toBe([])
        ->and($screen->toMoveAlongside())->toBe(['vpn.type'])
        ->and($drawn->said())->toContain('vpn.type cannot hold wireguard; its choices are openvpn')
        ->and($drawn->offers())->toContain(__('plugins.settings.also_move', ['setting' => 'vpn.type']))
        ->and($drawn->offers())->not->toContain(__('plugins.settings.also_move', ['setting' => 'vpn.countries']));
});

it('moves an offered setting alongside, choosing among its values with the change counted, and rehearses both in one change', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = piaAloneRefused($tuning);
    $screen->alsoMove('vpn.countries');
    $refusedAlongside = $screen->changing?->alongside;
    $screen->alsoMove('vpn.type');
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->rehearse();
    $withNothingChosen = whatWasRehearsedOfTheGuard($tuning);
    $screen->choose('vpn.type', 'nordvpn');
    $screen->choose('vpn.type', 'openvpn');
    $screen->rehearse();

    expect($refusedAlongside)->toBe([])
        ->and($drawn->said())->toContain(__('plugins.settings.alongside', ['setting' => 'vpn.provider']))
        ->and($drawn->offers())->toContain('openvpn', __('plugins.settings.leave_out'))
        ->and($drawn->offersThatWait())->not->toContain('openvpn')
        ->and($withNothingChosen)->toBe(['settings-set vpn.provider=pia'])
        ->and(whatWasRehearsedOfTheGuard($tuning))->toBe(['settings-set vpn.provider=pia', 'settings-set vpn.provider=pia vpn.type=openvpn']);
});

it('leaves a setting moved alongside out of the change again', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = piaAloneRefused($tuning);
    $screen->alsoMove('vpn.type');
    $screen->choose('vpn.type', 'openvpn');
    $screen->leaveOut('vpn.type');
    $screen->rehearse();

    expect($screen->changing?->alongside)->toBe([])
        ->and(whatWasRehearsedOfTheGuard($tuning))->toBe(['settings-set vpn.provider=pia', 'settings-set vpn.provider=pia']);
});

it('puts every setting moved alongside back to its default with the one put back', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = theGuardsChoicesScreen($tuning);
    $screen->backToDefault('vpn.provider');
    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);
    $screen->rehearse();
    $alone = whatWasRehearsedOfTheGuard($tuning);
    $offered = $screen->toMoveAlongside();
    $screen->alsoMove('vpn.type');
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->choose('vpn.type', 'openvpn');
    $screen->rehearse();

    expect($alone)->toBe(['settings-clear vpn.provider'])
        ->and($offered)->toBe(['vpn.type', 'vpn.region'])
        ->and($drawn->said())->toContain(__('plugins.settings.alongside_back', ['setting' => 'vpn.provider']))
        ->and($drawn->offers())->toContain(__('plugins.settings.rehearse'))
        ->and($screen->changing?->alongside)->toBe(['vpn.type' => ''])
        ->and(whatWasRehearsedOfTheGuard($tuning))->toBe(['settings-clear vpn.provider', 'settings-clear vpn.provider vpn.type']);
});

it('draws every setting the change moves with what it held and costs, and the services it starts and leaves waiting, then agrees to it whole', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::answered(thePairMoved(WhatTheSettingRunDid::Rehearsed)));
    $screen = theGuardsChoicesScreen($tuning);
    $screen->change('vpn.provider');
    $screen->choose('vpn.provider', 'pia');
    $screen->changing = $screen->changing?->alsoMoving('vpn.type');
    $screen->choose('vpn.type', 'openvpn');
    $screen->rehearse();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->agree();

    expect($drawn->said())->toContain(
        __('plugins.settings.moves_to', ['setting' => 'vpn.provider', 'value' => 'pia']),
        __('plugins.settings.was', ['was' => 'mullvad']),
        __('plugins.settings.consequence', ['consequence' => 'The tunnel drops for a moment']),
        __('plugins.settings.moves_to', ['setting' => 'vpn.type', 'value' => 'openvpn']),
        __('plugins.settings.was_default'),
        __('plugins.settings.starts', ['services' => 'caddy']),
        __('plugins.waiting.held_back', ['service' => 'qbittorrent']),
        __('plugins.waiting.secret', ['secret' => 'vpn.user']),
    )
        ->and(array_map(static fn(ASettingChangeAgreed $agreed): string => ASettingChangeAsReported::said($agreed->change()), $tuning->carriedOut()))->toBe(['settings-set vpn.provider=pia vpn.type=openvpn']);
});

it('sends nothing for a setting nobody offered alongside', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = theGuardsChoicesScreen($tuning);
    $screen->change('vpn.provider');
    $screen->alsoMove('vpn.type');

    expect($screen->changing?->alongside)->toBe([])
        ->and($screen->toMoveAlongside())->toBe([]);
});

it('leaves nothing out of a change nobody opened, nor while the stack works on one', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = theGuardsChoicesScreen($tuning);
    $screen->leaveOut('vpn.type');
    $unopened = $screen->changing;
    $screen = piaAloneRefused($tuning);
    $screen->alsoMove('vpn.type');
    $screen->choose('vpn.type', 'openvpn');
    $screen->rehearse();
    $screen->leaveOut('vpn.type');

    expect($unopened)->toBeNull()
        ->and($screen->following)->not->toBeNull()
        ->and($screen->changing?->alongside)->toBe(['vpn.type' => 'openvpn']);
});

it('offers, after a second refusal, what hangs together with a setting moved alongside, and not what is already moved', function (): void {
    $tuning = aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices()));
    $screen = piaAloneRefused($tuning);
    $screen->alsoMove('vpn.type');
    $screen->choose('vpn.type', 'openvpn');
    $screen->rehearse();
    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->toMoveAlongside())->toBe(['vpn.port']);
});

it('drops what the stack said of the change the moment the change is changed', function (): void {
    $answered = theGuardsChoicesScreen(aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::answered(thePairMoved(WhatTheSettingRunDid::Rehearsed))));
    $answered->change('vpn.provider');
    $answered->choose('vpn.provider', 'pia');
    $answered->rehearse();
    $answered->whileItRuns();
    $rehearsed = $answered->howItIsGoing()->change;
    $answered->choose('vpn.provider', 'mullvad');

    $refused = piaAloneRefused(aStackHoldingTheGuardsChoices(WhatBecameOfTheWork::refused(outsideItsChoices())));
    $wasRefused = $refused->howItIsGoing()->refused;
    $refused->alsoMove('vpn.type');
    $afterMovingAlongside = $refused->howItIsGoing()->refused;
    $refused->choose('vpn.type', 'openvpn');
    $refused->rehearse();
    $refused->whileItRuns();
    WhatTheDeviceWouldDraw::by($refused);
    $refusedAgain = $refused->howItIsGoing()->refused;
    $refused->leaveOut('vpn.type');

    expect($rehearsed)->not->toBeNull()
        ->and([$answered->report, $answered->howItIsGoing()->change])->toBe([null, null])
        ->and($wasRefused)->not->toBeNull()
        ->and($afterMovingAlongside)->toBeNull()
        ->and($refusedAgain)->not->toBeNull()
        ->and($refused->howItIsGoing()->refused)->toBeNull();
});
