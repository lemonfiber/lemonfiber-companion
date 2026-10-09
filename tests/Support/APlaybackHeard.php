<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Household\Internal\Playing\KeepsTheMembersPlace;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AStackThatKeepsThePlace;

/** Everything one playback is heard by: what hears it, what is playing, the player, the core told and the keychain. */
final readonly class APlaybackHeard
{
    public function __construct(
        public KeepsTheMembersPlace $place,
        public WhatIsPlaying $playing,
        public APlayerOnAHandset $player,
        public AStackThatKeepsThePlace $core,
        public AKeychainInMemory $keychain,
    ) {}
}
