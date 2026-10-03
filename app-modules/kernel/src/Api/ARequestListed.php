<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One request somebody in the household made, by its number. */
final readonly class ARequestListed
{
    public function __construct(private RequestId $id, private string $title, private string $by) {}

    /** The number the request service files it under. */
    public function id(): RequestId
    {
        return $this->id;
    }

    /** What it is called, or empty where no service has been told about it yet. */
    public function title(): string
    {
        return $this->title;
    }

    /** Who asked for it, by the name the media server holds them under. */
    public function by(): string
    {
        return $this->by;
    }
}
