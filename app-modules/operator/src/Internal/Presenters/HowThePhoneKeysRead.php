<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePhoneKeys;
use Modules\Operator\Internal\ViewModels\APhoneKeyAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\ThePhoneKeysTurnedOutToBe;

/**
 * What asking a stack for its phone keys, or revoking one, produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out.
 */
final readonly class HowThePhoneKeysRead
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): ThePhoneKeysTurnedOutToBe
    {
        return new ThePhoneKeysTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), keys: [], refused: null);
    }

    /** The stack answered, and these are its phone keys, each dated against `$now`. */
    public function these(ThePhoneKeys $keys, Instant $now): ThePhoneKeysTurnedOutToBe
    {
        $shown = [];

        foreach ($keys as $key) {
            $shown[] = $this->one($key, $now);
        }

        return new ThePhoneKeysTurnedOutToBe(went: HowTheReadingWent::itCameBack(), keys: $shown, refused: null);
    }

    /** The stack refused a revoke, in its words, beside the keys as they were last shown. */
    public function refused(ARefusalInItsWords $why, ThePhoneKeysTurnedOutToBe $shown): ThePhoneKeysTurnedOutToBe
    {
        return new ThePhoneKeysTurnedOutToBe(went: $shown->went, keys: $shown->keys, refused: new HowARefusalReads()->inItsWords($why));
    }

    /** It could not be asked, and this is what the operator met. */
    public function met(Obstacle $why): ThePhoneKeysTurnedOutToBe
    {
        return new ThePhoneKeysTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), keys: [], refused: null);
    }

    /** One phone key, as the card that draws it. */
    private function one(APhoneKey $key, Instant $now): APhoneKeyAsShown
    {
        return new APhoneKeyAsShown(
            name: $key->name()->shown(),
            livesSaid: $key->lives()->saidOnTheScreen(),
            registered: $this->ago($key->registered(), $now),
            used: $this->ago($key->used(), $now),
            revoked: $this->ago($key->whenRevoked(), $now),
            mayBeRevoked: ! $key->wasRevoked(),
        );
    }

    /** How long before `$now` a moment was, or nothing where it names none. */
    private function ago(AMomentAsWritten $moment, Instant $now): AgoAsShown
    {
        return $moment->read(
            read: static fn(Instant $at): AgoAsShown => AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now),
            unreadable: AgoAsShown::unsaid(...),
        );
    }
}
