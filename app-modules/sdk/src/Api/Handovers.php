<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HostingEnvelope;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\TheFilesTouched;
use Modules\Kernel\Api\WhatTheHandoverDid;
use Modules\Sdk\Api\Fields\HostingField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `hosting` envelope an install or a removal answers with, as what it did.
 *
 * {@see Hosts}' sibling for the same envelope read after an act, and written the
 * same way: a static fold with no state, refusing rather than salvaging.
 *
 * **What it changed is required here.** A reading carries no `changed` and an
 * act always does, so an act answered without one is an answer to some other
 * question, and it is refused rather than shown as having happened.
 *
 * **Where the command stands, and where its words go, come off its own row.**
 * `changed` names the command and the listing beside it says where it now
 * stands, which is what an install is reported by: the manager's answer after
 * the act, never the act having been carried out.
 */
final readonly class Handovers
{
    /**
     * What one install or removal did, as the stack reported it.
     *
     * @param Envelope<mixed> $envelope the `hosting` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheHandoverDid
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HostingIsUnreadable::missing(WireField::Data);
        }

        $changed = self::changed($data);
        $name = self::word($changed);
        $row = self::row($data, $name);
        $started = self::flag($changed, HostingField::Started);

        if (self::flag($changed, WireField::Installed)) {
            return self::installed($changed, $row, $name);
        }

        if ($started) {
            throw HostingIsUnreadable::startedByRemoving();
        }

        return WhatTheHandoverDid::removing(
            name: $name,
            rehearsed: self::flag($changed, HostingField::Rehearsed),
            standing: self::standing($row),
            touched: self::touched($changed),
        );
    }

    /**
     * An install, and where the stack said the command's words go, or that it did not say.
     *
     * @param array<mixed> $changed
     * @param array<mixed> $row
     */
    private static function installed(array $changed, array $row, string $name): WhatTheHandoverDid
    {
        $output = self::output($row, $name);

        if ($output === null) {
            return WhatTheHandoverDid::installingWithNowhereSaid(
                name: $name,
                rehearsed: self::flag($changed, HostingField::Rehearsed),
                started: self::flag($changed, HostingField::Started),
                standing: self::standing($row),
                touched: self::touched($changed),
            );
        }

        return WhatTheHandoverDid::installing(
            name: $name,
            rehearsed: self::flag($changed, HostingField::Rehearsed),
            started: self::flag($changed, HostingField::Started),
            standing: self::standing($row),
            output: $output,
            touched: self::touched($changed),
        );
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Hosts::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return HostingEnvelope::in($envelope)->data;
    }

    /**
     * What the run changed, which an act's answer must carry.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function changed(array $data): array
    {
        if (! array_key_exists(WireField::Changed->value, $data)) {
            throw HostingIsUnreadable::changed(WireField::Changed);
        }

        $changed = $data[WireField::Changed->value];

        if (! is_array($changed)) {
            throw HostingIsUnreadable::changed(WireField::Changed);
        }

        return $changed;
    }

    /**
     * The command it acted on, as lemonfiber names it.
     *
     * @param array<mixed> $changed
     */
    private static function word(array $changed): string
    {
        if (! array_key_exists(WireField::Name->value, $changed)) {
            throw HostingIsUnreadable::changed(WireField::Name);
        }

        $said = $changed[WireField::Name->value];

        if (! is_string($said) || trim($said) === '') {
            throw HostingIsUnreadable::changed(WireField::Name);
        }

        return $said;
    }

    /**
     * One of the yes-or-no fields of what it changed.
     *
     * Required and never defaulted: a missing `rehearsed` read as false is a
     * rehearsal shown as a real install, and a missing `installed` read either
     * way is a removal reported as an install or the other way round.
     *
     * @param array<mixed> $changed
     */
    private static function flag(array $changed, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $changed)) {
            throw HostingIsUnreadable::changed($field);
        }

        $said = $changed[$field->value];

        if (! is_bool($said)) {
            throw HostingIsUnreadable::changed($field);
        }

        return $said;
    }

    /**
     * Every file it wrote or took back, refusing any entry that is not one.
     *
     * @param array<mixed> $changed
     */
    private static function touched(array $changed): TheFilesTouched
    {
        if (! array_key_exists(HostingField::Touched->value, $changed)) {
            throw HostingIsUnreadable::changed(HostingField::Touched);
        }

        $listed = $changed[HostingField::Touched->value];

        if (! is_array($listed)) {
            throw HostingIsUnreadable::changed(HostingField::Touched);
        }

        $files = [];

        foreach ($listed as $file) {
            if (! is_string($file) || trim($file) === '') {
                throw HostingIsUnreadable::changed(HostingField::Touched);
            }

            $files[] = $file;
        }

        return TheFilesTouched::these(...$files);
    }

    /**
     * The listed row for the command it acted on.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function row(array $data, string $name): array
    {
        if (! array_key_exists(HostingField::Commands->value, $data) || ! is_array($data[HostingField::Commands->value])) {
            throw HostingIsUnreadable::missing(HostingField::Commands);
        }

        foreach ($data[HostingField::Commands->value] as $row) {
            if (is_array($row) && self::names($row, $name)) {
                return $row;
            }
        }

        throw HostingIsUnreadable::unlisted($name);
    }

    /**
     * Whether one listed row is the command of that name.
     *
     * @param array<mixed> $row
     */
    private static function names(array $row, string $name): bool
    {
        return array_key_exists(WireField::Name->value, $row) && $row[WireField::Name->value] === $name;
    }

    /**
     * Where that command stands now, as a word this app reads.
     *
     * @param array<mixed> $row
     */
    private static function standing(array $row): HowItIsHosted
    {
        if (! array_key_exists(WireField::Standing->value, $row) || ! is_string($row[WireField::Standing->value])) {
            throw HostingIsUnreadable::missing(WireField::Standing);
        }

        return HowItIsHosted::tryFrom($row[WireField::Standing->value])
            ?? throw HostingIsUnreadable::missing(WireField::Standing);
    }

    /**
     * Where that command writes its words, or nothing where the stack did not say.
     *
     * Absent and null are the stack not saying, which the contract allows. A
     * value that is there and is not a path is refused rather than read as
     * either.
     *
     * @param array<mixed> $row
     */
    private static function output(array $row, string $name): ?string
    {
        if (! array_key_exists(HostingField::Output->value, $row) || $row[HostingField::Output->value] === null) {
            return null;
        }

        $said = $row[HostingField::Output->value];

        if (! is_string($said) || trim($said) === '') {
            throw HostingIsUnreadable::output($name);
        }

        return $said;
    }
}
