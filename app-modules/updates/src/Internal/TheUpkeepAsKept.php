<?php

declare(strict_types=1);

namespace Modules\Updates\Internal;

use function array_key_exists;

use InvalidArgumentException;

use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;

/**
 * Where a stack stands on being up to date, as the phone writes it before sealing it and reads it back after.
 *
 * **Written in {@see Shape::One}, field for field what the stack reported**:
 * where the services stand against their pins, the release history, the
 * services taking the update would change and those nothing puts back, how
 * the notes stand, the files the operator edited, and the release running.
 * Nothing is worked out, so a reading read back draws as the one that was read.
 *
 * **What became of the last update is not written.** It is the outcome of an
 * action, and the confirmation of an action is never served from what the
 * phone kept; a reading read back says no update was taken.
 *
 * **Read back by the shape it says it was written in.** The match over
 * {@see Shape} is where a later layout has to be answered.
 *
 * **Anything that does not read is nothing.** What opens is what this phone
 * sealed; one that still does not read as a reading is let go of rather than
 * repaired, because the stack can always be asked again.
 */
final readonly class TheUpkeepAsKept
{
    /**
     * The reading as a value to seal, or nothing where it cannot be written.
     *
     * A stack whose words are not valid text cannot be written; it is shown
     * as it was read and kept nowhere.
     */
    public static function written(Upkeep $upkeep): ?Unsealed
    {
        $history = [];

        foreach ($upkeep->history() as $release) {
            $history[] = self::release($release);
        }

        $edits = [];

        foreach ($upkeep->editsKept() as $edit) {
            $edits[] = ['path' => $edit->path(), 'diff' => $edit->diff()];
        }

        $written = json_encode([
            'pins' => $upkeep->againstThePins()->value,
            'history' => $history,
            'changing' => self::names($upkeep->changing()),
            'permanent' => self::names($upkeep->cannotBePutBack()),
            'notes' => $upkeep->notes()->value,
            'edits' => $edits,
            'running' => $upkeep->inUse(
                named: static fn(Release $inUse): Fields => new Fields([self::release($inUse)]),
                unstated: static fn(): Fields => new Fields([]),
            )->held,
        ]);

        return $written === false ? null : Unsealed::of($written);
    }

    /** The reading a value written in this shape holds, or nothing where it does not read as one. */
    public static function read(Shape $shape, Unsealed $value): ?Upkeep
    {
        try {
            return match ($shape) {
                Shape::One => self::inShapeOne(self::fieldsIn(json_decode($value->inTheClear(), associative: true))),
            };
        } catch (InvalidArgumentException) {
            // A field missing or of the wrong type, and a kernel value
            // refusing what it was handed — a blank version, an unmarked line
            // of a diff — are the same answer: a reading that does not read.
            return null;
        }
    }

    /** @param array<array-key, mixed> $written */
    private static function inShapeOne(array $written): Upkeep
    {
        $history = [];

        foreach (self::listAt($written, 'history') as $release) {
            $history[] = self::releaseIn(self::fieldsIn($release));
        }

        $edits = [];

        foreach (self::listAt($written, 'edits') as $edit) {
            $fields = self::fieldsIn($edit);
            $edits[] = AStackEdit::at(self::textAt($fields, 'path'), self::textAt($fields, 'diff'));
        }

        $upkeep = Upkeep::reported(
            AgainstThePins::tryFrom(self::textAt($written, 'pins')) ?? throw KeptUpkeepDoesNotRead::at('pins'),
            Releases::these(...$history),
            self::servicesAt($written, 'changing'),
            self::servicesAt($written, 'permanent'),
            HowServicesTookIt::none(),
            HowTheNotesStand::tryFrom(self::textAt($written, 'notes')) ?? throw KeptUpkeepDoesNotRead::at('notes'),
            TheStackEdits::these(...$edits),
        );

        foreach (self::listAt($written, 'running') as $inUse) {
            $upkeep = $upkeep->runningOn(self::releaseIn(self::fieldsIn($inUse)));
        }

        return $upkeep;
    }

    /** @return array<string, mixed> */
    private static function release(Release $release): array
    {
        return [
            'version' => $release->version(),
            'noticeable' => $release->theHouseholdWouldNotice(),
            'withdrawn' => $release->wasWithdrawn(),
            'delivers' => $release->delivers()->either(
                said: static fn(string $prose): Fields => new Fields([$prose]),
                saidNothing: static fn(): Fields => new Fields([]),
            )->held,
        ];
    }

    /** @param array<array-key, mixed> $fields */
    private static function releaseIn(array $fields): Release
    {
        $delivers = WhatAReleaseDelivers::saidNothing();

        foreach (self::listAt($fields, 'delivers') as $prose) {
            $delivers = is_string($prose) ? WhatAReleaseDelivers::said($prose) : throw KeptUpkeepDoesNotRead::at('delivers');
        }

        return Release::called(
            self::textAt($fields, 'version'),
            noticeable: self::flagAt($fields, 'noticeable'),
            withdrawn: self::flagAt($fields, 'withdrawn'),
            delivers: $delivers,
        );
    }

    /** @return list<string> */
    private static function names(Services $services): array
    {
        $names = [];

        foreach ($services as $service) {
            $names[] = $service->named();
        }

        return $names;
    }

    /** @param array<array-key, mixed> $fields */
    private static function servicesAt(array $fields, string $field): Services
    {
        $services = [];

        foreach (self::listAt($fields, $field) as $name) {
            $services[] = ServiceId::called(is_string($name) ? $name : throw KeptUpkeepDoesNotRead::at($field));
        }

        return Services::these(...$services);
    }

    /** @return array<array-key, mixed> */
    private static function fieldsIn(mixed $decoded): array
    {
        return is_array($decoded) ? $decoded : throw KeptUpkeepDoesNotRead::asFields();
    }

    /**
     * @param array<array-key, mixed> $fields
     *
     * @return array<array-key, mixed>
     */
    private static function listAt(array $fields, string $field): array
    {
        $value = self::at($fields, $field);

        return is_array($value) ? $value : throw KeptUpkeepDoesNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function textAt(array $fields, string $field): string
    {
        $value = self::at($fields, $field);

        return is_string($value) ? $value : throw KeptUpkeepDoesNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function flagAt(array $fields, string $field): bool
    {
        $value = self::at($fields, $field);

        return is_bool($value) ? $value : throw KeptUpkeepDoesNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function at(array $fields, string $field): mixed
    {
        return array_key_exists($field, $fields) ? $fields[$field] : throw KeptUpkeepDoesNotRead::at($field);
    }
}
