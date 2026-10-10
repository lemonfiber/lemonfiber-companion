<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Session;

/**
 * Values that may be read only where they are meant to go, and the accessors that read them.
 *
 * Three types each carry a string with a fixed place to go, and each
 * publishes one way to reach it per place, named for that place:
 * `forTheHeader`, `forTheExchange`, `forTheClient`, and for an address also
 * `forTheOperatorWhoCouldNotReachIt` and `forThePersonAskedToTrustIt`. The naming is the guard — reading one
 * for any other purpose is meant to read wrong at the call site.
 *
 * **Here rather than in the test that counts them, because two rules need it.**
 * `AValueWithOneDestinationTest` asks whether a second accessor has appeared
 * beside the one; `NothingSecretReachesAScreenTest` asks whether a template
 * calls the one. They are different questions about the same three facts, and
 * the copy that goes stale is whichever is not being edited that day — a
 * template rule naming `forTheClient` while the accessor had been renamed would
 * find nothing and report a clean run. Neither accessor of an address is a
 * template's to call: the operator's screen draws the address through the
 * obstacle that carries it, and `TheTriedAddressIsShownOnlyToTheOperatorTest`
 * holds who reads it.
 */
final readonly class OneDestination
{
    /**
     * The types, the accessors each may publish, and what that buys.
     *
     * @return list<array{class-string, list<string>, string, string}>
     */
    public static function all(): array
    {
        return [
            [
                Session::class,
                ['forTheHeader'],
                'N1-R8',
                'The session is carried in the credential header the API defines and must never '
                . 'reach a URL or a query parameter. A query string is written to every proxy '
                . 'log and every browser history between here and the stack, so a second '
                . 'accessor is not a tidiness question — it is the one that ends up in a log.',
            ],
            [
                Credential::class,
                ['forTheExchange'],
                'N1-R7',
                'A credential is exchanged once and must not be retained for re-sending. '
                . '`forTheExchange()` forgets before it answers, which is what makes "once" a '
                . 'fact about the object; a second accessor added beside it would answer '
                . 'without forgetting and the guarantee would be gone with nothing to notice.',
            ],
            [
                Address::class,
                ['forTheClient', 'forTheOperatorWhoCouldNotReachIt', 'forThePersonAskedToTrustIt'],
                'N1-R15',
                'A stack address must not be logged, transmitted or put in a diagnostic report, '
                . 'and is shown only to the operator on the screen that says it was not reached, and to '
                . 'the person deciding whether to trust the join link that names it. One accessor named '
                . 'for the transport and one for each of those screens are what make any other use read '
                . 'wrong where it is written, which is the only place anybody would catch it.',
            ],
        ];
    }
}
