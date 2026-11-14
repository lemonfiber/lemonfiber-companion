<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One stage a traced item reached: the stage, the service that recorded it, and when.
 *
 * When is the service's own wording, and empty for a stage inferred rather
 * than timed, such as being monitored.
 */
final readonly class AStageItReached
{
    private function __construct(private Stage $stage, private ServiceId $service, private AMomentAsWritten $at) {}

    /** A stage a service recorded, with when it said, unwritten where it was not timed. */
    public static function recorded(Stage $stage, ServiceId $service, AMomentAsWritten $at): self
    {
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

    /** When the service said it happened, unwritten where it was not timed. */
    public function at(): AMomentAsWritten
    {
        return $this->at;
    }
}
