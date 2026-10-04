<?php

declare(strict_types=1);

namespace Modules\Services\Internal;

use function array_key_exists;

use InvalidArgumentException;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatToDoWithIt;

/**
 * What a stack runs, as the phone writes it before sealing it and reads it back after.
 *
 * **Written in {@see Shape::One}, field for field what the stack reported**:
 * how the stack is running, what each verb takes away, every service with how
 * it runs, how much it matters, what leans on it, the code it exited on and the
 * forms that brought it in, the forms asked for, and each service those forms
 * left out with what it would need. Nothing is worked out, so a listing read
 * back draws as the one that was read.
 *
 * **Only the listing.** An action, its confirmation and what a start waits on
 * are never handed here, so none of them can be written.
 *
 * **Read back by the shape it says it was written in**, and anything that
 * does not read is nothing: the stack can always be asked again.
 */
final readonly class TheListingAsKept
{
    /** A verb whose length the stack bounded in seconds, written as the count; one it left open is written as what it awaits. */
    private const string BOUNDED = 'at_most';

    private const string OPEN_ENDED = 'lasts_until';

    /**
     * The listing as a value to seal, or nothing where it cannot be written.
     *
     * A stack whose words are not valid text cannot be written; it is shown
     * as it was read and kept nowhere.
     */
    public static function written(Daemons $daemons): ?Unsealed
    {
        $services = [];

        foreach ($daemons as $daemon) {
            $services[] = self::daemon($daemon);
        }

        $leftOut = [];

        foreach ($daemons->leftOut() as $left) {
            $leftOut[] = ['id' => $left->id()->named(), 'name' => $left->name(), 'needs' => $left->needs()->value, 'asked_by' => self::forms($left->askedBy())];
        }

        $disturbs = [];

        foreach ([WhatToDoWithIt::Start, WhatToDoWithIt::Stop, WhatToDoWithIt::Restart] as $doing) {
            $disturbs[$doing->value] = $daemons->disturbs()->forThe(
                $doing,
                said: static fn(WhatItTakesAway $takes): Written => $takes->either(
                    bounded: static fn(int $seconds): Written => new Written([self::BOUNDED => $seconds]),
                    openEnded: static fn(Awaiting $awaiting): Written => new Written([self::OPEN_ENDED => $awaiting->value]),
                ),
                unreported: static fn(): Written => new Written([]),
            )->held;
        }

        $written = json_encode([
            'running' => $daemons->running()->value,
            'disturbs' => $disturbs,
            'services' => $services,
            'active' => self::forms($daemons->active()),
            'left_out' => $leftOut,
        ]);

        return $written === false ? null : Unsealed::of($written);
    }

    /** The listing a value written in this shape holds, or nothing where it does not read as one. */
    public static function read(Shape $shape, Unsealed $value): ?Daemons
    {
        try {
            return match ($shape) {
                Shape::One => self::inShapeOne(self::fieldsIn(json_decode($value->inTheClear(), associative: true))),
            };
        } catch (InvalidArgumentException) {
            // A field missing or of the wrong type, and a kernel value
            // refusing what it was handed — a blank name, an unnamed form —
            // are the same answer: a listing that does not read.
            return null;
        }
    }

    /** @param array<array-key, mixed> $written */
    private static function inShapeOne(array $written): Daemons
    {
        $services = [];

        foreach (self::listAt($written, 'services') as $service) {
            $services[] = self::daemonIn(self::fieldsIn($service));
        }

        $leftOut = [];

        foreach (self::listAt($written, 'left_out') as $left) {
            $fields = self::fieldsIn($left);
            $leftOut[] = AServiceLeftOut::needing(
                ServiceId::called(self::textAt($fields, 'id')),
                self::textAt($fields, 'name'),
                WhatItWouldNeed::tryFrom(self::textAt($fields, 'needs')) ?? throw KeptListingDoesNotRead::at('needs'),
                self::formsAt($fields, 'asked_by'),
            );
        }

        $disturbs = self::fieldsIn(self::at($written, 'disturbs'));

        return Daemons::of(
            HowTheStackIsRunning::tryFrom(self::textAt($written, 'running')) ?? throw KeptListingDoesNotRead::at('running'),
            Disturbances::of(
                self::takesAwayIn(self::fieldsIn(self::at($disturbs, WhatToDoWithIt::Start->value))),
                self::takesAwayIn(self::fieldsIn(self::at($disturbs, WhatToDoWithIt::Stop->value))),
                self::takesAwayIn(self::fieldsIn(self::at($disturbs, WhatToDoWithIt::Restart->value))),
            ),
            ...$services,
        )->asked(self::formsAt($written, 'active'), TheServicesLeftOut::of(...$leftOut));
    }

    /** @return array<string, mixed> */
    private static function daemon(Daemon $daemon): array
    {
        $leaning = [];

        foreach ($daemon->whatLeansOnIt() as $id) {
            $leaning[] = $id->named();
        }

        return [
            'id' => $daemon->id()->named(),
            'name' => $daemon->name(),
            'runs' => $daemon->runs()->value,
            'matters' => $daemon->matters()->value,
            'leaning' => $leaning,
            'exited' => $daemon->exit(
                said: static fn(int $code): Written => new Written([$code]),
                unstated: static fn(): Written => new Written([]),
            )->held,
            'brought_in_by' => self::forms($daemon->whatBroughtItIn()),
        ];
    }

    /** @param array<array-key, mixed> $fields */
    private static function daemonIn(array $fields): Daemon
    {
        $leaning = [];

        foreach (self::listAt($fields, 'leaning') as $id) {
            $leaning[] = ServiceId::called(is_string($id) ? $id : throw KeptListingDoesNotRead::at('leaning'));
        }

        $name = self::textAt($fields, 'name');
        $id = ServiceId::called(self::textAt($fields, 'id'));
        $runs = HowAServiceRuns::tryFrom(self::textAt($fields, 'runs')) ?? throw KeptListingDoesNotRead::at('runs');
        $matters = HowMuchItMatters::tryFrom(self::textAt($fields, 'matters')) ?? throw KeptListingDoesNotRead::at('matters');
        $daemon = Daemon::called($name, $id, $runs, $matters, WhatLeansOnIt::these(...$leaning));

        foreach (self::listAt($fields, 'exited') as $code) {
            $daemon = Daemon::thatExited($name, $id, $runs, $matters, WhatLeansOnIt::these(...$leaning), is_int($code) ? $code : throw KeptListingDoesNotRead::at('exited'));
        }

        return $daemon->broughtInBy(self::formsAt($fields, 'brought_in_by'));
    }

    /** @param array<array-key, mixed> $fields */
    private static function takesAwayIn(array $fields): WhatItTakesAway
    {
        if (array_key_exists(self::BOUNDED, $fields)) {
            $seconds = $fields[self::BOUNDED];

            return WhatItTakesAway::atMost(is_int($seconds) ? $seconds : throw KeptListingDoesNotRead::at(self::BOUNDED));
        }

        return WhatItTakesAway::until(Awaiting::tryFrom(self::textAt($fields, self::OPEN_ENDED)) ?? throw KeptListingDoesNotRead::at(self::OPEN_ENDED));
    }

    /** @return list<string> */
    private static function forms(Forms $forms): array
    {
        $names = [];

        foreach ($forms as $form) {
            $names[] = $form->named();
        }

        return $names;
    }

    /** @param array<array-key, mixed> $fields */
    private static function formsAt(array $fields, string $field): Forms
    {
        $forms = [];

        foreach (self::listAt($fields, $field) as $name) {
            $forms[] = Form::called(is_string($name) ? $name : throw KeptListingDoesNotRead::at($field));
        }

        return Forms::these(...$forms);
    }

    /** @return array<array-key, mixed> */
    private static function fieldsIn(mixed $decoded): array
    {
        return is_array($decoded) ? $decoded : throw KeptListingDoesNotRead::asFields();
    }

    /**
     * @param array<array-key, mixed> $fields
     *
     * @return array<array-key, mixed>
     */
    private static function listAt(array $fields, string $field): array
    {
        $value = self::at($fields, $field);

        return is_array($value) ? $value : throw KeptListingDoesNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function textAt(array $fields, string $field): string
    {
        $value = self::at($fields, $field);

        return is_string($value) ? $value : throw KeptListingDoesNotRead::at($field);
    }

    /** @param array<array-key, mixed> $fields */
    private static function at(array $fields, string $field): mixed
    {
        return array_key_exists($field, $fields) ? $fields[$field] : throw KeptListingDoesNotRead::at($field);
    }
}
