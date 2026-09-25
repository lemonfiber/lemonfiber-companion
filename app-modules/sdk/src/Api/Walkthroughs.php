<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\WalkthroughEnvelope;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\TheLinesItSaid;
use Modules\Kernel\Api\TheWalkthroughSaysNothing;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatComesNext;
use Modules\Kernel\Api\WhatCouldBeWalkedInstead;
use Modules\Kernel\Api\WhatTheServicesWereSaying;
use Modules\Kernel\Api\WhatToDoNext;
use Modules\Kernel\Api\WhatWasWalked;
use Modules\Kernel\Api\WhereItStopped;
use Modules\Kernel\Api\WhereTheWalkthroughIs;
use Modules\Kernel\Api\WhichWalk;
use Modules\Kernel\Api\WhyTheWalkthroughStopped;
use Modules\Sdk\Api\Fields\WalkthroughField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `walkthrough` envelope into what a walkthrough did.
 *
 * Every word is required to be one this app has a case for, and every
 * sentence the contract requires to be one; anything else is refused with
 * {@see WalkthroughIsUnreadable}, never defaulted. The lines are taken in the
 * order they arrived and handed on unaltered.
 */
final readonly class Walkthroughs
{
    /** @param Envelope<mixed> $envelope the `walkthrough` envelope, as the client returned it */
    public static function in(Envelope $envelope): AWalkthrough
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw WalkthroughIsUnreadable::missing(WireField::Data);
        }

        try {
            return self::read($data);
        } catch (TheWalkthroughSaysNothing $why) {
            throw WalkthroughIsUnreadable::because($why);
        }
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
        return WalkthroughEnvelope::in($envelope)->data;
    }

    /** @param array<array-key, mixed> $data */
    private static function read(array $data): AWalkthrough
    {
        $walk = AWalkthrough::reported(
            self::shape($data),
            self::state($data),
            self::text($data, WalkthroughField::Proves),
            self::item($data),
            self::lines($data),
            inBackground: self::flag($data, WalkthroughField::InBackground),
            alreadyHere: self::flag($data, WalkthroughField::AlreadyHere),
        )
            ->offering(WhatCouldBeWalkedInstead::of(...self::texts($data, WalkthroughField::Suggestions)))
            ->handingOnTo(self::handover($data));
        $link = self::link($data);
        $walk = $link instanceof HowTheImportLinked ? $walk->linked($link) : $walk;
        $stopped = self::stopped($data);

        return $stopped instanceof WhereItStopped ? $walk->stoppedAt($stopped) : $walk;
    }

    /** @param array<array-key, mixed> $data */
    private static function shape(array $data): WhichWalk
    {
        $said = self::text($data, WalkthroughField::Shape);

        return WhichWalk::tryFrom($said)
            ?? throw WalkthroughIsUnreadable::word(WalkthroughField::Shape, $said, ...array_map(static fn(WhichWalk $case): string => $case->value, WhichWalk::cases()));
    }

    /** @param array<array-key, mixed> $data */
    private static function state(array $data): WhereTheWalkthroughIs
    {
        $said = self::text($data, WireField::State);

        return WhereTheWalkthroughIs::tryFrom($said)
            ?? throw WalkthroughIsUnreadable::word(WireField::State, $said, ...array_map(static fn(WhereTheWalkthroughIs $case): string => $case->value, WhereTheWalkthroughIs::cases()));
    }

    /** @param array<array-key, mixed> $row */
    private static function step(array $row): WalkthroughStep
    {
        $said = self::text($row, WalkthroughField::Step);

        return WalkthroughStep::tryFrom($said)
            ?? throw WalkthroughIsUnreadable::word(WalkthroughField::Step, $said, ...array_map(static fn(WalkthroughStep $case): string => $case->value, WalkthroughStep::cases()));
    }

    /**
     * Every line, in the order the stack said them.
     *
     * An empty detail is the stack having nothing particular to say, which is
     * a line without one rather than a line with a blank one.
     *
     * @param array<array-key, mixed> $data
     */
    private static function lines(array $data): TheLinesItSaid
    {
        $lines = [];

        foreach (self::rows($data, WalkthroughField::Lines) as $row) {
            $line = self::table($row, WalkthroughField::Lines);
            $step = self::step($line);
            $said = self::text($line, WalkthroughField::Said);
            $detail = self::sentence($line, WireField::Detail);

            $lines[] = $detail === ''
                ? ALineItSaid::withoutDetail($step, $said)
                : ALineItSaid::withDetail($step, $said, $detail);
        }

        return TheLinesItSaid::of(...$lines);
    }

    /**
     * What it walked: absent or `null` where it never got as far as choosing.
     *
     * @param array<array-key, mixed> $data
     */
    private static function item(array $data): WhatWasWalked
    {
        if (self::absent($data, WireField::Item)) {
            return WhatWasWalked::nothingChosen();
        }

        return WhatWasWalked::called(self::text($data, WireField::Item));
    }

    /**
     * What the import did: absent or `null` where it never got that far.
     *
     * @param array<array-key, mixed> $data
     */
    private static function link(array $data): ?HowTheImportLinked
    {
        if (self::absent($data, WalkthroughField::Link)) {
            return null;
        }

        $said = self::text($data, WalkthroughField::Link);

        return HowTheImportLinked::tryFrom($said)
            ?? throw WalkthroughIsUnreadable::word(WalkthroughField::Link, $said, ...array_map(static fn(HowTheImportLinked $case): string => $case->value, HowTheImportLinked::cases()));
    }

    /**
     * Where it hands the operator on to: absent or `null` where it did not work, which names nowhere.
     *
     * @param array<array-key, mixed> $data
     */
    private static function handover(array $data): WhatComesNext
    {
        if (self::absent($data, WalkthroughField::Handover)) {
            return WhatComesNext::of();
        }

        $handover = self::table($data[WalkthroughField::Handover->value], WalkthroughField::Handover);
        $next = [];

        foreach (self::texts($handover, WalkthroughField::Next) as $said) {
            $next[] = WhatToDoNext::tryFrom($said)
                ?? throw WalkthroughIsUnreadable::word(WalkthroughField::Next, $said, ...array_map(static fn(WhatToDoNext $case): string => $case->value, WhatToDoNext::cases()));
        }

        return WhatComesNext::of(...$next);
    }

    /**
     * Where and why it stopped: absent or `null` where it did not.
     *
     * @param array<array-key, mixed> $data
     */
    private static function stopped(array $data): ?WhereItStopped
    {
        if (self::absent($data, WalkthroughField::Stopped)) {
            return null;
        }

        $stopped = self::table($data[WalkthroughField::Stopped->value], WalkthroughField::Stopped);
        $said = self::text($stopped, WireField::Reason);
        $why = WhyTheWalkthroughStopped::tryFrom($said)
            ?? throw WalkthroughIsUnreadable::word(WireField::Reason, $said, ...array_map(static fn(WhyTheWalkthroughStopped $case): string => $case->value, WhyTheWalkthroughStopped::cases()));

        return WhereItStopped::at(
            self::step($stopped),
            $why,
            self::text($stopped, WireField::Remedy),
            WhatTheServicesWereSaying::of(...self::texts($stopped, WalkthroughField::Logs)),
        );
    }

    /**
     * Whether an optional field is absent or `null`, which the contract makes the same.
     *
     * @param array<array-key, mixed> $row
     */
    private static function absent(array $row, NamesAWireField $field): bool
    {
        return ! array_key_exists($field->value, $row) || $row[$field->value] === null;
    }

    /**
     * A field holding a list.
     *
     * @param array<array-key, mixed> $row
     * @return list<mixed>
     */
    private static function rows(array $row, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $row) || ! is_array($row[$field->value]) || ! array_is_list($row[$field->value])) {
            throw WalkthroughIsUnreadable::missing($field);
        }

        return $row[$field->value];
    }

    /**
     * A field holding a list of text, each entry carried as it came.
     *
     * @param array<array-key, mixed> $row
     * @return list<string>
     */
    private static function texts(array $row, NamesAWireField $field): array
    {
        $texts = [];

        foreach (self::rows($row, $field) as $entry) {
            $texts[] = is_string($entry) ? $entry : throw WalkthroughIsUnreadable::missing($field);
        }

        return $texts;
    }

    /**
     * One entry of a list, or one field, which must be a table.
     *
     * @return array<array-key, mixed>
     */
    private static function table(mixed $entry, NamesAWireField $field): array
    {
        return is_array($entry) ? $entry : throw WalkthroughIsUnreadable::missing($field);
    }

    /** @param array<array-key, mixed> $row */
    private static function flag(array $row, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $row) || ! is_bool($row[$field->value])) {
            throw WalkthroughIsUnreadable::missing($field);
        }

        return $row[$field->value];
    }

    /**
     * Text the contract requires and allows to be empty, carried as it came.
     *
     * @param array<array-key, mixed> $row
     */
    private static function sentence(array $row, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value])) {
            throw WalkthroughIsUnreadable::missing($field);
        }

        return $row[$field->value];
    }

    /**
     * Text the contract requires to say something, refused where blank.
     *
     * @param array<array-key, mixed> $row
     */
    private static function text(array $row, NamesAWireField $field): string
    {
        $said = self::sentence($row, $field);

        if (trim($said) === '') {
            throw WalkthroughIsUnreadable::missing($field);
        }

        return $said;
    }
}
