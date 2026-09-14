<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function mb_stripos;

/**
 * One line a service wrote, and the three things that place it.
 *
 * `N2-R10` asks for a log read that names the service, and a line carries its
 * own — the contract sends `service` per line rather than once per window,
 * because the same read can be asked about more than one and the app must not
 * be the thing that decides which line belongs to which.
 *
 * **The moment is optional and stays that way.** `log.at` may be absent, which
 * is ordinary: plenty of services write lines with no timestamp of their own,
 * and a window of two hundred is mostly readable without one. {@see when()} is
 * two arms rather than a nullable getter, for `C2`'s reason — a screen handed a
 * null would print an empty column and an operator would read it as *this
 * happened at no time*.
 *
 * **It is carried as the service stated it, not as an {@see Instant}.** A
 * moment turned into a count of seconds and rendered back in the phone's
 * timezone would have two operators in different places disagree about when
 * something happened on the same machine — and the one thing a log line's
 * timestamp is for is matching it against the other lines around it, which are
 * all in the service's own frame. So it is machine data on the way through,
 * like the line and the service name beside it.
 *
 * **A blank line is a line.** Services print them to separate one thing from
 * the next, and a reader that dropped them would join two unrelated stanzas
 * into one paragraph — which is worse than the blank, because it reads as
 * something the service said.
 */
final readonly class Said
{
    private function __construct(
        private string $line,
        private ServiceId $service,
        private Stream $stream,
        private ?string $at = null,
    ) {}

    /**
     * A line whose moment the service recorded, in the service's own words.
     *
     * A stated-but-blank moment is refused where the wire is read rather than
     * here, because only there is the line's position known — and *line 41 says
     * it happened at nothing* can be acted on where *a line says so* cannot.
     */
    public static function at(string $when, string $line, ServiceId $service, Stream $stream): self
    {
        return new self($line, $service, $stream, $when);
    }

    /** A line with no moment of its own, which is ordinary rather than a fault. */
    public static function whenever(string $line, ServiceId $service, Stream $stream): self
    {
        return new self($line, $service, $stream);
    }

    /** What the service wrote, exactly as it wrote it. */
    public function line(): string
    {
        return $this->line;
    }

    /** Which of the two mouths it came out of. */
    public function stream(): Stream
    {
        return $this->stream;
    }

    /** Which service wrote it. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /**
     * Say when, or say that the service did not.
     *
     * @template TThen of object
     * @template TUnstated of object
     *
     * @param  Closure(string): TThen $then
     * @param  Closure(): TUnstated   $unstated
     * @return TThen|TUnstated
     */
    public function when(Closure $then, Closure $unstated): object
    {
        return $this->at === null ? $unstated() : $then($this->at);
    }

    /**
     * Whether this line holds what somebody is looking for.
     *
     * Case-insensitive and on the text only. Both halves are decisions worth
     * stating: somebody scanning for `timeout` should not have to know whether
     * the service shouted it, and the service name is the same on every line of
     * a window so matching it would select all of them or none.
     *
     * `mb_stripos` rather than `str_contains` with a case fold, because a log
     * line is whatever bytes a service wrote and folding case by byte turns a
     * multi-byte character into something that matches nothing.
     */
    public function holds(LookingFor $looking): bool
    {
        return mb_stripos($this->line, $looking->typed()) !== false;
    }
}
