<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ADeviceToWatchOn;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SomethingThatGoesWrong;
use Modules\Kernel\Api\WhatToWatchOn;
use Modules\Operator\Internal\ViewModels\ACauseAsShown;
use Modules\Operator\Internal\ViewModels\ADeviceAsShown;
use Modules\Operator\Internal\ViewModels\ATroubleAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheAdviceTurnedOutToBe;

/**
 * What asking a stack which app to watch on produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out.
 */
final readonly class HowTheAdviceReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheAdviceTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is its advice. */
    public function this(WhatToWatchOn $advice): TheAdviceTurnedOutToBe
    {
        $devices = [];

        foreach ($advice->devices() as $device) {
            $devices[] = $this->device($device);
        }

        $troubles = [];

        foreach ($advice->troubles() as $trouble) {
            $troubles[] = $this->trouble($trouble);
        }

        return new TheAdviceTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            devices: $devices,
            onlyAtHome: $advice->onlyAtHome(),
            nothingIsInstalled: $advice->nothingIsInstalled(),
            strainingPreset: $advice->straining()->preset(),
            strainingCaution: $advice->straining()->caution(),
            strainingInstead: $advice->straining()->instead(),
            troubles: $troubles,
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheAdviceTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** One device, as the row that draws it. */
    private function device(ADeviceToWatchOn $device): ADeviceAsShown
    {
        return new ADeviceAsShown(
            device: $device->device(),
            client: $device->client(),
            supportSaid: $device->support()->saidOnTheScreen(),
            caution: $device->caution(),
            instead: $device->instead(),
        );
    }

    /** One symptom and its causes, as the row that draws them. */
    private function trouble(SomethingThatGoesWrong $trouble): ATroubleAsShown
    {
        $causes = [];

        foreach ($trouble as $cause) {
            $causes[] = new ACauseAsShown(because: $cause->because(), tell: $cause->tell(), fix: $cause->fix());
        }

        return new ATroubleAsShown(symptom: $trouble->symptom(), causes: $causes);
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): TheAdviceTurnedOutToBe
    {
        return new TheAdviceTurnedOutToBe(
            went: $went,
            devices: [],
            onlyAtHome: '',
            nothingIsInstalled: '',
            strainingPreset: '',
            strainingCaution: '',
            strainingInstead: '',
            troubles: [],
        );
    }
}
