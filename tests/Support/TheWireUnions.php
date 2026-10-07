<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_map;
use function array_unique;
use function array_values;

use BackedEnum;

use function count;
use function file_get_contents;
use function is_string;

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\HowItWasReached;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowSeriousAConnectionIs;
use Modules\Kernel\Api\HowSeriousALineIs;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\HowTheDoorWasChosen;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\HowWellADeviceIsServed;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\Stream;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatHappenedToIt;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhatSortItIs;
use Modules\Kernel\Api\WhatTheHandoffNeedsNext;
use Modules\Kernel\Api\WhatToDoNext;
use Modules\Kernel\Api\WhereAConnectionStands;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhereTheAskingStands;
use Modules\Kernel\Api\WhereTheFrontDoorStands;
use Modules\Kernel\Api\WhereTheHandoffStands;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheWalkthroughIs;
use Modules\Kernel\Api\WhereThisCopyStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Kernel\Api\WhichWalk;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyTheWalkthroughStopped;

use function preg_match_all;
use function preg_quote;

use ReflectionClass;

use function sort;
use function sprintf;

/**
 * The values a generated envelope allows a field, and the enums held to them.
 *
 * What `EveryWireValueIsACaseTest` compares each enum's cases with.
 */
final readonly class TheWireUnions
{
    /** The enums `EveryWireValueIsACaseTest` checks against the contract, by the field each mirrors. */
    public const array CHECKED_AGAINST_THE_WIRE = [
        Category::class => 'category',
        Conclusion::class => 'outcome',
        Overall::class => 'overall',
        HowItStands::class => 'standing',
        Severity::class => 'severity',
        WhoSettledIt::class => 'whose',
        HowItWasReached::class => 'how',
        HowAServiceRuns::class => 'state',
        HowMuchItMatters::class => 'criticality',
        HowTheStackIsRunning::class => 'condition',
        Stage::class => 'stage',
        HowItStopped::class => 'stall',
        AgainstThePins::class => 'state',
        HowTheNotesStand::class => 'state',
        HowItEnded::class => 'ending',
        HowToUndoIt::class => 'reversal',
        Awaiting::class => 'until',
        Standing::class => 'state',
        Stream::class => 'stream',
        HowSeriousALineIs::class => 'level',
        Medium::class => 'medium',
        Cost::class => 'cost',
        Stance::class => 'stance',

        HowItIsHosted::class => 'standing',
        WhatKeepsItRunning::class => 'manager',
        HowFarItGoesBack::class => 'reversal',
        WhatLemonfiberAsksFor::class => 'reach',
        WhereTheLineStands::class => 'restraint',
        HowTheLineWasMeasured::class => 'source',
        WhatACapDoes::class => 'exceeded',
        WhereTheMonthStands::class => 'reached',
        WhereTheRoomStands::class => 'level',
        WhatAVolumeHolds::class => 'role',
        WhatGettingItBackCosts::class => 'reclaim',
        HowLemonfiberWasInstalled::class => 'installed',
        HowSureTheTraceIs::class => 'confidence',
        WhatItWouldNeed::class => 'needs',
        WhatHappenedToIt::class => 'outcome',
        WhereThisCopyStands::class => 'standing',
        WhereACredentialStands::class => 'state',
        WhoMadeACredential::class => 'origin',
        HowWellADeviceIsServed::class => 'support',
        WhereTheFrontDoorStands::class => 'standing',
        WhereTheHandoffStands::class => 'state',
        WhatTheHandoffNeedsNext::class => 'remedy',
        WhatItFaces::class => 'facing',
        HowTheDoorWasChosen::class => 'chosen',
        WhereTheInvitationStands::class => 'standing',
        WhetherTheyCanAsk::class => 'linked',
        WhatBecomesOfUnrated::class => 'unrated',
        HowFarTheRemovalReached::class => 'revoked',
        WhatBecameOfTheChoice::class => 'disposition',
        WhichRemoval::class => 'tier',
        WhatSortItIs::class => 'sort',
        WhereTheAskingStands::class => 'state',
        WalkthroughStep::class => 'step',
        WhereTheWalkthroughIs::class => 'state',
        WhichWalk::class => 'shape',
        HowTheImportLinked::class => 'link',
        WhyTheWalkthroughStopped::class => 'reason',
        WhatToDoNext::class => 'next',
        HowDriftWasJudged::class => 'assessment',
        WhereAConnectionStands::class => 'state',
        HowSeriousAConnectionIs::class => 'severity',
        WhatGoingBackDoes::class => 'does',

        // `state` twice, and that is the wire's name rather than a mistake here:
        // a problem's standing and a household request's are different unions in
        // different envelopes. Each has a rule in `EveryWireValueIsACaseTest` naming
        // which envelope it reads.
        Waiting::class => 'state',
        WhatBecameOfIt::class => 'outcome',
    ];

    /**
     * Every literal one field is fixed to across the arms of an object union.
     *
     * `standing` on a download and `of` on a line are not literal unions: each arm
     * of the shape fixes the field to a literal of its own, the way `Conclusion`'s
     * `outcome` is. Read off the generated type as `field: 'word'`, once each.
     *
     * @return list<string>
     */
    public static function theArmsIn(string $envelope, string $field): array
    {
        preg_match_all(sprintf("/\\b%s: '([a-z_-]+)'/", $field), $envelope, $found);
        $words = array_values(array_unique($found[1]));
        sort($words);

        return $words;
    }

    /**
     * The literals a named field of the envelope is declared as.
     *
     * Every occurrence is collected rather than the first, and two that disagree
     * make this answer nothing. A field name appearing twice with different unions
     * is a question this cannot answer, and answering it with whichever came first
     * would be the quiet half-right result these rules exist to refuse.
     *
     * @return list<string>
     */
    public static function wireUnion(string $field): array
    {
        return self::unionIn(TheGeneratedEnvelopes::theGeneratedDoctorEnvelope(), $field);
    }

    /**
     * The same reading, over text it is handed rather than text it goes and finds.
     *
     * Split from `wireUnion` because this is the half that decides, and it had only
     * ever been asked about a generated file where every answer is the right one.
     * Every property the five rules in `EveryWireValueIsACaseTest` demonstrate in
     * that state is equally true of a reader that always answers with the enum it
     * is being compared to — and the `[]` two disagreeing unions produce is a shape
     * nobody had watched it take.
     *
     * @return list<string>
     */
    public static function unionIn(string $source, string $field): array
    {
        $pattern = sprintf("/\\b%s\\??: ((?:'[a-z_-]+'\\|)+'[a-z_-]+')/", preg_quote($field, '/'));

        preg_match_all($pattern, $source, $found);

        $unions = array_values(array_unique($found[1]));

        if (count($unions) !== 1) {
            return [];
        }

        preg_match_all("/'([a-z_-]+)'/", $unions[0], $literals);

        sort($literals[1]);

        return $literals[1];
    }

    /**
     * Every value a check's verdict may carry as its outcome.
     *
     * Read as single literals rather than as a union: the verdict is a union of
     * object shapes and each arm fixes `outcome` to one value of its own.
     *
     * @return list<string>
     */
    public static function wireOutcomes(): array
    {
        return self::outcomesIn(TheGeneratedEnvelopes::theGeneratedDoctorEnvelope());
    }

    /**
     * The same reading, over text it is handed. Split for the reason `unionIn` is.
     *
     * @return list<string>
     */
    public static function outcomesIn(string $source): array
    {
        preg_match_all("/outcome: '([a-z_-]+)'/", $source, $found);

        $values = array_values(array_unique($found[1]));

        sort($values);

        return $values;
    }

    /**
     * An enum's values, sorted, for comparing as a set.
     *
     * @param list<BackedEnum> $cases
     *
     * @return list<string>
     */
    public static function valuesOf(array $cases): array
    {
        $values = array_map(static fn(BackedEnum $case): string => (string) $case->value, $cases);

        sort($values);

        return $values;
    }

    /**
     * An enum's backing values, sorted, for comparing as a set.
     *
     * Read off the class constants, which is where PHP puts an enum's cases. The
     * obvious `ReflectionEnum` wants a `class-string<UnitEnum>` where this has a
     * `class-string`, and its constructor throws a checked exception — which a Pest
     * body, being a closure, may not.
     *
     * @param ReflectionClass<object> $class
     *
     * @return list<string>
     */
    public static function backedValuesOf(ReflectionClass $class): array
    {
        $values = [];

        foreach ($class->getConstants() as $case) {
            if ($case instanceof BackedEnum) {
                $values[] = (string) $case->value;
            }
        }

        sort($values);

        return $values;
    }

    /**
     * The words a settlement can be, read from the tagged objects that carry them.
     *
     * Not `unionIn`, which reads a union of literals joined by `|`. These five are
     * each the tag of an object of its own — `array{settled: 'contested', ...}` —
     * because four of them carry different fields alongside. So the literals are
     * gathered from every occurrence of the tag rather than from one union, and the
     * count is asserted by the rule that uses this rather than here.
     *
     * @return list<string>
     */
    public static function theSettlementsIn(string $source): array
    {
        preg_match_all("/\\bsettled: '([a-z_-]+)'/", $source, $found);

        $words = array_values(array_unique($found[1]));

        sort($words);

        return $words;
    }

    /**
     * The words a reach can be, read the same way a settlement's are.
     *
     * `asked` and `by-name` are tags on objects of their own rather than a union
     * joined by `|`, because the two carry different fields — one a capability and
     * its claimants, the other a service and a reason. So they are gathered by tag,
     * exactly as `theSettlementsIn` gathers the five settlements.
     *
     * @return list<string>
     */
    public static function theReachesIn(string $source): array
    {
        preg_match_all("/\\bhow: '([a-z_-]+)'/", $source, $found);

        $words = array_values(array_unique($found[1]));

        sort($words);

        return $words;
    }

    /**
     * Every literal union any generated envelope declares, as a set of values.
     *
     * @return list<list<string>>
     */
    public static function everyWireUnion(): array
    {
        $found = [];

        foreach (Tree::filesUnder(Tree::at('vendor/lemonfiber/sdk-php/src/Generated'), '.php') as $path) {
            $said = file_get_contents($path);

            if (! is_string($said)) {
                continue;
            }

            preg_match_all("/(?:'[a-z_-]+'\\|)+'[a-z_-]+'/", $said, $unions);

            foreach ($unions[0] as $union) {
                preg_match_all("/'([a-z_-]+)'/", $union, $literals);

                sort($literals[1]);

                $found[] = $literals[1];
            }
        }

        return $found;
    }
}
