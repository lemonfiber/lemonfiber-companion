<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use function array_key_exists;
use function is_string;

use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\EventHasNoName;
use Modules\Kernel\Api\WhereAStreamResumes;

/**
 * Where a stack's stream left off, as the platform's store holds it.
 *
 * A value this build did not write reads as from the start, which hands back
 * no past alert.
 */
final readonly class TheEventAsWritten
{
    /** The field of a stored value that holds the event. */
    private const string AFTER = 'after';

    /**
     * The fields a value holding this event is written with.
     *
     * @return array<string, string>
     */
    public static function written(AnEventHeard $event): array
    {
        return [self::AFTER => $event->shown()];
    }

    /** Where a stream resumes, from a decoded value written in this shape. */
    public static function read(mixed $found, int $shape): WhereAStreamResumes
    {
        if (! KeptInAShape::isIn($found, $shape) || ! array_key_exists(self::AFTER, $found) || ! is_string($found[self::AFTER])) {
            return WhereAStreamResumes::fromTheStart();
        }

        try {
            return WhereAStreamResumes::after(AnEventHeard::named($found[self::AFTER]));
        } catch (EventHasNoName) {
            return WhereAStreamResumes::fromTheStart();
        }
    }
}
