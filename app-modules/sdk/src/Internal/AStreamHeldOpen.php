<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Iterator;
use Lemonfiber\Sdk\Events\SseParser;

use function sprintf;

/**
 * One open connection to a stack's event stream, and what has been read of it so far.
 *
 * The two travel together because they are one thing: the parser holds the
 * half of an event that arrived before the rest of it, and a parser handed a
 * different connection's bytes would join two streams into one event.
 */
final readonly class AStreamHeldOpen
{
    /** @param Iterator<int, string> $chunks */
    public function __construct(
        public Iterator $chunks,
        public SseParser $parser,
    ) {}

    /**
     * The identifier of the last event this stream carried, which a stream
     * opened in its place resumes after; none where no event carried one.
     */
    public function leftOffAt(): ?string
    {
        return $this->parser->lastEventId();
    }

    /**
     * Everything that has arrived, up to the first read that found nothing.
     *
     * A chunk is taken and the reader moved on before the chunk is looked at,
     * so the read that comes back empty is the last one this call waits on and
     * whatever the move found is the first thing the next call takes. A stream
     * that runs out before any read comes back empty has ended.
     */
    public function taken(): WhatArrivedOnTheStream
    {
        $arrived = '';

        while ($this->chunks->valid()) {
            $chunk = $this->chunks->current();
            $this->chunks->next();

            if ($chunk === '') {
                return new WhatArrivedOnTheStream($this->parser->feed($arrived), $arrived !== '', ended: false);
            }

            $arrived = sprintf('%s%s', $arrived, $chunk);
        }

        return new WhatArrivedOnTheStream($this->parser->feed($arrived), $arrived !== '', ended: true);
    }
}
