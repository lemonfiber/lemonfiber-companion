<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules\OneHome;

use function array_keys;

use Bootstrap\Composition\NativePHP\TheHarnessInstead;
use Lemonfiber\Native\Handover;
use Lemonfiber\Native\Link;
use Lemonfiber\Native\Scanning;
use Lemonfiber\Native\Telling;
use Modules\Device\Api\PlatformNotifier;
use Modules\Device\Api\SystemEntropy;
use Modules\Dx\Api\AStandInStack;
use Modules\Dx\Internal\WhatAStackWouldSay;
use Modules\Dx\Internal\WhatTheContractDeclares;
use Modules\Dx\Internal\WhatTheWireWouldAnswer;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SomethingStillComing;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Modules\Vault\Internal\KeptInAShape;

use function sprintf;

use Tests\Support\Fakes\AStoreOnAHandset;
use Tests\Support\WhatAReaderNames;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatTheReadersRead;
use Tests\Support\WhereAnExpressionPoints;

/**
 * D9 — the constants whose value matches another's without being the same
 * decision, each with what it means.
 *
 * Most are coincidences: four is the years between leap years and the
 * characters in a group read at a glance, and neither changes with the other.
 * The rest are kept apart on purpose. A stand-in speaks the wire the way a
 * stack does, a double speaks for a native half, and the native half's words
 * are the bridge's to read: one home would have the reader and what it reads
 * agree by construction, so a mistake in one would be copied into the other
 * and every test would pass.
 *
 * The list only shrinks. `TheExemptionsOnlyShrinkTest` holds it to a ceiling
 * and refuses an entry naming a constant nothing declares.
 */
final readonly class Coincidences
{
    /** @var array<class-string, array<string, string>> by class, each constant and what it means */
    public const array OF = [
        PlatformNotifier::class => ['UNDER' => 'the group notifications are filed under on the phone'],
        SystemEntropy::class => ['BYTES' => 'the bytes of randomness a nonce is drawn from'],
        Nonce::class => ['SHORTEST' => 'the fewest characters a nonce may have'],
        AStandInStack::class => ['IS_ANSWERING' => 'the status a working stand-in answers with, written as a stack writes it'],
        TheHarnessInstead::class => ['THE_ROUTE_IS_THERE' => 'the status a suite is answered with for a route that names a screen'],
        WhatAStackWouldSay::class => ['A_NUMBER' => 'the integer a stand-in payload is given'],
        WhatTheWireWouldAnswer::class => ['A_FEW_LINES' => 'the lines a stand-in scrollback holds'],
        WhatTheContractDeclares::class => ['EACH' => 'how a path names each entry of a list'],
        WhatARefusalMeant::class => ['A_REFUSAL' => 'the first status that is a refusal'],
        AMomentAsWritten::class => [
            'LEAP_EVERY' => 'the years between leap years',
            'LAST_SECOND' => 'the highest second a timestamp may write, a leap second included',
        ],
        ARatio::class => ['HUNDREDTHS' => 'the hundredths in a ratio of one'],
        SomethingStillComing::class => ['ALL_OF_IT' => 'the percentage a finished download has reached'],
        AtAGlance::class => ['MIXED_BY' => 'the hash a fingerprint is mixed by before it is read at a glance'],
        PlatformWorkLeftRunning::class => ['JOB_UNDER' => 'the field of a kept record that holds the handle'],
        KeptInAShape::class => ['SHAPE' => 'the field of a kept record that says which shape it was written in'],
        Handover::class => ['OFFERED' => "the native half's word for a sheet that was presented"],
        Link::class => ['UNREACHABLE' => "the native half's word for no network"],
        Scanning::class => ['READ' => "the native half's word for a code that was read"],
        Telling::class => ['WITHHELD' => "the native half's word for notifications the operator turned down"],
        AStoreOnAHandset::class => [
            'THE_NARROWEST' => 'what the double of the native store answers a word it does not know with, spelled apart from the bridge it stands behind',
            'WHENEVER_THE_APP_RUNS' => "what the double of Android's store grants every value, spelled apart from the bridge it stands behind",
        ],
        WhatAReaderNames::class => ['SETTLING' => "the passes before a method's local names settle"],
        WhatTheReadersRead::class => ['THE_MOST_PASSES' => 'the passes before reading the readers gives up'],
        WhatTheDeviceWouldDraw::class => [
            'SAID' => "the prop EDGE carries a node's words on",
            'LABELLED' => "the prop EDGE carries a control's words on",
        ],
        WhereAnExpressionPoints::class => ['PAYLOAD' => 'the property an SDK envelope carries its payload on'],
    ];

    /**
     * Every listed constant, as `Class::NAME`.
     *
     * @return list<string>
     */
    public static function named(): array
    {
        $named = [];

        foreach (self::OF as $class => $constants) {
            foreach (array_keys($constants) as $constant) {
                $named[] = sprintf('%s::%s', $class, $constant);
            }
        }

        return $named;
    }
}
