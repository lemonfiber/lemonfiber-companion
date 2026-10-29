<?php

declare(strict_types=1);

namespace Tests\Support;

use function app;
use function array_filter;
use function array_values;
use function assert;

use Bootstrap\Composition\ArtworkGoesWithTheSession;
use Bootstrap\Composition\EveryKeeperOfAStack;

use function is_array;
use function json_encode;

use Lemonfiber\Native\Keeps;
use LogicException;
use Modules\Device\Api\PlatformPhoneKeys;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhichTab;
use Modules\Kernel\Api\Whose;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\Noticing;
use Modules\News\Api\TheItems;
use Modules\Requests\Api\KeepingWhatWasAsked;
use Modules\Sdk\Api\Assessors;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Modules\Vault\Api\PlatformGrants;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformPhoneKeysNames;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformStandings;
use Modules\Vault\Api\PlatformWhereHearingLeftOff;
use Modules\Vault\Api\PlatformWhereTheOperatorWas;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Modules\Watching\Api\KeepingTheirLanguages;
use ReflectionProperty;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APhoneKeyInMemory;
use Tests\Support\Fakes\APhoneKeyOnAHandset;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\GrantsKeptInMemory;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WhereHearingLeftOffInMemory;
use Tests\Support\Fakes\WhereTheOperatorWasInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;

/**
 * Something kept for a stack in each keeper of it, put there through the keeper's own port.
 */
final readonly class WhatEveryKeeperKeeps
{
    /** Back the app's own keepers with a platform store, a database and a phone key of the test's own, so what they keep can be read back. */
    public static function inTheAppOverStoresOfItsOwn(): void
    {
        app()->instance(Keeps::class, APlatformStore::working());
        AKeptDatabase::migrated();
        APhoneKeyOnAHandset::onAnAndroidPhone()->theAdapterOverIt();
    }

    /**
     * The keepers the app asks as one when it forgets a stack, in the order it asks them.
     *
     * @return list<ForgetsAStack>
     */
    public static function askedAsOne(ForgetsAStack $composed): array
    {
        return array_values(array_filter(self::heldBy(EveryKeeperOfAStack::class, 'keepers', $composed), static fn(mixed $keeper): bool => $keeper instanceof ForgetsAStack));
    }

    /**
     * What one of the composition's sets of keepers holds, read from the set itself.
     *
     * @param class-string $set
     *
     * @return list<mixed>
     */
    public static function heldBy(string $set, string $field, object $composed): array
    {
        $held = new ReflectionProperty($set, $field)->getValue($composed);
        assert(is_array($held));

        return array_values($held);
    }

    /** A stack a keeper holds something for. */
    public static function aStack(string $seed): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
            StackName::of(sprintf('Kept %s', $seed)),
            Address::of('https://192.168.1.46'),
            Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
        );
    }


    /** Have a keeper hold something for this stack, through its own port. */
    public static function somethingFor(ForgetsAStack $keeper, Stack $stack): void
    {
        if ($keeper instanceof PlatformStacks || $keeper instanceof StacksInMemory) {
            $keeper->remember($stack);

            return;
        }

        if ($keeper instanceof PlatformKeychain || $keeper instanceof AKeychainInMemory || $keeper instanceof ArtworkGoesWithTheSession) {
            $keeper->keep($stack->id(), Session::of('a-session'), Whose::theOperator());

            return;
        }

        if ($keeper instanceof KeepingTheLastReading || $keeper instanceof KeepingTheLastUpkeep || $keeper instanceof KeepingWhatItRuns || $keeper instanceof KeepingWhatWasAsked) {
            self::aReadingFor($keeper, $stack);

            return;
        }

        if ($keeper instanceof KeepingTheirLanguages) {
            $keeper->choose($stack->id(), Whose::member('robin'), TheirLanguages::of(HearIn::Dutch, ReadIn::English));

            return;
        }

        if ($keeper instanceof Noticing) {
            $keeper->whatIsNewIn($stack->id(), TheItems::of(KindOfNews::Request, AnItem::aRequest(1)));

            return;
        }

        if (self::aMarkerFor($keeper, $stack)) {
            return;
        }

        if ($keeper instanceof Assessors) {
            self::whatItOffersFor($keeper, $stack);

            return;
        }

        throw new LogicException(sprintf('No way to have %s keep something.', $keeper::class));
    }
    /**
     * What a stack declares when it serves nothing this app asks for.
     *
     * @return array<string, mixed>
     */
    public static function aDeclarationOfNothing(): array
    {
        return ['api_version' => 1, 'kind' => 'capabilities', 'data' => ['scope' => 'operator', 'capabilities' => []]];
    }

    /** Have a keeper of readings hold the newest reading of its kind for this stack. */
    private static function aReadingFor(KeepingTheLastReading|KeepingTheLastUpkeep|KeepingWhatItRuns|KeepingWhatWasAsked $keeper, Stack $stack): Noted
    {
        return match (true) {
            $keeper instanceof KeepingTheLastReading => $keeper->keep($stack->id(), TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()), Instant::atEpochSeconds(1)),
            $keeper instanceof KeepingTheLastUpkeep => $keeper->keep($stack->id(), WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), Instant::atEpochSeconds(1)),
            $keeper instanceof KeepingWhatItRuns => $keeper->keep($stack->id(), WhatIsKeptOfServices::aListingOfNothing()),
            $keeper instanceof KeepingWhatWasAsked => $keeper->keep($stack->id(), WhatIsKeptOfRequests::aReadingOfNothing()),
        };
    }

    /** Have a keeper of what the phone notes about a stack hold one note for it, answering whether it was one. */
    private static function aMarkerFor(ForgetsAStack $keeper, Stack $stack): bool
    {
        $kept = $stack->id();

        $noted = match (true) {
            $keeper instanceof PlatformStandings, $keeper instanceof StandingsInMemory => $keeper->remember($kept, HowItStands::Healthy, Instant::atEpochSeconds(1)),
            $keeper instanceof PlatformWhereTheOperatorWas, $keeper instanceof WhereTheOperatorWasInMemory => $keeper->wasOn($kept, WhichTab::Services),
            $keeper instanceof PlatformWhereHearingLeftOff, $keeper instanceof WhereHearingLeftOffInMemory => $keeper->heardTo($kept, AnEventHeard::named('run-1')),
            $keeper instanceof PlatformWorkLeftRunning, $keeper instanceof WorkLeftRunningInMemory => $keeper->remember($kept, KindOfWork::Walkthrough, Job::named('a-walk')),
            $keeper instanceof PlatformGrants, $keeper instanceof GrantsKeptInMemory => $keeper->keepTheGrant($kept, TheGrantIsFor::of(Whose::member('ada'), ThisDevice::named('this-device')), AGrant::of(str_repeat('0', 32), Instant::atEpochSeconds(1))),
            $keeper instanceof PlatformPhoneKeysNames, $keeper instanceof PhoneKeysNamesKeptInMemory => $keeper->keepTheName($kept, APhoneKeysName::of('Galaxy A51 · 7f3a')),
            $keeper instanceof PlatformPhoneKeys, $keeper instanceof APhoneKeyInMemory => $keeper->madeFor($kept),
            default => null,
        };

        return $noted !== null;
    }

    /** Have what this stack offers held, by asking it on a frame of its own. */
    private static function whatItOffersFor(Assessors $assessors, Stack $stack): void
    {
        MockClient::destroyGlobal();
        MockClient::global(['*' => MockResponse::make((string) json_encode(self::aDeclarationOfNothing()))]);

        try {
            $assessors->whetherItOffers($stack, Session::of('a-session'), WhatToDoWithIt::Restart);
        } catch (TheReadingWaitsAFrame) {
            // Asking a stack nothing is held for is a frame of its own: what it
            // said is held, and the frame that asked is told to wait.
        }
    }
}
