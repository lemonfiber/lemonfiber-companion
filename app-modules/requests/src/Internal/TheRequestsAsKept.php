<?php

declare(strict_types=1);

namespace Modules\Requests\Internal;

use function array_key_exists;

use InvalidArgumentException;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfRequest;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\TheOthersOnAnAsk;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

/**
 * What a household asked its stack for, as the phone writes it before sealing it and reads it back after.
 *
 * **Written in {@see Shape::One}, field for field what the stack reported**:
 * each request's number, who asked, the other members it is also for, what
 * for, when it was asked for, how big it is and whether that was measured or
 * guessed, where it stands, and, where it was turned down, the reason the
 * stack gave and when. Nothing is worked out, so a reading read back
 * draws as the one that was read.
 *
 * **Only the reading.** An approval, a decline, the reason an operator typed
 * for one and what the stack answered when it was told are never handed here,
 * so none of them can be written.
 *
 * **Read back by the shape it says it was written in**, and anything that
 * does not read is nothing: the stack can always be asked again.
 */
final readonly class TheRequestsAsKept
{
    private const string NOT_YET_NAMED = 'not_yet_named';

    private const string KIND = 'kind_of_request';

    private const string ASKED = 'asked';

    private const string ALSO_ASKED = 'also_asked';

    /**
     * The reading as a value to seal, or nothing where it cannot be written.
     *
     * A household whose words are not valid text cannot be written; it is
     * shown as it was read and kept nowhere.
     */
    public static function written(Requested $requested): ?Unsealed
    {
        $requests = [];

        foreach ($requested as $wanted) {
            $requests[] = self::wanted($wanted);
        }

        $written = json_encode(['requests' => $requests]);

        return $written === false ? null : Unsealed::of($written);
    }

    /** The reading a value written in this shape holds, or nothing where it does not read as one. */
    public static function read(Shape $shape, Unsealed $value): ?Requested
    {
        try {
            return match ($shape) {
                Shape::One => self::inShapeOne(self::fieldsIn(json_decode($value->inTheClear(), associative: true))),
            };
        } catch (InvalidArgumentException) {
            // A field missing or of the wrong type, and a kernel value
            // refusing what it was handed — a request nobody made, one for
            // nothing — are the same answer: a reading that does not read.
            return null;
        }
    }

    /** @param array<array-key, mixed> $written */
    private static function inShapeOne(array $written): Requested
    {
        $requests = [];

        foreach (self::listAt($written, 'requests') as $request) {
            $requests[] = self::askedIn(self::fieldsIn($request));
        }

        return Requested::of(...$requests);
    }

    /** @return array<string, mixed> */
    private static function wanted(Wanted $wanted): array
    {
        /** @var array<string, string> $called */
        $called = $wanted->called()->either(
            named: static fn(string $title): Written => new Written(['for' => $title, self::KIND => $wanted->called()->kind()->value]),
            notYet: static fn(KindOfRequest $kind): Written => new Written([self::NOT_YET_NAMED => $kind->value]),
        )->held;

        return [
            'id' => $wanted->id()->reference(),
            'by' => $wanted->by(),
            ...$called,
            'size' => $wanted->size()->either(
                measured: static fn(int $bytes): Written => new Written(['measured' => $bytes]),
                guessed: static fn(int $bytes): Written => new Written(['guessed' => $bytes]),
                unknown: static fn(): Written => new Written([]),
            )->held,
            'standing' => $wanted->standing()->either(
                said: static fn(Waiting $said): Written => new Written([$said->value]),
                unnamed: static fn(): Written => new Written([]),
            )->held,
            'refused' => $wanted->refusal(
                was: static fn(TurnedDown $why): Written => new Written([[
                    'reason' => $why->reason(),
                    'at' => self::momentWritten($why->when()),
                ]]),
                wasNot: static fn(): Written => new Written([]),
            )->held,
            self::ASKED => self::momentWritten($wanted->asked()),
            self::ALSO_ASKED => self::othersWritten($wanted->alsoAskedBy()),
        ];
    }

    /** @return list<string> */
    private static function othersWritten(TheOthersOnAnAsk $others): array
    {
        $names = [];

        foreach ($others as $name) {
            $names[] = $name;
        }

        return $names;
    }

    /** @return array<array-key, mixed> */
    private static function momentWritten(AMomentAsWritten $moment): array
    {
        return $moment->read(
            read: static fn(Instant $when): Written => new Written([$when->epochSeconds()]),
            unreadable: static fn(): Written => new Written([]),
        )->held;
    }

    /**
     * One request kept, with when it was asked for and the other members it is also for, where those were kept.
     *
     * @param array<array-key, mixed> $fields
     */
    private static function askedIn(array $fields): Wanted
    {
        $asked = array_key_exists(self::ASKED, $fields) ? self::momentIn(self::listAt($fields, self::ASKED), self::ASKED) : AMomentAsWritten::unwritten();
        $also = [];

        foreach (array_key_exists(self::ALSO_ASKED, $fields) ? self::listAt($fields, self::ALSO_ASKED) : [] as $member) {
            $also[] = is_string($member) ? $member : throw KeptRequestsDoNotRead::at(self::ALSO_ASKED);
        }

        return self::wantedIn($fields)->askedAt($asked)->alsoFor(TheOthersOnAnAsk::these(...$also));
    }

    /** @param array<array-key, mixed> $fields */
    private static function wantedIn(array $fields): Wanted
    {
        $id = RequestId::named(self::textAt($fields, 'id'));
        $by = self::textAt($fields, 'by');
        $size = self::sizeIn(self::fieldsIn(self::at($fields, 'size')));

        if (array_key_exists(self::NOT_YET_NAMED, $fields)) {
            return self::notYetNamedIn($fields, $id, $by, $size);
        }

        $for = self::textAt($fields, 'for');
        $kind = self::kindIn($fields);

        foreach (self::listAt($fields, 'refused') as $refused) {
            return Wanted::turnedDown($id, $by, $for, $kind, $size, self::turnedDownIn(self::fieldsIn($refused)));
        }

        return Wanted::of($id, $by, $for, $kind, $size, self::standingIn(self::listAt($fields, 'standing')));
    }

    /**
     * The kind a named request was kept as, or something asked for where the kept reading names none.
     *
     * @param array<array-key, mixed> $fields
     */
    private static function kindIn(array $fields): KindOfRequest
    {
        if (! array_key_exists(self::KIND, $fields)) {
            return KindOfRequest::Something;
        }

        return KindOfRequest::tryFrom(self::textAt($fields, self::KIND)) ?? throw KeptRequestsDoNotRead::at(self::KIND);
    }

    /** @param array<array-key, mixed> $fields */
    private static function notYetNamedIn(array $fields, RequestId $id, string $by, Size $size): Wanted
    {
        $kind = KindOfRequest::tryFrom(self::textAt($fields, self::NOT_YET_NAMED)) ?? throw KeptRequestsDoNotRead::at(self::NOT_YET_NAMED);

        foreach (self::listAt($fields, 'refused') as $refused) {
            return Wanted::turnedDownNotYetNamed($id, $by, $kind, $size, self::turnedDownIn(self::fieldsIn($refused)));
        }

        return Wanted::notYetNamed($id, $by, $kind, $size, self::standingIn(self::listAt($fields, 'standing')));
    }

    /** @param array<array-key, mixed> $fields */
    private static function sizeIn(array $fields): Size
    {
        if (array_key_exists('measured', $fields)) {
            return Size::measured(self::numberAt($fields, 'measured'));
        }

        return array_key_exists('guessed', $fields) ? Size::guessedAt(self::numberAt($fields, 'guessed')) : Size::unknown();
    }

    /** @param array<array-key, mixed> $said */
    private static function standingIn(array $said): HowARequestStands
    {
        foreach ($said as $value) {
            return HowARequestStands::said((is_string($value) ? Waiting::tryFrom($value) : null) ?? throw KeptRequestsDoNotRead::at('standing'));
        }

        return HowARequestStands::unnamed();
    }

    /** @param array<array-key, mixed> $fields */
    private static function turnedDownIn(array $fields): TurnedDown
    {
        return TurnedDown::at(self::momentIn(self::listAt($fields, 'at'), 'at'), self::textAt($fields, 'reason'));
    }

    /**
     * The moment a kept list holds, or one unwritten where it holds none.
     *
     * @param array<array-key, mixed> $said
     */
    private static function momentIn(array $said, string $field): AMomentAsWritten
    {
        foreach ($said as $when) {
            return match (true) {
                is_int($when) => AMomentAsWritten::byTheStack($when),
                is_string($when) => AMomentAsWritten::of($when),
                default => throw KeptRequestsDoNotRead::at($field),
            };
        }

        return AMomentAsWritten::unwritten();
    }

    /** @return array<array-key, mixed> */
    private static function fieldsIn(mixed $decoded): array
    {
        return is_array($decoded) ? $decoded : throw KeptRequestsDoNotRead::asFields();
    }

    /**
     * @param array<array-key, mixed> $fields
     *
     * @return array<array-key, mixed>
     */
    private static function listAt(array $fields, string $field): array
    {
        $value = self::at($fields, $field);

        return is_array($value) ? $value : throw KeptRequestsDoNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function textAt(array $fields, string $field): string
    {
        $value = self::at($fields, $field);

        return is_string($value) ? $value : throw KeptRequestsDoNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function numberAt(array $fields, string $field): int
    {
        $value = self::at($fields, $field);

        return is_int($value) ? $value : throw KeptRequestsDoNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function at(array $fields, string $field): mixed
    {
        return array_key_exists($field, $fields) ? $fields[$field] : throw KeptRequestsDoNotRead::at($field);
    }
}
