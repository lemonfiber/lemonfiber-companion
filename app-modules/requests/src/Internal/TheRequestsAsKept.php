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

use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

/**
 * What a household asked its stack for, as the phone writes it before sealing it and reads it back after.
 *
 * **Written in {@see Shape::One}, field for field what the stack reported**:
 * each request's number, who asked, what for, how big it is and whether that
 * was measured or guessed, where it stands, and, where it was turned down, the
 * reason the stack gave and when. Nothing is worked out, so a reading read back
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
            $requests[] = self::wantedIn(self::fieldsIn($request));
        }

        return Requested::of(...$requests);
    }

    /** @return array<string, mixed> */
    private static function wanted(Wanted $wanted): array
    {
        return [
            'number' => $wanted->number(),
            'by' => $wanted->by(),
            'for' => $wanted->forWhat(),
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
                    'at' => $why->when(
                        then: static fn(string $when): Written => new Written([$when]),
                        unstated: static fn(): Written => new Written([]),
                    )->held,
                ]]),
                wasNot: static fn(): Written => new Written([]),
            )->held,
        ];
    }

    /** @param array<array-key, mixed> $fields */
    private static function wantedIn(array $fields): Wanted
    {
        $number = self::numberAt($fields, 'number');
        $by = self::textAt($fields, 'by');
        $for = self::textAt($fields, 'for');
        $size = self::sizeIn(self::fieldsIn(self::at($fields, 'size')));

        foreach (self::listAt($fields, 'refused') as $refused) {
            return Wanted::turnedDown($number, $by, $for, $size, self::turnedDownIn(self::fieldsIn($refused)));
        }

        return Wanted::of($number, $by, $for, $size, self::standingIn(self::listAt($fields, 'standing')));
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
            return HowARequestStands::said(Waiting::tryFrom(is_string($value) ? $value : '') ?? throw KeptRequestsDoNotRead::at('standing'));
        }

        return HowARequestStands::unnamed();
    }

    /** @param array<array-key, mixed> $fields */
    private static function turnedDownIn(array $fields): TurnedDown
    {
        $reason = self::textAt($fields, 'reason');

        foreach (self::listAt($fields, 'at') as $when) {
            return TurnedDown::at(is_string($when) ? $when : throw KeptRequestsDoNotRead::at('at'), $reason);
        }

        return TurnedDown::because($reason);
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
