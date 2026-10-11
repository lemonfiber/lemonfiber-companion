<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AKeyForAnApp;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Operator\Internal\ViewModels\AKeyAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheKeysTurnedOutToBe;

/**
 * What asking a stack for its integration keys produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out.
 */
final readonly class HowTheKeysRead
{
    /** The key a key that reads is said with. */
    private const string READING = 'stacks.keys.admits.read';

    /** The key a key that reads and acts is said with. */
    private const string ACTING = 'stacks.keys.admits.act';

    /** The key a member's key is said with, naming the member. */
    private const string A_MEMBERS = 'stacks.keys.admits.member';

    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheKeysTurnedOutToBe
    {
        return new TheKeysTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), keys: [], purposes: '');
    }

    /** The stack answered, and these are its keys, each dated against `$now`. */
    public function these(TheKeysForApps $keys, Instant $now): TheKeysTurnedOutToBe
    {
        $shown = [];

        foreach ($keys as $key) {
            $shown[] = $this->one($key, $now);
        }

        return new TheKeysTurnedOutToBe(went: HowTheReadingWent::itCameBack(), keys: $shown, purposes: $keys->purposes());
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheKeysTurnedOutToBe
    {
        return new TheKeysTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), keys: [], purposes: '');
    }

    /** One key, as the card that draws it. */
    private function one(AKeyForAnApp $key, Instant $now): AKeyAsShown
    {
        [$admits, $filling] = $key->admits()->either(
            reading: static fn(): AdmitsAsShown => new AdmitsAsShown(self::READING, []),
            acting: static fn(): AdmitsAsShown => new AdmitsAsShown(self::ACTING, []),
            member: static fn(string $member): AdmitsAsShown => new AdmitsAsShown(self::A_MEMBERS, ['member' => $member]),
        )->pair();

        return new AKeyAsShown(
            name: $key->name()->shown(),
            admits: $admits,
            admitsFilling: $filling,
            forSaid: $key->isFor()->saidOnTheScreen(),
            standsSaid: $key->stands()->saidOnTheScreen(),
            minted: $this->ago($key->minted(), $now),
            used: $this->ago($key->used(), $now),
            byAMember: $key->wasMintedByAMember(),
            mayBeRevoked: $key->stands()->mayBeRevoked(),
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
