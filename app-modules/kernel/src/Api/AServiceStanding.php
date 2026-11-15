<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service of a project already here: whether it runs, whether lemonfiber could take it over, and where it keeps its configuration and its library.
 */
final readonly class AServiceStanding
{
    private function __construct(
        private string $service,
        private ThePortsItPublishes $ports,
        private bool $running,
        private bool $adoptable,
        private WhereAServiceKeepsIt $config,
        private WhereAServiceKeepsIt $library,
    ) {}

    /** What the stack found of one service; a blank name is refused. */
    public static function found(
        string $service,
        ThePortsItPublishes $ports,
        bool $running,
        bool $adoptable,
        WhereAServiceKeepsIt $config,
        WhereAServiceKeepsIt $library,
    ): self {
        if (trim($service) === '') {
            throw TheSurveySaysNothing::about('service');
        }

        return new self($service, $ports, $running, $adoptable, $config, $library);
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

    /** Where it reads its configuration from on this machine. */
    public function config(): WhereAServiceKeepsIt
    {
        return $this->config;
    }

    /** Where its library is on this machine. */
    public function library(): WhereAServiceKeepsIt
    {
        return $this->library;
    }
}
