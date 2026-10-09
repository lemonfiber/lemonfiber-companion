<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Genres;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\ItsDetails;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Seasons;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheTitleIs;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhenItWasReleased;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Kernel\Api\Whose;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShelfThatWasRead;

/**
 * One film the core says streams at the door, on one stack, for a member signed in there.
 */
final readonly class ATitleThatPlays
{
    /** The machine it is on. */
    public static function theStack(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('p', Nonce::SHORTEST))),
            StackName::of('The loft'),
            Address::of('https://192.168.1.42:8443'),
            Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
        );
    }

    /** Where the core says a title streams from, at the door. */
    public static function streamingAtTheDoor(string $id): WhereItPlays
    {
        return WhereItPlays::at(Location::of(sprintf('https://192.168.1.42:8920/Videos/%s/master.m3u8', $id)), Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)));
    }

    /** The film, as the core answers it: `a1`, streaming at the door. */
    public static function alien(): ATitle
    {
        return ATitle::of(Holding::of(HoldingId::called('a1'), 'Alien', Medium::Film, WhenItCameOut::in(1979)), ItsDetails::of('A crew meets something on the way home.', HowLongItRuns::minutes(117), Genres::of('Horror'), '', WhenItWasReleased::unstated()), self::streamingAtTheDoor('a1'), Seasons::none());
    }

    /** A shelf that answers the film when it is asked for. */
    public static function onTheShelf(): AShelfThatWasRead
    {
        return AShelfThatWasRead::holdingNothing()->answeringTheTitle(WhatTheTitleIs::told(self::alien()));
    }

    /** A keychain holding a session on its stack, Ada's unless a test says whose. */
    public static function signedIn(?Whose $whose = null): AKeychainInMemory
    {
        $keychain = AKeychainInMemory::working();
        $keychain->keep(self::theStack()->id(), Session::of('a-session-not-a-secret'), $whose ?? Whose::member('ada'));

        return $keychain;
    }
}
