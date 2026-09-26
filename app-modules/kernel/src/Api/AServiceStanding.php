<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service of a project already here: whether it runs, and whether lemonfiber could take it over.
 */
final readonly class AServiceStanding
{
    private function __construct(
        private string $service,
        private ThePortsItPublishes $ports,
        private bool $running,
        private bool $adoptable,
    ) {}

    /** What the stack found of one service; a blank name is refused. */
    public static function found(string $service, ThePortsItPublishes $ports, bool $running, bool $adoptable): self
    {
        if (trim($service) === '') {
            throw TheSurveySaysNothing::about('service');
        }

        return new self($service, $ports, $running, $adoptable);
    }

    /** The name it answers to in its project. */
    public function service(): string
    {
        return $this->service;
    }

    /** Every host port it publishes, lowest first. */
    public function ports(): ThePortsItPublishes
    {
        return $this->ports;
    }

    /** Whether it is running now, as against present and stopped. */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /** Whether lemonfiber knows it and could take it over as it stands. */
    public function isAdoptable(): bool
    {
        return $this->adoptable;
    }
}
