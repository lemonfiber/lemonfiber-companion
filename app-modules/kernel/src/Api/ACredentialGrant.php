<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A credential of another service the operator granted a third-party plugin, named by the setting it is held under and never by its value.
 *
 * No value is carried, and none could be: the stack names a grant by its
 * setting, the service whose credential it is and the capability of the
 * plugin's own it fills.
 */
final readonly class ACredentialGrant
{
    private function __construct(
        private APluginId $plugin,
        private string $credential,
        private ServiceId $of,
        private string $fills,
        private AMomentAsWritten $approved,
    ) {}

    /** A grant to that plugin of the credential held under that setting, for that capability; a blank setting or capability is refused. */
    public static function of(APluginId $plugin, string $credential, ServiceId $of, string $fills, AMomentAsWritten $approved): self
    {
        foreach (['credential' => $credential, 'for' => $fills] as $field => $said) {
            if (trim($said) === '') {
                throw CredentialGrantSaysNothing::about($field);
            }
        }

        return new self($plugin, $credential, $of, $fills, $approved);
    }

    /** The plugin it is granted to. */
    public function plugin(): APluginId
    {
        return $this->plugin;
    }

    /** The setting the credential is held under. */
    public function credential(): string
    {
        return $this->credential;
    }

    /** The service whose credential it is. */
    public function whose(): ServiceId
    {
        return $this->of;
    }

    /** The capability of the plugin's own it fills. */
    public function fills(): string
    {
        return $this->fills;
    }

    /** When the operator approved it, as the stack wrote it, which may name no moment. */
    public function approved(): AMomentAsWritten
    {
        return $this->approved;
    }

    /** Whether this is the same grant: the same credential to the same plugin. */
    public function isTheSameAs(self $other): bool
    {
        return $this->plugin->named() === $other->plugin->named() && $this->credential === $other->credential;
    }
}
