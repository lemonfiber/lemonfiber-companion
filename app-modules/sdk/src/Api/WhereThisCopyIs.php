<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SelfUpdateEnvelope;
use Modules\Kernel\Api\HowItWouldBeUpdated;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowThisCopyGotThere;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Kernel\Api\WhatAnUpdateWouldBring;
use Modules\Kernel\Api\WhatIsReleased;
use Modules\Kernel\Api\WhereThisCopyStands;
use Modules\Sdk\Api\Fields\SelfUpdateField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `self-update` envelope into what the kernel knows about the running copy of lemonfiber.
 *
 * Every word is required to be one this app has a case for, and every sentence
 * the contract requires to be one. Anything else is refused with
 * {@see SelfUpdateIsUnreadable}, never defaulted. Optional sentences arrive as
 * `null` or absent and are read as empty.
 *
 * Only what the requirements ask for is read. The rest is recorded in
 * `WhatTheContractCarriesThatNothingReadsTest`, each with its reason.
 */
final readonly class WhereThisCopyIs
{
    /**
     * The running copy, how it got there, and what moving it would come to.
     *
     * @param Envelope<mixed> $envelope the `self-update` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ThisCopyOfLemonfiber
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SelfUpdateIsUnreadable::missing(WireField::Data);
        }

        return ThisCopyOfLemonfiber::reported(
            self::text($data, WireField::Running),
            HowThisCopyGotThere::by(self::installed($data), self::optional($data, SelfUpdateField::Owner)),
            self::standing($data),
            WhatIsReleased::said(self::optional($data, WireField::Offered), self::optional($data, WireField::Changed)),
            self::optional($data, SelfUpdateField::Untold),
            self::updatedBy($data),
            WhatAnUpdateWouldBring::said(self::text($data, SelfUpdateField::Carries), self::text($data, SelfUpdateField::Afterwards)),
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
        return SelfUpdateEnvelope::in($envelope)->data;
    }

    /**
     * How the running copy got onto the machine.
     *
     * @param array<mixed> $data
     */
    private static function installed(array $data): HowLemonfiberWasInstalled
    {
        $said = self::text($data, WireField::Installed);

        return HowLemonfiberWasInstalled::tryFrom($said)
            ?? throw SelfUpdateIsUnreadable::word(WireField::Installed, $said, ...array_map(static fn(HowLemonfiberWasInstalled $case): string => $case->value, HowLemonfiberWasInstalled::cases()));
    }

    /**
     * Where the running copy stands against what has been released.
     *
     * @param array<mixed> $data
     */
    private static function standing(array $data): WhereThisCopyStands
    {
        $said = self::text($data, WireField::Standing);

        return WhereThisCopyStands::tryFrom($said)
            ?? throw SelfUpdateIsUnreadable::word(WireField::Standing, $said, ...array_map(static fn(WhereThisCopyStands $case): string => $case->value, WhereThisCopyStands::cases()));
    }

    /**
     * The exact command, or why there is none, or neither.
     *
     * The command wins where the stack sent both, since a thing to type is the
     * more useful of the two.
     *
     * @param array<mixed> $data
     */
    private static function updatedBy(array $data): HowItWouldBeUpdated
    {
        $command = self::optional($data, WireField::Command);

        if ($command !== '') {
            return HowItWouldBeUpdated::byRunning($command);
        }

        $instead = self::optional($data, WireField::Instead);

        return $instead === '' ? HowItWouldBeUpdated::notSaid() : HowItWouldBeUpdated::insteadBecause($instead);
    }

    /**
     * A sentence the contract makes optional: empty where absent or `null`, refused where blank or not text.
     *
     * @param array<mixed> $data
     */
    private static function optional(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return '';
        }

        return self::text($data, $field);
    }

    /**
     * A required field, as text.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $data)) {
            throw SelfUpdateIsUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw SelfUpdateIsUnreadable::missing($field);
        }

        return $said;
    }
}
