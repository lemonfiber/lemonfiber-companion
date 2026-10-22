<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** A move in place: the stack's own service, and the first-party plugin that would take its place. */
final readonly class AMoveInPlace
{
    private function __construct(private ServiceId $service, private APluginId $plugin) {}

    /** The stack's own service, and the plugin that would take its place. */
    public static function of(ServiceId $service, APluginId $plugin): self
    {
        return new self($service, $plugin);
    }

    /** The stack's own service. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** The plugin that would take its place. */
    public function plugin(): APluginId
    {
        return $this->plugin;
    }
}
