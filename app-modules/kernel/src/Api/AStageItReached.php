<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One stage a traced item reached: the stage, the service that recorded it, and when.
 *
 * When is the service's own wording, and empty for a stage inferred rather
 * than timed, such as being monitored.
 */
final readonly class AStageItReached
{
    private function __construct(private Stage $stage, private ServiceId $service, private string $at) {}

    /** A stage a service recorded, with when it said; a blank when is refused, an empty one is untimed. */
    public static function recorded(Stage $stage, ServiceId $service, string $at): self
    {
        if ($at !== '' && trim($at) === '') {
            throw TheTraceSaysNothing::about('at');
        }

        return new self($stage, $service, $at);
    }

    public function stage(): Stage
    {
        return $this->stage;
    }

    public function service(): ServiceId
    {
        return $this->service;
    }

    /** When the service said it happened, or empty where it was not timed. */
    public function at(): string
    {
        return $this->at;
    }
}
