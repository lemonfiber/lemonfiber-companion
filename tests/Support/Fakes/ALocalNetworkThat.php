<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\TheLocalNetwork;

/**
 * A platform that lets this app onto the local network, or refuses it, the same way every time.
 *
 * Not `readonly`: every address asked about is written down when it is asked.
 */
final class ALocalNetworkThat implements TheLocalNetwork
{
    /** @var list<string> */
    private array $asked = [];

    private function __construct(private readonly bool $refuses) {}

    /** A platform that lets this app onto the local network, which is what Android always is. */
    public static function letsItThrough(): self
    {
        return new self(refuses: false);
    }

    /** A platform on which the operator declined the local network. */
    public static function refusesIt(): self
    {
        return new self(refuses: true);
    }

    public function refusesTheWayTo(Address $at): bool
    {
        $this->asked[] = $at->forTheClient();

        return $this->refuses;
    }

    /** @return list<string> every address asked about, in order */
    public function asked(): array
    {
        return $this->asked;
    }
}
