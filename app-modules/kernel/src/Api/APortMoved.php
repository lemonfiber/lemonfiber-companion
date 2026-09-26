<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where one lemonfiber service would listen to run beside what is already here.
 */
final readonly class APortMoved
{
    private function __construct(
        private string $service,
        private int $from,
        private int $to,
    ) {}

    /** What the stack said of one service moved aside; a blank service is refused. */
    public static function of(string $service, int $from, int $to): self
    {
        if (trim($service) === '') {
            throw TheSurveySaysNothing::about('service');
        }

        return new self($service, $from, $to);
    }

    /** The lemonfiber service being moved. */
    public function service(): string
    {
        return $this->service;
    }

    /** The port it would ordinarily take. */
    public function from(): int
    {
        return $this->from;
    }

    /** The port it would take instead, which is where it is reached. */
    public function to(): int
    {
        return $this->to;
    }
}
