<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\FormsEnvelope;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `forms` envelope, as every form a stack declares.
 *
 * Written the way {@see Rosters} is — a static fold with no state, reading
 * through {@see WireField} and refusing rather than salvaging.
 *
 * **This is the only reading that lists a stack's forms.** The `status`
 * envelope carries a `forms` field too, and it is a different fact: the forms
 * that reading was asked about, which for the whole-stack reading is none. Nor
 * is a form anything a service row says — each row carries the one compose
 * profile it belongs to, and a profile is not a form. Only this answer names
 * the forms a verb can be asked for by.
 *
 * **The `id` is what is read**, because it is what the stack takes a form by:
 * `up`, `down` and `restart` are told the id, and a form shown by anything else
 * would be a control whose label and whose request disagree.
 */
final readonly class Repertoires
{
    /**
     * Every form the stack declares, in the order it declares them.
     *
     * @param Envelope<mixed> $envelope the `forms` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Forms
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw RepertoireIsUnreadable::missing(WireField::Data);
        }

        $forms = [];
        $position = 0;

        foreach (self::listed($data) as $row) {
            if (! is_array($row)) {
                throw RepertoireIsUnreadable::item($position);
            }

            $forms[] = Form::called(self::id($row, $position));
            $position++;
        }

        return Forms::these(...$forms);
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Rosters::payload()}'s reason: the
     * generated envelope asserts its shape without checking it.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return FormsEnvelope::in($envelope)->data;
    }

    /**
     * The list of forms, as it arrived.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function listed(array $data): array
    {
        if (! array_key_exists(WireField::Forms->value, $data)) {
            throw RepertoireIsUnreadable::missing(WireField::Forms);
        }

        $listed = $data[WireField::Forms->value];

        if (! is_array($listed)) {
            throw RepertoireIsUnreadable::missing(WireField::Forms);
        }

        return $listed;
    }

    /**
     * What one form is asked for by.
     *
     * Blank is refused here rather than left to {@see Form::called()} because
     * the position is only knowable here.
     *
     * @param array<mixed> $row
     */
    private static function id(array $row, int $position): string
    {
        if (! array_key_exists(WireField::Id->value, $row)) {
            throw RepertoireIsUnreadable::unnamed($position);
        }

        $said = $row[WireField::Id->value];

        if (! is_string($said) || trim($said) === '') {
            throw RepertoireIsUnreadable::unnamed($position);
        }

        return $said;
    }
}
