<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service of a plugin taking a privileged shape, as an install's reading states it.
 *
 * Approved apart from the offer, as the stack spells its approval: agreeing to
 * the install approves none of them.
 */
final readonly class AShapeTaken
{
    private function __construct(
        private string $service,
        private APrivilegedShape $shape,
        private PluginLines $grants,
        private PluginLines $devices,
        private string $approval,
        private bool $joinsTheDevicesGroup = false,
    ) {}

    /** The service taking it, the shape, what it is granted and given, and what approving it is written as; a blank service or approval is refused. */
    public static function by(string $service, APrivilegedShape $shape, PluginLines $grants, PluginLines $devices, string $approval): self
    {
        foreach (['service' => $service, 'approval' => $approval] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($service, $shape, $grants, $devices, $approval);
    }

    /** The same shape, whose service is added to the group owning its devices on the engine's host. */
    public function joiningTheDevicesGroup(): self
    {
        return new self($this->service, $this->shape, $this->grants, $this->devices, $this->approval, joinsTheDevicesGroup: true);
    }

    /** Whether its service is added to the group owning its devices on the engine's host. */
    public function joinsTheDevicesGroup(): bool
    {
        return $this->joinsTheDevicesGroup;
    }

    /** The service taking it. */
    public function service(): string
    {
        return $this->service;
    }

    /** The shape it takes. */
    public function shape(): APrivilegedShape
    {
        return $this->shape;
    }

    /** The kernel capabilities it is granted. */
    public function grants(): PluginLines
    {
        return $this->grants;
    }

    /** The devices it is given. */
    public function devices(): PluginLines
    {
        return $this->devices;
    }

    /** What approving it is written as. */
    public function approval(): string
    {
        return $this->approval;
    }
}
