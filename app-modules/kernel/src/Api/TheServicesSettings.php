<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;

/**
 * Every setting and secret a stack's services read.
 *
 * None at all is an answer, apart from *could not be read*, which is an
 * {@see Obstacle} that {@see WhatWasFoundOfTheSettings} keeps apart.
 */
final readonly class TheServicesSettings
{
    private function __construct(private TheSettingsRead $settings, private TheSecretsRead $secrets) {}

    /** These settings and secrets. */
    public static function of(TheSettingsRead $settings, TheSecretsRead $secrets): self
    {
        return new self($settings, $secrets);
    }

    /** Only the settings and secrets one of these services reads. */
    public function readByAnyOf(Services $services): self
    {
        return new self($this->settings->readByAnyOf($services), $this->secrets->readByAnyOf($services));
    }

    /** Only the settings and secrets none of these services reads. */
    public function readByNoneOf(Services $services): self
    {
        return new self($this->settings->readByNoneOf($services), $this->secrets->readByNoneOf($services));
    }

    /** Only the settings and secrets this owner holds. */
    public function ownedBy(WhoseASettingIs $whose): self
    {
        return new self($this->settings->ownedBy($whose), $this->secrets->ownedBy($whose));
    }

    /**
     * Whose they are: the core first where it holds any, then each namespace in the order its first setting or secret is listed.
     */
    public function owners(): WhoseTheSettingsAre
    {
        $owners = [];

        foreach ([...$this->settings, ...$this->secrets] as $one) {
            $whose = $one->whose();
            $met = array_any($owners, static fn(WhoseASettingIs $owner): bool => $owner->is($whose));
            $owners = match (true) {
                $met => $owners,
                $whose->is(WhoseASettingIs::theCores()) => [$whose, ...$owners],
                default => [...$owners, $whose],
            };
        }

        return WhoseTheSettingsAre::these(...$owners);
    }

    /** Every setting, in name order. */
    public function settings(): TheSettingsRead
    {
        return $this->settings;
    }

    /** Every secret, in name order, never with its value. */
    public function secrets(): TheSecretsRead
    {
        return $this->secrets;
    }
}
