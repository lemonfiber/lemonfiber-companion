<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_string;

use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\WhatWasNamed;
use Modules\Sdk\Api\MoveIsUnreadable;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * The shapes the `adoption`, `import`, `beside` and `replacement` envelopes carry alike, read once.
 *
 * Each of the four stands somewhere, a blocked one says why, and three name a
 * project or a list of names. Each is read here, so the four cannot come to
 * read one shape four ways.
 *
 * `Internal` because it takes the payload as it arrived, which is a table no
 * other module is handed.
 */
final readonly class WhatAMoveCarries
{
    /**
     * Where the move stands, with what it came to; a blocked one with the stack's reason.
     *
     * **Read off the stance rather than off the presence of a refusal**, for
     * {@see \Modules\Sdk\Api\Dials::reviewIn()}'s reason: a sentence beside
     * *applied* would be a reason a move did not happen, drawn under the word
     * saying it did.
     *
     * @param array<mixed> $data
     */
    public static function came(array $data, string $kind, TheAdoption|TheImport|TheStandingBeside|TheReplacement $came): AMove
    {
        $said = Required::text($data, WireField::Stance, MoveIsUnreadable::missing($kind, WireField::Stance));
        $stance = Stance::tryFrom($said) ?? throw MoveIsUnreadable::missing($kind, WireField::Stance);

        return $stance === Stance::Blocked
            ? AMove::blocked(Required::text($data, WireField::Refusal, MoveIsUnreadable::missing($kind, WireField::Refusal)), $came)
            : AMove::at($stance, $came);
    }

    /**
     * Text the stack may leave out, as `''` where it did.
     *
     * Absent and null are the stack not saying, which the contract allows. A
     * value that is there and is not text with something in it is refused
     * rather than read as either.
     *
     * @param array<mixed> $data
     */
    public static function optional(array $data, NamesAWireField $field, string $kind): string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return '';
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw MoveIsUnreadable::missing($kind, $field);
        }

        return $said;
    }

    /**
     * A list of names — services, or paths — each refused if it is not one.
     *
     * @param array<mixed> $data
     */
    public static function named(array $data, NamesAWireField $list, string $kind): WhatWasNamed
    {
        $names = [];
        $position = 0;

        foreach (Required::rows($data, $list, MoveIsUnreadable::missing($kind, $list)) as $name) {
            if (! is_string($name) || trim($name) === '') {
                throw MoveIsUnreadable::entry($kind, $list, $position, $list);
            }

            $names[] = $name;
            $position++;
        }

        return WhatWasNamed::of($list->value, ...$names);
    }
}
