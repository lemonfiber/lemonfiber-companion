<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function in_array;

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Kernel\Api\InTheConnectionCatalogue;

/**
 * What stood between a member's phone and their house as it signed in, in the household's words.
 *
 * The house being out of reach is said as every member screen says it; the
 * rest are the way in's own, because they are about the name and password the
 * member was given and the code they scanned.
 */
enum WhatStoodInTheWayOfJoining: string
{
    case Refused = 'refused';

    case TooManyAttempts = 'too_many_attempts';

    case NotKept = 'not_kept';

    case NotPermitted = 'not_permitted';

    case NotTheHouse = 'not_the_house';

    case NoAnswer = 'no_answer';

    case NameNotFound = 'name_not_found';

    case NothingAtTheAddress = 'nothing_at_the_address';

    case ConnectionRefused = 'connection_refused';

    case AnswerUnreadable = 'answer_unreadable';

    /** What stood in the way of a sign-in that went as it did, or null where nothing did. */
    public static function of(HowTheSignInWent $went): ?self
    {
        return match ($went) {
            HowTheSignInWent::NotYet, HowTheSignInWent::SignedIn => null,
            HowTheSignInWent::ThePairWasRefused, HowTheSignInWent::CredentialWasRefused => self::Refused,
            HowTheSignInWent::TooManyAttempts => self::TooManyAttempts,
            HowTheSignInWent::NoStoreOnThisDevice, HowTheSignInWent::TheStoreWouldNotOpen => self::NotKept,
            HowTheSignInWent::TheNetworkIsNotPermitted => self::NotPermitted,
            HowTheSignInWent::TheMachineIsNotTheOnePaired, HowTheSignInWent::TheAddressIsNotTheStacks => self::NotTheHouse,
            HowTheSignInWent::StackDidNotAnswer => self::NoAnswer,
            HowTheSignInWent::NameWasNotFound => self::NameNotFound,
            HowTheSignInWent::NothingAtThePairedAddress => self::NothingAtTheAddress,
            HowTheSignInWent::ConnectionWasTurnedAway => self::ConnectionRefused,
            HowTheSignInWent::AnswerCouldNotBeRead => self::AnswerUnreadable,
        };
    }

    /** The key for what stood in the way. */
    public function said(): string
    {
        return $this->isTheHouseOutOfReach() ? InTheConnectionCatalogue::forTheHouseholdUnder($this->value)->said() : InTheWayInsWords::said($this->value);
    }

    /** The key for what to do about it. */
    public function remedy(): string
    {
        return $this->isTheHouseOutOfReach() ? InTheConnectionCatalogue::forTheHouseholdUnder($this->value)->remedy() : InTheWayInsWords::remedy($this->value);
    }

    /** Whether it is the house being out of reach, which every member screen says the same way. */
    private function isTheHouseOutOfReach(): bool
    {
        return in_array($this, [self::NoAnswer, self::NameNotFound, self::NothingAtTheAddress, self::ConnectionRefused, self::AnswerUnreadable], strict: true);
    }
}
