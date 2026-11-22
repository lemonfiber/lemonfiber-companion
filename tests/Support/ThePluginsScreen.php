<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatExtendsThisStack;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\Fakes\StacksInMemory;

/** The plugins screen about one stack, in front of fakes a test chooses. */
final class ThePluginsScreen
{
    /** Signed in as the operator. */
    public static function on(
        Stack $stack,
        AStackThatExtendsItself $extending,
        ?AKeychainInMemory $keychain = null,
        ?ACameraInMemory $camera = null,
        ?AStackToldToChangeSomething $adjusting = null,
    ): WhatExtendsThisStack {
        $keychain ??= AKeychainInMemory::working();
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

        return self::built($stack, $extending, $keychain, $camera, $adjusting);
    }

    /** The same screen on a phone that holds no session for the stack. */
    public static function signedOut(Stack $stack, AStackThatExtendsItself $extending): WhatExtendsThisStack
    {
        return self::built($stack, $extending, AKeychainInMemory::working(), camera: null, adjusting: null);
    }

    private static function built(Stack $stack, AStackThatExtendsItself $extending, AKeychainInMemory $keychain, ?ACameraInMemory $camera, ?AStackToldToChangeSomething $adjusting): WhatExtendsThisStack
    {
        $unasked = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

        $screen = new WhatExtendsThisStack(
            $extending,
            $adjusting ?? AStackToldToChangeSomething::met($unasked),
            AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::met($unasked), WhatBecameOfTheWork::noOutcome()),
            $keychain,
            AroundThePhone::holding(StacksInMemory::holding($stack)),
            new AppsSettingsThatOpen(),
            AroundThePhone::listening(),
            $camera ?? ACameraInMemory::reading('tdarr'),
        );
        $screen->setParams(['stack' => $stack->id()->stored()]);

        return $screen;
    }
}
