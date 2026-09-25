<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ArchivesEnvelope;
use Modules\Kernel\Api\TheCopies;
use Modules\Sdk\Api\Fields\ArchivesField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `archives` envelope, as the copies a machine holds.
 *
 * Names, in the order the stack gave them. A name that is not text, or is
 * blank, refuses the whole listing rather than being dropped: a list of copies
 * one short is a copy somebody believes they do not have.
 */
final readonly class TheArchives
{
    /**
     * The copies a stack holds, by name.
     *
     * @param Envelope<mixed> $envelope the `archives` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheCopies
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data) || ! array_key_exists(ArchivesField::Archives->value, $data) || ! is_array($data[ArchivesField::Archives->value])) {
            throw ArchivesAreUnreadable::missing(ArchivesField::Archives);
        }

        return TheCopies::named(...self::names($data[ArchivesField::Archives->value]));
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
        return ArchivesEnvelope::in($envelope)->data;
    }

    /**
     * Every name, refusing one this app could not show.
     *
     * @param  array<mixed>  $listed
     * @return list<string>
     */
    private static function names(array $listed): array
    {
        $names = [];
        $position = 0;

        foreach ($listed as $name) {
            if (! is_string($name) || trim($name) === '') {
                throw ArchivesAreUnreadable::archive($position);
            }

            $names[] = $name;
            $position++;
        }

        return $names;
    }
}
