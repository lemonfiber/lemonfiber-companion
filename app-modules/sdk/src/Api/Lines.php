<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\LogWindow;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stream;
use Modules\Sdk\Api\Fields\LogField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * A log window, as the lines this app can show.
 *
 * The sibling of {@see Stoppages} for the log window's payload, and written the same
 * way: a static fold with no state, reading through {@see WireField} so no
 * field name is spelled twice, and refusing rather than salvaging.
 *
 * **It is handed the SDK's own window rather than a list of envelopes.** That
 * window already pairs what was asked for with what arrived, which is the only
 * honest thing that can be said about the edge of a view — and rebuilding the
 * pairing here from a count would be a second copy of the one decision a window
 * turns on.
 *
 * **A line missing a field is refused, not skipped.** A log read is what an
 * operator turns to when the rest of the app has not explained something, and a
 * window quietly short of the line that would have explained it is worse than
 * no window at all: they conclude the service never said it.
 */
final readonly class Lines
{
    /**
     * Every line of a window, in the order the service wrote them.
     *
     * The order is the stack's — oldest first, as the SDK's window hands them
     * over — and is preserved untouched, for {@see Reports}' reason: which
     * order a person should read them in is a screen's decision, made where
     * there is a screen to make it. Here it is also the only order that means
     * anything, because a log line is read against the line before it.
     */
    public static function in(LogWindow $window): Scrollback
    {
        $service = ServiceId::called($window->service());
        $asked = HowManyLines::of($window->bound());

        $said = [];
        $position = 0;

        foreach ($window->lines() as $envelope) {
            // Every line, not the window. A wire version this build does not
            // app does not support, and a window is many envelopes rather than
            // one — the client asserts each line's *kind* as it builds the
            // window and says nothing about its version, so this is the only
            // place the gate can stand for a log read.
            $said[] = self::line(Wire::checked($envelope)->data, $position);
            $position++;
        }

        return Scrollback::of($service, $asked, ...$said);
    }

    /**
     * One line, with the three fields the contract promises and the optional one.
     */
    private static function line(mixed $row, int $position): Said
    {
        if (! is_array($row)) {
            throw LineIsUnreadable::said(LogField::Line, $position);
        }

        $line = self::text($row, LogField::Line, $position, blankIsALine: true);
        $service = ServiceId::called(self::text($row, WireField::Service, $position));
        $stream = self::stream($row, $position);

        if (! array_key_exists(WireField::At->value, $row) || $row[WireField::At->value] === null) {
            return Said::whenever($line, $service, $stream);
        }

        return Said::at(self::moment($row, $position), $line, $service, $stream);
    }

    /**
     * A named field of one line, as text.
     *
     * `$blankIsALine` is the whole reason this takes a flag rather than being
     * two methods: a blank *log line* is a line — services print them to
     * separate one stanza from the next, and dropping them would join two
     * unrelated passages into one paragraph, which reads as something the
     * service said. A blank *service name* is a fault. Same shape, opposite
     * answers, and spelling the difference at the call site is what keeps
     * somebody from tightening one and breaking the other.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $field, int $position, bool $blankIsALine = false): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $row)) {
            throw LineIsUnreadable::said($field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw LineIsUnreadable::said($field, $position);
        }

        if (! $blankIsALine && trim($said) === '') {
            throw LineIsUnreadable::said($field, $position);
        }

        return $said;
    }

    /**
     * Which mouth one line came out of, as a case rather than as the word.
     *
     * A stream this app does not recognise is refused rather than passed
     * through, which is the contract's own distinction applied to a closed set: a word
     * this build has not heard of means the contract moved, and rendering it raw
     * would put a field value on somebody's screen.
     *
     * @param array<mixed> $row
     */
    private static function stream(array $row, int $position): Stream
    {
        $said = self::text($row, LogField::Stream, $position);

        return Stream::tryFrom($said) ?? throw LineIsUnreadable::stream($said, $position);
    }

    /**
     * When one line happened, where the service said so.
     *
     * Stated-and-blank is refused rather than treated as unstated, because the
     * two are different facts about the service: one wrote a timestamp it could
     * not fill in, and the other does not write them. Folding them would hide a
     * fault in whatever produced the line.
     *
     * @param array<mixed> $row
     */
    private static function moment(array $row, int $position): string
    {
        $said = $row[WireField::At->value];

        if (! is_string($said) || trim($said) === '') {
            throw LineIsUnreadable::moment(is_string($said) ? $said : '', $position);
        }

        return $said;
    }
}
