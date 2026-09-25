<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\BackupEnvelope;
use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Fields\BackupField;
use Modules\Sdk\Internal\Scopes;
use Modules\Sdk\Internal\Wire;

/**
 * The `backup` envelope, as what taking a copy came to.
 *
 * What it covered, what it removed, how it paced, whether it holds
 * credentials and whether it was a rehearsal, each required. Where the copy
 * was written on the machine is not read: a copy is asked for by the name
 * the listing of copies gives it, and a path on a machine the operator has no
 * filesystem in front of names nothing they can use.
 */
final readonly class TheCopyTaken
{
    /**
     * The stack's report of one copy.
     *
     * @param Envelope<mixed> $envelope the `backup` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ACopyTaken
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw BackupIsUnreadable::missing(WireField::Data);
        }

        return ACopyTaken::reported(
            Scopes::in($data),
            TheCopies::named(...self::pruned($data)),
            self::pace($data),
            WhetherItHoldsASecret::said(self::flag($data, BackupField::Sensitive)),
            WhetherItWasRehearsed::said(self::flag($data, BackupField::Rehearsed)),
        );
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return BackupEnvelope::in($envelope)->data;
    }

    /**
     * The older copies it removed, by name.
     *
     * @param  array<array-key, mixed> $data
     * @return list<string>
     */
    private static function pruned(array $data): array
    {
        if (! array_key_exists(BackupField::Pruned->value, $data)) {
            throw BackupIsUnreadable::missing(BackupField::Pruned);
        }

        $listed = $data[BackupField::Pruned->value];

        if (! is_array($listed) || ! array_is_list($listed)) {
            throw BackupIsUnreadable::missing(BackupField::Pruned);
        }

        $names = [];

        foreach ($listed as $position => $name) {
            if (! is_string($name)) {
                throw BackupIsUnreadable::pruned($position);
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * How the copy's size stood against the budget.
     *
     * @param array<array-key, mixed> $data
     */
    private static function pace(array $data): HowACopyPaced
    {
        if (! array_key_exists(BackupField::Pace->value, $data) || ! is_array($data[BackupField::Pace->value])) {
            throw BackupIsUnreadable::missing(BackupField::Pace);
        }

        $pace = $data[BackupField::Pace->value];

        return HowACopyPaced::measured(
            moved: self::bytes($pace, BackupField::Moved),
            budget: self::bytes($pace, BackupField::Budget),
            brisk: self::flag($pace, BackupField::Brisk),
        );
    }

    /**
     * A figure the pace must carry.
     *
     * @param array<array-key, mixed> $pace
     */
    private static function bytes(array $pace, NamesAWireField $field): int
    {
        if (! array_key_exists($field->value, $pace) || ! is_int($pace[$field->value])) {
            throw BackupIsUnreadable::missing($field);
        }

        return $pace[$field->value];
    }

    /**
     * A yes or no the report must carry.
     *
     * @param array<array-key, mixed> $data
     */
    private static function flag(array $data, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw BackupIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
