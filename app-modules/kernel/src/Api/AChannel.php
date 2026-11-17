<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** One live TV channel a member may watch: what it is called, its number where it has one, and what is on it now where the guide says. */
final readonly class AChannel
{
    private function __construct(private ChannelId $id, private string $name, private string $number, private ?AProgramme $now) {}

    /** A channel with what is on it now. */
    public static function showing(ChannelId $id, string $name, string $number, AProgramme $now): self
    {
        return new self($id, $name, $number, $now);
    }

    /** A channel the guide says nothing of now. */
    public static function withNothingSaid(ChannelId $id, string $name, string $number): self
    {
        return new self($id, $name, $number, null);
    }

    public function id(): ChannelId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** Its number on the tuner, or empty where it has none. */
    public function number(): string
    {
        return $this->number;
    }

    /**
     * Say what happens where the guide says what is on it now, and where it says nothing.
     *
     * @template TShowing of object
     * @template TNothing of object
     *
     * @param Closure(AProgramme): TShowing $showing
     * @param Closure(): TNothing           $nothingSaid
     *
     * @return TShowing|TNothing
     */
    public function now(Closure $showing, Closure $nothingSaid): object
    {
        return $this->now instanceof AProgramme ? $showing($this->now) : $nothingSaid();
    }
}
