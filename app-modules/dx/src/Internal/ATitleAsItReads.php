<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_key_exists;
use function is_array;

use Modules\Sdk\Api\Fields\TitleField;
use Modules\Sdk\Api\WireField;

/**
 * One title on a stand-in stack, as a reader can take it: undated, and with no front door.
 *
 * The `title` envelope's own declaration, corrected where the generated type
 * leaves the shape open. A synthesised release day is the word `released`,
 * and a synthesised location and fingerprint are words no door serves, which
 * the reader refuses as it would refuse any stack that wrote them. A stand-in
 * has no door to stream through, so the title and each episode carry a reason
 * in the place of a location, as a stack that knows no household address
 * does: the field's own name, which is what makes a stand-in's words obvious
 * on a screen.
 */
final readonly class ATitleAsItReads
{
    /**
     * The envelope, corrected.
     *
     * @param array<string, mixed> $envelope the envelope as synthesised from the declaration
     *
     * @return array<string, mixed>
     */
    public static function from(array $envelope): array
    {
        $report = self::fieldsOf($envelope['data']);
        $title = self::fieldsOf(self::at($report, WireField::Title->value));
        $seasons = [];

        foreach (self::fieldsOf(self::at($title, WireField::Seasons->value)) as $season) {
            $fields = self::fieldsOf($season);
            $episodes = [];

            foreach (self::fieldsOf(self::at($fields, TitleField::Episodes->value)) as $episode) {
                $episodes[] = self::undoored(self::fieldsOf($episode));
            }

            $seasons[] = [...$fields, TitleField::Episodes->value => $episodes];
        }

        $report[WireField::Title->value] = [...self::undoored($title), TitleField::Released->value => null, WireField::Seasons->value => $seasons];
        $envelope['data'] = $report;

        return $envelope;
    }

    /**
     * What a synthesised value holds, or nothing where it is not a table.
     *
     * @return array<array-key, mixed>
     */
    private static function fieldsOf(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /** @param array<array-key, mixed> $fields */
    private static function at(array $fields, string $field): mixed
    {
        if (! array_key_exists($field, $fields)) {
            return null;
        }

        return $fields[$field];
    }

    /**
     * An item with no location, and a reason in its place.
     *
     * @param array<array-key, mixed> $item
     *
     * @return array<array-key, mixed>
     */
    private static function undoored(array $item): array
    {
        return [
            ...$item,
            TitleField::StreamFrom->value => null,
            WireField::Door->value => null,
            TitleField::Unlocated->value => TitleField::Unlocated->value,
        ];
    }
}
