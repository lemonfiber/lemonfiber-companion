<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_string;

use Modules\Kernel\Api\ACertificateReplacement;
use Modules\Kernel\Api\AHolderReached;
use Modules\Kernel\Api\HowFarAHolderWasReached;
use Modules\Kernel\Api\WhereAReplacementSettled;
use Modules\Sdk\Api\CredentialsIsUnreadable;
use Modules\Sdk\Api\Fields\CredentialsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;

/**
 * What replacing the internal certificate would do or came to, as the `credentials` envelope's `rotated` carries it.
 *
 * Apart from {@see \Modules\Sdk\Api\CredentialsKept} for its size. Which
 * credential it was, where its value is kept, what is owed afterwards and the
 * names a stack would have accepted are not read, each with its reason in
 * `WhatThisAppDoesNotRead`.
 */
final readonly class CertificateReplacementRead
{
    /**
     * What the replacement would do or came to, which an answer to replacing it must carry.
     *
     * @param array<mixed> $data
     */
    public static function rotated(array $data): ACertificateReplacement
    {
        if (! array_key_exists(CredentialsField::Rotated->value, $data) || ! is_array($data[CredentialsField::Rotated->value])) {
            throw CredentialsIsUnreadable::missing(CredentialsField::Rotated);
        }

        $rotated = $data[CredentialsField::Rotated->value];
        $settled = Required::rows($rotated, WireField::Settled, CredentialsIsUnreadable::replacement(WireField::Settled));
        $where = self::where($settled);

        if ($where === WhereAReplacementSettled::Rehearsed) {
            return ACertificateReplacement::read(self::offer($rotated), self::text($settled, WireField::Detail));
        }

        return ACertificateReplacement::settled($where, self::said($where, $settled), ...self::holders($rotated));
    }

    /**
     * Where it settled, refused where the stack said a word this app has no case for.
     *
     * @param array<mixed> $settled
     */
    private static function where(array $settled): WhereAReplacementSettled
    {
        $said = self::text($settled, WireField::Settled);

        return WhereAReplacementSettled::tryFrom($said)
            ?? throw CredentialsIsUnreadable::replacementSaid(WireField::Settled, $said, ...array_map(static fn(WhereAReplacementSettled $case): string => $case->value, WhereAReplacementSettled::cases()));
    }

    /**
     * What the stack said of where it settled: what a landed one observed, nothing for a name nothing answers to, and its detail otherwise.
     *
     * @param array<mixed> $settled
     */
    private static function said(WhereAReplacementSettled $where, array $settled): string
    {
        return match ($where) {
            WhereAReplacementSettled::Replaced => self::text($settled, CredentialsField::Observed),
            WhereAReplacementSettled::Unknown => '',
            WhereAReplacementSettled::Rehearsed,
            WhereAReplacementSettled::ReplacedUnproven,
            WhereAReplacementSettled::Unproven,
            WhereAReplacementSettled::Refused,
            WhereAReplacementSettled::Elsewhere => self::text($settled, WireField::Detail),
        };
    }

    /**
     * The offer a yes quotes, empty where the reading named none.
     *
     * @param array<mixed> $rotated
     */
    private static function offer(array $rotated): string
    {
        if (! array_key_exists(WireField::Offer->value, $rotated) || $rotated[WireField::Offer->value] === null) {
            return '';
        }

        return is_string($rotated[WireField::Offer->value]) ? $rotated[WireField::Offer->value] : throw CredentialsIsUnreadable::replacement(WireField::Offer);
    }

    /**
     * Each holder, and how far the replacement reached it.
     *
     * @param  array<mixed>          $rotated
     * @return list<AHolderReached>
     */
    private static function holders(array $rotated): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(Required::rows($rotated, CredentialsField::Consumers, CredentialsIsUnreadable::replacement(CredentialsField::Consumers))) as $row) {
            if (! is_array($row)) {
                throw CredentialsIsUnreadable::replacement(CredentialsField::Consumers);
            }

            $reach = Required::rows($row, WireField::Reach, CredentialsIsUnreadable::replacement(WireField::Reach));
            $said = self::text($reach, WireField::Reach);
            $how = HowFarAHolderWasReached::tryFrom($said)
                ?? throw CredentialsIsUnreadable::replacementSaid(WireField::Reach, $said, ...array_map(static fn(HowFarAHolderWasReached $case): string => $case->value, HowFarAHolderWasReached::cases()));

            $found[] = AHolderReached::said(
                self::text($row, CredentialsField::Consumer),
                $how,
                $how === HowFarAHolderWasReached::Updated ? '' : self::text($reach, WireField::Detail),
            );
        }

        return $found;
    }

    /**
     * Text with something in it, refused in the words of a replacement.
     *
     * @param array<mixed> $table
     */
    private static function text(array $table, NamesAWireField $field): string
    {
        return Required::text($table, $field, CredentialsIsUnreadable::replacement($field));
    }
}
