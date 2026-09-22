<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

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
    public static function missing(WireField $field): self
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
            WireField::Settings->value,
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
            WireField::Settings->value,
            $position,
        ));
    }

    /**
     * The origin names a word this app does not know.
     *
     * Apart from {@see missing()} because it is a different defect and points
     * somewhere else. Missing means the answer did not come from a lemonfiber
     * this app can read; this means it came from a *newer* one, which added an
     * arm to the origin and this app has not been taught it. The word is
     * quoted because it is the only thing that finds the release that added
     * it.
     */
    public static function anOriginNobodyHere(string $word): self
    {
        return new self(sprintf(
            'The config envelope attributes a setting to `%s`, which is not a `%s` this app knows. This answer came from a lemonfiber newer than this app.',
            $word,
            WireField::Origin->value,
        ));
    }
}
