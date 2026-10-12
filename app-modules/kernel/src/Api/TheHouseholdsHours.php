<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The hours the household is up and the stack is held back, as the stack wrote them, with the zone they are read in where it said.
 */
final readonly class TheHouseholdsHours
{
    private function __construct(private string $from, private string $to, private string $zone) {}

    /** The day's start and end, refused where either is blank; the zone empty where the stack gave none. */
    public static function from(string $from, string $to, string $zone): self
    {
        foreach (['rhythm.from' => $from, 'rhythm.to' => $to] as $field => $said) {
            if (trim($said) === '') {
                throw LineSaysNothing::about($field);
            }
        }

        return new self(trim($from), trim($to), trim($zone));
    }

    /** When the household's day starts. */
    public function starts(): string
    {
        return $this->from;
    }

    /** When it ends, which may be the next morning. */
    public function ends(): string
    {
        return $this->to;
    }

    /** The zone the clients read those hours in, empty where the stack did not say. */
    public function zone(): string
    {
        return $this->zone;
    }
}
