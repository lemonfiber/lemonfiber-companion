<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service reading a setting or a secret: the service, the variable it reads it into, and the default it declares.
 */
final readonly class AServiceReadingIt
{
    private function __construct(private ServiceId $service, private string $variable, private ASettingValue $default) {}

    /** The service and its variable; a blank variable is refused. */
    public static function of(ServiceId $service, string $variable, ASettingValue $default): self
    {
        if (trim($variable) === '') {
            throw SettingSaysNothing::about('variable');
        }

        return new self($service, trim($variable), $default);
    }

    /** The service. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** The variable it reads it into. */
    public function variable(): string
    {
        return $this->variable;
    }

    /** What its manifest declares while no value is recorded. */
    public function default(): ASettingValue
    {
        return $this->default;
    }
}
