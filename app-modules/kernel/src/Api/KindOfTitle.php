<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether a title a member found is a film or a series, as the core's search names it.
 *
 * The wire's words, held to the contract by `EveryWireValueIsACaseTest`. A
 * search names a title by its id and this kind together, and asking for it or
 * reading its poster names both again.
 */
enum KindOfTitle: string
{
    /** A film. */
    case Film = 'movies';

    /** A series. */
    case Series = 'tv';
}
