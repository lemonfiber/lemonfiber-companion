<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StackEnvelope;
use Modules\Kernel\Api\APartTheWordsLeaveOut;
use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePartsTheWordsLeaveOut;
use Modules\Kernel\Api\WhoseStackItIs;
use Modules\Sdk\Api\Fields\StackField;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `stack` envelope, as how the stack runs without lemonfiber.
 *
 * The sibling of {@see WhatTheWatcherSaw}, written the same way.
 */
final readonly class WhatTheStackSaidOfItself
{
    /** The word the wire writes for the stack this build ships. */
    private const string BUNDLED = 'bundled';

    /**
     * Whose stack it is, where its project and `.env` are, the words that bring it up, how any action runs without a question, and what those words leave out.
     *
     * @param Envelope<mixed> $envelope the `stack` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): HowItRunsWithoutLemonfiber
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw StackIsUnreadable::missing(WireField::Data);
        }

        $leftOut = [];

        foreach (InTheOrderSent::rows(Required::rows($data, WireField::Exceptions, StackIsUnreadable::missing(WireField::Exceptions))) as $row) {
            $leftOut[] = self::leftOut($row);
        }

        return HowItRunsWithoutLemonfiber::said(
            self::whose($data),
            Required::text($data, WireField::Project, StackIsUnreadable::missing(WireField::Project)),
            Required::text($data, StackField::Env, StackIsUnreadable::missing(StackField::Env)),
            self::plainUp($data),
            Required::text(self::table($data, StackField::NonInteractive), WireField::Form, StackIsUnreadable::missing(WireField::Form)),
            ThePartsTheWordsLeaveOut::these(...$leftOut),
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
        return StackEnvelope::in($envelope)->data;
    }

    /**
     * The bundled stack, written as its word, or the operator's own, written as the directory it lives in.
     *
     * @param array<mixed> $data
     */
    private static function whose(array $data): WhoseStackItIs
    {
        if (array_key_exists(WireField::Source->value, $data) && $data[WireField::Source->value] === self::BUNDLED) {
            return WhoseStackItIs::bundled();
        }

        return WhoseStackItIs::theOperators(Required::text(self::table($data, WireField::Source), StackField::Own, StackIsUnreadable::missing(StackField::Own)));
    }

    /**
     * The words that bring the stack up, refused where there are none or one is not a word.
     *
     * @param array<mixed> $data
     */
    private static function plainUp(array $data): TheCommandLine
    {
        $words = [];

        foreach (InTheOrderSent::rows(Required::rows($data, StackField::PlainUp, StackIsUnreadable::missing(StackField::PlainUp))) as $word) {
            $words[] = Required::isText($word) ? $word : throw StackIsUnreadable::missing(StackField::PlainUp);
        }

        return $words === [] ? throw StackIsUnreadable::missing(StackField::PlainUp) : TheCommandLine::of(...$words);
    }

    /** A part the words leave out, and why. */
    private static function leftOut(mixed $row): APartTheWordsLeaveOut
    {
        $table = is_array($row) ? $row : throw StackIsUnreadable::missing(WireField::Exceptions);

        return APartTheWordsLeaveOut::because(
            Required::text($table, WireField::Part, StackIsUnreadable::missing(WireField::Part)),
            Required::text($table, WireField::Because, StackIsUnreadable::missing(WireField::Because)),
        );
    }

    /**
     * An object a field must hold.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function table(array $data, NamesAWireField $field): array
    {
        return array_key_exists($field->value, $data) && is_array($data[$field->value]) ? $data[$field->value] : throw StackIsUnreadable::missing($field);
    }
}
