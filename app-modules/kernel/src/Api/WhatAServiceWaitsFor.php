<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function trim;

/**
 * One thing a service held back waits for, never with its value: a setting, with its choices where it lists them; a secret; or the service it runs through, held back itself.
 */
final readonly class WhatAServiceWaitsFor
{
    /** @param list<string> $choices */
    private function __construct(private KindOfThingWaitedFor $is, private string $named, private array $choices) {}

    /** A setting only the operator holds, or one holding a value outside its choices, with the values it accepts where it lists any; a blank name is refused. */
    public static function aSetting(string $setting, string ...$choices): self
    {
        return new self(KindOfThingWaitedFor::Setting, self::named($setting), array_values($choices));
    }

    /** A secret nobody has entered, by its name in the secret store; a blank name is refused. */
    public static function aSecret(string $secret): self
    {
        return new self(KindOfThingWaitedFor::Secret, self::named($secret), []);
    }

    /** The service it runs through, held back itself. */
    public static function aService(ServiceId $service): self
    {
        return new self(KindOfThingWaitedFor::Service, $service->named(), []);
    }

    /**
     * Say what happens for each of the three, and get back what you built.
     *
     * @template TSetting of object
     * @template TSecret of object
     * @template TService of object
     *
     * @param  Closure(string, list<string>): TSetting $setting
     * @param  Closure(string): TSecret                $secret
     * @param  Closure(ServiceId): TService            $service
     * @return TSetting|TSecret|TService
     */
    public function either(Closure $setting, Closure $secret, Closure $service): object
    {
        return match ($this->is) {
            KindOfThingWaitedFor::Setting => $setting($this->named, $this->choices),
            KindOfThingWaitedFor::Secret => $secret($this->named),
            KindOfThingWaitedFor::Service => $service(ServiceId::called($this->named)),
        };
    }

    /** Whether it is this setting. */
    public function isTheSetting(ASetting $setting): bool
    {
        return $this->is === KindOfThingWaitedFor::Setting && $this->named === $setting->name();
    }

    private static function named(string $named): string
    {
        if (trim($named) === '') {
            throw SettingSaysNothing::about('waits for');
        }

        return trim($named);
    }
}
