<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Sdk\Api\Fields\ConfigField;

use function sprintf;

/**
 * The `config` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see HouseholdIsUnreadable} is, for the payload the
 * settings screen reads. Every one of these is a bug somewhere other than
 * here, so the message names the field: that is the only thing that shortens
 * the search. A developer reads it, so it is `sprintf` and never translated
 * (`L1`).
 *
 * **A settings listing is worth refusing rather than salvaging, and the reason
 * is sharper than for a report.** A listing one row short is a setting the
 * operator cannot see and therefore cannot change, on a screen whose whole
 * promise is that it shows all of them. There is nothing on the screen to say
 * a row was dropped, and the operator's next move — concluding the stack does
 * not have that setting — is wrong in a way nothing corrects.
 */
final class SettingIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The config envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function notAList(): self
    {
        return new self(sprintf(
            'The config envelope\'s `%s` is not a list of settings. This answer did not come from a lemonfiber of a version this app can read.',
            ConfigField::Settings->value,
        ));
    }

    /**
     * One row of the listing is not a setting, named by where it was.
     *
     * Apart from {@see notAList()} because they are different defects and the
     * message that fits one is wrong about the other: there the listing is not
     * a list, here it is, and one entry in it is not a setting. Naming the
     * position is what makes the second searchable — a stack sending eighty
     * settings and one bad row is otherwise a message with nowhere to look.
     */
    public static function row(int $position): self
    {
        return new self(sprintf(
            'The config envelope\'s `%s` has something at position %d that is not a setting. This answer did not come from a lemonfiber of a version this app can read.',
            ConfigField::Settings->value,
            $position,
        ));
    }

    /**
     * One setting could not say who put its value there.
     *
     * What was wrong with the origin is the previous exception's to say, and
     * its message is carried in this one's so a developer reading one line has
     * both halves: which listing, and what about the table.
     */
    public static function origin(OriginIsUnreadable $why): self
    {
        return new self(sprintf(
            'A setting in the config envelope cannot say where its value came from. %s',
            $why->getMessage(),
        ), previous: $why);
    }
}
