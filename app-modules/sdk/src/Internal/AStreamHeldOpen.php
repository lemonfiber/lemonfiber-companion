<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Iterator;
use Lemonfiber\Sdk\Events\SseParser;

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
}
