<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;

use function str_repeat;

use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\StacksInMemory;

/**
 * The health screen, about the one stack the files about that screen look at.
 *
 * Shared by `SeeingHowAStackIsTest`, `SeeingAStacksFindingsInOrderTest` and
 * `SeeingWhoPutACheckThereTest`.
 */
final readonly class TheHealthScreenOfTheLoft
{
    /** The machine this screen is about. */
    public static function theStackBeingLookedAt(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
            StackName::of('The loft'),
            Address::of('https://192.168.1.42:8443'),
            Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
        );
    }

    /**
     * The screen, with a stack it knows and a keychain holding whatever a test says.
     */
    public static function theHealthScreen(
        AStackThatWasAsked $asking,
        ?AKeychainInMemory $keychain = null,
        ?string $named = null,
    ): HowThisStackIs {
        $stack = self::theStackBeingLookedAt();
        $keychain ??= AKeychainInMemory::working();
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

        $screen = new HowThisStackIs($asking, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
        $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

        return $screen;
    }
}
