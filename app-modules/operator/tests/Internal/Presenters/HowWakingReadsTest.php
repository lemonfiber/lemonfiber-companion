<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Connection\Api\WhyWakingStayedOff;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AWakeFingerprint;
use Modules\Kernel\Api\AWakeRegistered;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\AWakingSecret;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatThisPhoneWakesThrough;
use Modules\Kernel\Api\WhoWakesThisPhone;
use Modules\Kernel\Api\WhyNoEndpoint;
use Modules\Operator\Internal\Presenters\HowWakingReads;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\ALinkWithItsCode;
use Modules\Operator\Internal\ViewModels\ASettingAsShown;
use Modules\Operator\Internal\ViewModels\HowWakingIsShown;
use Modules\Operator\Internal\ViewModels\WhatWakingSaid;

use function sprintf;
use function str_repeat;

function ntfyToWakeThrough(): ADistributor
{
    return ADistributor::of('io.heckel.ntfy', 'ntfy');
}

function nextPushToWakeThrough(): ADistributor
{
    return ADistributor::of('org.unifiedpush.distributor.nextpush', 'NextPush');
}

function theWakeTheSwitchShows(): AWakeRegistered
{
    return new AWakeRegistered(
        APushName::of('galaxy-a51-7f3a'),
        AWakeFingerprint::of('2fb15b007051cfa9'),
        AWakingKey::of(sprintf("\x04%s", str_repeat("\x01", 64))),
        AWakingSecret::of(str_repeat('s', 16)),
    );
}

function whereNtfyIsFound(): ALinkWithItsCode
{
    return new ALinkWithItsCode('https://ntfy.sh', [[true]]);
}

function whoWakesAmong(string $chosen, ADistributor ...$installed): WhoWakesThisPhone
{
    return WhoWakesThisPhone::among(WhatThisPhoneWakesThrough::installed(TheDistributors::of(...$installed)), $chosen);
}

function noChoiceDrawn(): ASettingAsShown
{
    return new ASettingAsShown(said: '', offered: []);
}

it('draws nothing where waking is not drawn', function (): void {
    expect(new HowWakingReads()->notDrawn())->toEqual(new HowWakingIsShown(drawn: false, on: false, usable: false, line: '', distributor: '', choice: noChoiceDrawn(), install: null))
        ->and(new HowWakingReads()->forTheStack(WhoWakesThisPhone::among(WhatThisPhoneWakesThrough::notHere(), ''), HowThisPhoneIsWoken::off(), whereNtfyIsFound()))
        ->toEqual(new HowWakingIsShown(drawn: false, on: false, usable: false, line: '', distributor: '', choice: noChoiceDrawn(), install: null));
});

it('draws waking on through the distributor that wakes it, offering no choice', function (): void {
    expect(new HowWakingReads()->forTheStack(whoWakesAmong('io.heckel.ntfy', ntfyToWakeThrough(), nextPushToWakeThrough()), HowThisPhoneIsWoken::on(ntfyToWakeThrough(), theWakeTheSwitchShows()), whereNtfyIsFound()))
        ->toEqual(new HowWakingIsShown(drawn: true, on: true, usable: true, line: 'waking.on', distributor: 'ntfy', choice: noChoiceDrawn(), install: null));
});

it('draws waking off, or owed its removal, by who would wake the phone', function (HowThisPhoneIsWoken $waking, WhoWakesThisPhone $who, HowWakingIsShown $shown): void {
    expect(new HowWakingReads()->forTheStack($who, $waking, whereNtfyIsFound()))->toEqual($shown);
})->with([
    'off, one distributor' => [HowThisPhoneIsWoken::off(), whoWakesAmong('', ntfyToWakeThrough()), new HowWakingIsShown(drawn: true, on: false, usable: true, line: 'waking.off', distributor: 'ntfy', choice: noChoiceDrawn(), install: null)],
    'owed, one distributor' => [HowThisPhoneIsWoken::owed(APushName::of('galaxy-a51-7f3a')), whoWakesAmong('', ntfyToWakeThrough()), new HowWakingIsShown(drawn: true, on: false, usable: true, line: 'waking.owed', distributor: 'ntfy', choice: noChoiceDrawn(), install: null)],
    'off, several to choose among' => [HowThisPhoneIsWoken::off(), whoWakesAmong('', ntfyToWakeThrough(), nextPushToWakeThrough()), new HowWakingIsShown(drawn: true, on: false, usable: true, line: 'waking.off_choosing', distributor: '', choice: new ASettingAsShown(said: '', offered: [
        new AChoiceOfASettingAsShown(said: 'ntfy', word: 'io.heckel.ntfy', chosen: false),
        new AChoiceOfASettingAsShown(said: 'NextPush', word: 'org.unifiedpush.distributor.nextpush', chosen: false),
    ]), install: null)],
    'owed, several to choose among' => [HowThisPhoneIsWoken::owed(APushName::of('galaxy-a51-7f3a')), whoWakesAmong('', ntfyToWakeThrough(), nextPushToWakeThrough()), new HowWakingIsShown(drawn: true, on: false, usable: true, line: 'waking.owed', distributor: '', choice: new ASettingAsShown(said: '', offered: [
        new AChoiceOfASettingAsShown(said: 'ntfy', word: 'io.heckel.ntfy', chosen: false),
        new AChoiceOfASettingAsShown(said: 'NextPush', word: 'org.unifiedpush.distributor.nextpush', chosen: false),
    ]), install: null)],
    'off, none installed' => [HowThisPhoneIsWoken::off(), whoWakesAmong(''), new HowWakingIsShown(drawn: true, on: false, usable: false, line: 'waking.none_installed', distributor: '', choice: noChoiceDrawn(), install: whereNtfyIsFound())],
    'owed, none installed' => [HowThisPhoneIsWoken::owed(APushName::of('galaxy-a51-7f3a')), whoWakesAmong(''), new HowWakingIsShown(drawn: true, on: false, usable: false, line: 'waking.owed', distributor: '', choice: noChoiceDrawn(), install: whereNtfyIsFound())],
]);

it('offers the distributors to choose among on the app\'s settings, the one that wakes the phone marked and said', function (): void {
    expect(new HowWakingReads()->choiceAmong(whoWakesAmong('org.unifiedpush.distributor.nextpush', ntfyToWakeThrough(), nextPushToWakeThrough())))->toEqual(new ASettingAsShown(said: 'NextPush', offered: [
        new AChoiceOfASettingAsShown(said: 'ntfy', word: 'io.heckel.ntfy', chosen: false),
        new AChoiceOfASettingAsShown(said: 'NextPush', word: 'org.unifiedpush.distributor.nextpush', chosen: true),
    ]))
        ->and(new HowWakingReads()->choiceAmong(whoWakesAmong('', ntfyToWakeThrough(), nextPushToWakeThrough())))->toEqual(new ASettingAsShown(said: '', offered: [
            new AChoiceOfASettingAsShown(said: 'ntfy', word: 'io.heckel.ntfy', chosen: false),
            new AChoiceOfASettingAsShown(said: 'NextPush', word: 'org.unifiedpush.distributor.nextpush', chosen: false),
        ]))
        ->and(new HowWakingReads()->choiceAmong(whoWakesAmong('io.heckel.ntfy', ntfyToWakeThrough())))->toEqual(noChoiceDrawn())
        ->and(new HowWakingReads()->choiceAmong(whoWakesAmong('')))->toEqual(noChoiceDrawn())
        ->and(new HowWakingReads()->choiceAmong(WhoWakesThisPhone::among(WhatThisPhoneWakesThrough::notHere(), '')))->toEqual(noChoiceDrawn());
});

it('says why waking stayed off in the stack\'s words where it refused, and in this app\'s everywhere else', function (WhyWakingStayedOff $why, WhatWakingSaid $said): void {
    expect(new HowWakingReads()->stayedOff($why))->toEqual($said);
})->with([
    'no key name' => [WhyWakingStayedOff::noKeyName(), new WhatWakingSaid(words: '', line: 'waking.no_key_name', with: [])],
    'no endpoint' => [WhyWakingStayedOff::noEndpoint(WhyNoEndpoint::Dropped), new WhatWakingSaid(words: '', line: WhyNoEndpoint::Dropped->saidOnTheScreen(), with: [])],
    'refused' => [WhyWakingStayedOff::refused(ARefusalInItsWords::said('That address is not https', '', WhatTheRefusalNamed::nothing())), new WhatWakingSaid(words: 'That address is not https', line: '', with: [])],
    'signed out' => [WhyWakingStayedOff::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), new WhatWakingSaid(words: '', line: 'connection.session_has_ended', with: [])],
    'not kept' => [WhyWakingStayedOff::notKept(), new WhatWakingSaid(words: '', line: 'waking.not_kept', with: [])],
]);

it('says what stood in the way in this app\'s words where the stack was not reached', function (): void {
    $said = new HowWakingReads()->stayedOff(WhyWakingStayedOff::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect([$said->words, $said->line])->toBe(['', KindOfObstacle::StackDidNotAnswer->said()]);
});
