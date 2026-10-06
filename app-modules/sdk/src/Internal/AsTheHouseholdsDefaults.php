<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The query that asks a household read to answer as the household's defaults.
 *
 * One place for the parameter, because two reads send it: the shelf and the
 * household. Answered as the defaults, either is read for nobody, so it names
 * no member and carries nothing of anybody's.
 */
final readonly class AsTheHouseholdsDefaults
{
    /** @var array<string, string> what the read is asked with */
    public const array QUERY = ['defaults' => 'true'];
}
