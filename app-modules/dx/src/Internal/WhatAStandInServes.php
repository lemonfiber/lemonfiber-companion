<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function is_array;

use Modules\Kernel\Api\Availability;
use Modules\Sdk\Api\EveryRequestThisAppSends;
use Modules\Sdk\Api\Fields\CapabilitiesField;

/**
 * Everything a stand-in stack says it serves: every request this app sends.
 *
 * A stand-in answers every path from the contract, so it serves everything,
 * and its declaration says so; otherwise every screen would read it as a
 * stack too old for what it shows. Read off the registry every request is
 * declared in, so a request added tomorrow is one the stand-in serves.
 */
final readonly class WhatAStandInServes
{
    /** What a stack says it serves in. */
    public const string DECLARED_IN = 'CapabilitiesEnvelope';

    /**
     * The whole `capabilities` payload: the declaration's own fields, with every path available.
     *
     * @return array<array-key, mixed>
     */
    public static function declaration(): array
    {
        $said = WhatAStackWouldSay::inside(self::DECLARED_IN);

        return [...is_array($said) ? $said : [], ...self::everything()];
    }

    /**
     * The `capabilities` payload, every path available.
     *
     * @return array<string, array<string, string>>
     */
    public static function everything(): array
    {
        $served = [];

        foreach (EveryRequestThisAppSends::listed() as $path) {
            $served[$path->named()] = Availability::Available->value;
        }

        return [CapabilitiesField::Capabilities->value => $served];
    }
}
