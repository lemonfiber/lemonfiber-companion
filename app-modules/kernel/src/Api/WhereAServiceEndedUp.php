<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where one service stood once a verb that waited for it had finished.
 *
 * The name and the state and nothing more: that is what naming a service
 * that did not come back takes, and the service's own frame is where the rest
 * of it is read, off a listing taken now rather than a report of a moment ago.
 */
final readonly class WhereAServiceEndedUp
{
    private function __construct(private string $name, private HowAServiceRuns $runs) {}

    /** A service as the report names it; a blank name is refused. */
    public static function as(string $name, HowAServiceRuns $runs): self
    {
        $shown = trim($name);

        if ($shown === '') {
            throw ServiceIsUnnamed::whereOneWasExpected();
        }

        return new self($shown, $runs);
    }

    /** What an operator reads. */
    public function name(): string
    {
        return $this->name;
    }

    /** Where it stood when the stack stopped waiting. */
    public function runs(): HowAServiceRuns
    {
        return $this->runs;
    }
}
