<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function count;

use Modules\Connection\Api\WhyWakingStayedOff;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhoWakesThisPhone;
use Modules\Kernel\Api\WhyNoEndpoint;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Operator\Internal\ViewModels\ALinkWithItsCode;
use Modules\Operator\Internal\ViewModels\ASettingAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\HowWakingIsShown;
use Modules\Operator\Internal\ViewModels\WhatWakingSaid;

/**
 * Waking this phone through a distributor, as a stack's settings and the app's settings draw it.
 *
 * What the switch says follows what is kept for the stack first: on, or off
 * with its removal owed, is said whatever is installed now.
 */
final readonly class HowWakingReads
{
    /** How many distributors make a choice. */
    private const int A_CHOICE = 2;

    /** Nothing drawn: an iPhone, an Android too old to keep the key, or a member's session. */
    public function notDrawn(): HowWakingIsShown
    {
        return new HowWakingIsShown(drawn: false, on: false, usable: false, line: '', distributor: '', choice: $this->choice(TheDistributors::of(), ''), install: null);
    }

    /**
     * The switch for one stack, `$install` being where a distributor is found, with its code.
     */
    public function forTheStack(WhoWakesThisPhone $who, HowThisPhoneIsWoken $waking, ALinkWithItsCode $install): HowWakingIsShown
    {
        return $waking->either(
            off: fn(): HowWakingIsShown => $this->whenOff($who, $install),
            on: fn(ADistributor $through): HowWakingIsShown => new HowWakingIsShown(
                drawn: true,
                on: true,
                usable: true,
                line: 'waking.on',
                distributor: $through->called(),
                choice: $this->choice(TheDistributors::of(), ''),
                install: null,
            ),
            owed: fn(): HowWakingIsShown => $this->whenOff($who, $install, owed: true),
        );
    }

    /** The distributors to choose among on the app's settings, the one that wakes the phone marked. */
    public function choiceAmong(WhoWakesThisPhone $who): ASettingAsShown
    {
        return $who->either(
            through: fn(ADistributor $through, TheDistributors $installed): ASettingAsShown => $this->choice($installed, $through->package()),
            toChoose: fn(TheDistributors $installed): ASettingAsShown => $this->choice($installed, ''),
            noneInstalled: fn(): ASettingAsShown => $this->choice(TheDistributors::of(), ''),
            notHere: fn(): ASettingAsShown => $this->choice(TheDistributors::of(), ''),
        );
    }

    /** The distributors to choose among, `$chosen` marked, with none offered where fewer than two are installed. */
    public function choice(TheDistributors $installed, string $chosen): ASettingAsShown
    {
        if (count($installed) < self::A_CHOICE) {
            return new ASettingAsShown(said: '', offered: []);
        }

        $offered = [];
        $said = '';

        foreach ($installed as $distributor) {
            $isChosen = $distributor->package() === $chosen;
            $offered[] = new AChoiceOfASettingAsShown(said: $distributor->called(), word: $distributor->package(), chosen: $isChosen);
            $said = $isChosen ? $distributor->called() : $said;
        }

        return new ASettingAsShown(said: $said, offered: $offered);
    }

    /** Why waking stayed off, in the stack's words where it refused, and in this app's everywhere else. */
    public function stayedOff(WhyWakingStayedOff $why): WhatWakingSaid
    {
        return $why->either(
            noKeyName: static fn(): WhatWakingSaid => new WhatWakingSaid(words: '', line: 'waking.no_key_name', with: []),
            noEndpoint: static fn(WhyNoEndpoint $no): WhatWakingSaid => new WhatWakingSaid(words: '', line: $no->saidOnTheScreen(), with: []),
            refused: static fn(ARefusalInItsWords $said): WhatWakingSaid => new WhatWakingSaid(words: $said->summary(), line: '', with: []),
            met: static function (Obstacle $met): WhatWakingSaid {
                $went = HowTheReadingWent::somethingStopped($met);

                return $went->isSignedIn
                    ? new WhatWakingSaid(words: '', line: $went->met, with: $went->filling())
                    : new WhatWakingSaid(words: '', line: 'connection.session_has_ended', with: []);
            },
            notKept: static fn(): WhatWakingSaid => new WhatWakingSaid(words: '', line: 'waking.not_kept', with: []),
        );
    }

    private function whenOff(WhoWakesThisPhone $who, ALinkWithItsCode $install, bool $owed = false): HowWakingIsShown
    {
        $line = $owed ? 'waking.owed' : 'waking.off';

        return $who->either(
            through: fn(ADistributor $through): HowWakingIsShown => new HowWakingIsShown(
                drawn: true,
                on: false,
                usable: true,
                line: $line,
                distributor: $through->called(),
                choice: $this->choice(TheDistributors::of(), ''),
                install: null,
            ),
            toChoose: fn(TheDistributors $installed): HowWakingIsShown => new HowWakingIsShown(
                drawn: true,
                on: false,
                usable: true,
                line: $owed ? $line : 'waking.off_choosing',
                distributor: '',
                choice: $this->choice($installed, ''),
                install: null,
            ),
            noneInstalled: fn(): HowWakingIsShown => new HowWakingIsShown(
                drawn: true,
                on: false,
                usable: false,
                line: $owed ? $line : 'waking.none_installed',
                distributor: '',
                choice: $this->choice(TheDistributors::of(), ''),
                install: $install,
            ),
            notHere: fn(): HowWakingIsShown => $this->notDrawn(),
        );
    }
}
