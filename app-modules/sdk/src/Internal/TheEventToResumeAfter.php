<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The event a stream is opened after, or none where it opens from the start, carried out of an `either()` arm.
 *
 * {@see \Modules\Kernel\Api\WhereAStreamResumes::either()} answers with an
 * object, and the SDK opens a stream after a string or after nothing.
 */
final readonly class TheEventToResumeAfter
{
    public function __construct(public ?string $event) {}
}
