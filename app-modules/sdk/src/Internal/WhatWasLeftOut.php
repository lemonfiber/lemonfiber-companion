<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_is_list;
use function array_key_exists;

use Closure;

use function is_array;
use function is_string;

use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Sdk\Api\WireField;
use Throwable;

use function trim;

/**
 * Reads the services a set of forms left out, and a list of form names.
 *
 * Shared by the `status` and `preview` readers, which carry `filtered` in
 * one shape: a service, what it would need, and the forms that asked for it.
 * Each caller hands in its own refusal, so a defect is reported against the
 * envelope it arrived in.
 */
final readonly class WhatWasLeftOut
{
    /**
     * Every service left out, in the stack's order.
     *
     * @param mixed                   $listed  the `filtered` list, as it arrived
     * @param Closure(int): Throwable $refused the refusal for the entry at a position, or the list itself at `-1`
     */
    public static function in(mixed $listed, Closure $refused): TheServicesLeftOut
    {
        if (! is_array($listed) || ! array_is_list($listed)) {
            throw $refused(-1);
        }

        $leftOut = [];

        foreach ($listed as $position => $row) {
            $leftOut[] = self::one(is_array($row) ? $row : [], static fn(): Throwable => $refused($position));
        }

        return TheServicesLeftOut::of(...$leftOut);
    }

    /**
     * A list of form names, in the stack's order.
     *
     * @param mixed               $listed  the list, as it arrived
     * @param Closure(): Throwable $refused the refusal where it is not a list of names
     */
    public static function forms(mixed $listed, Closure $refused): Forms
    {
        if (! is_array($listed) || ! array_is_list($listed)) {
            throw $refused();
        }

        $forms = [];

        foreach ($listed as $named) {
            if (! is_string($named) || trim($named) === '') {
                throw $refused();
            }

            $forms[] = Form::called($named);
        }

        return Forms::these(...$forms);
    }

    /**
     * One service left out.
     *
     * @param array<array-key, mixed> $row
     * @param Closure(): Throwable    $refused
     */
    private static function one(array $row, Closure $refused): AServiceLeftOut
    {
        if (
            ! array_key_exists(WireField::Id->value, $row)
            || ! array_key_exists(WireField::Name->value, $row)
            || ! array_key_exists(WireField::Needs->value, $row)
            || ! array_key_exists(WireField::Forms->value, $row)
        ) {
            throw $refused();
        }

        $id = $row[WireField::Id->value];
        $name = $row[WireField::Name->value];
        $needs = $row[WireField::Needs->value];

        if (! is_string($id) || trim($id) === '' || ! is_string($name) || trim($name) === '' || ! is_string($needs)) {
            throw $refused();
        }

        return AServiceLeftOut::needing(
            ServiceId::called($id),
            $name,
            WhatItWouldNeed::tryFrom($needs) ?? throw $refused(),
            self::forms($row[WireField::Forms->value], $refused),
        );
    }
}
