<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\SettingsClearAction;
use Lemonfiber\Sdk\Generated\SettingsSetAction;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\ChangingASetting;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingSaysNothing;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\TheWorkAsked;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * Every setting and secret a stack's services read, through the SDK: the `settings` read, and the `settings-set` and `settings-clear` actions, rehearsed, carried out and followed.
 */
final readonly class Tuners implements SettingWhatServicesRead
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function readOn(Stack $stack, Session $session): WhatWasFoundOfTheSettings
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatWasFoundOfTheSettings::found(TheSettingsListed::in($client->read(Api::SETTINGS_ENDPOINT)));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheSettings::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SettingsAreUnreadable|SettingSaysNothing|ServiceIsUnnamed $why) {
            return WhatWasFoundOfTheSettings::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, ASettingChange $change): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->request($change, AnOffer::none())->rehearsed());
    }

    /**
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, ASettingChangeAgreed $agreed): WhatBecameOfTheWork
    {
        return $this->work()->sent($stack, $session, $this->request($agreed->change(), $agreed->offer()));
    }

    /**
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        try {
            return $this->work()->followed($stack, $session, $job, static fn(Envelope $envelope): TheSettingAsChanged => WhatTheSettingCameTo::in($envelope));
        } catch (SettingsAreUnreadable|SettingSaysNothing|ServiceIsUnnamed $why) {
            return WhatBecameOfTheWork::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** The action the change is sent as, carrying the offer agreed to where there is one. */
    private function request(ASettingChange $change, AnOffer $offer): SettingsSetAction|SettingsClearAction
    {
        return $offer->either(
            named: static fn(string $named): SettingsSetAction|SettingsClearAction => self::action($change, $named),
            none: static fn(): SettingsSetAction|SettingsClearAction => self::action($change, null),
        );
    }

    private static function action(ASettingChange $change, ?string $offer): SettingsSetAction|SettingsClearAction
    {
        return match ($change->how()) {
            ChangingASetting::Set => new SettingsSetAction(value: $change->value()->said(), setting: $change->setting(), offer: $offer),
            ChangingASetting::Clear => new SettingsClearAction(setting: $change->setting(), offer: $offer),
        };
    }

    private function work(): TheWorkAsked
    {
        return new TheWorkAsked($this->clients, $this->entropy);
    }
}
