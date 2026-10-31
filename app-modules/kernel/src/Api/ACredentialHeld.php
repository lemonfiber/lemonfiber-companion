<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One credential a stack holds: what it is, where it stands, who made it and what uses it.
 *
 * **There is no value here, and nowhere to put one.** The stack describes a
 * credential without disclosing it, and this type has no field a value could
 * be carried in.
 *
 * `advisory` is empty where the stack had nothing to say. The media server's
 * internal certificate also carries the SHA-256 of the one in force, which is
 * public; no other credential carries a likeness of anything.
 */
final readonly class ACredentialHeld
{
    private function __construct(
        private string $name,
        private string $setting,
        private WhereACredentialStands $state,
        private WhoMadeACredential $origin,
        private WhatUsesIt $consumers,
        private string $advisory,
        private TheCertificateInForce $inForce,
    ) {}

    /** What the stack said of one credential; a blank name, setting or advisory is refused. */
    public static function described(
        string $name,
        string $setting,
        WhereACredentialStands $state,
        WhoMadeACredential $origin,
        WhatUsesIt $consumers,
        string $advisory,
    ): self {
        if (trim($name) === '') {
            throw CredentialSaysNothing::about('name');
        }

        if (trim($setting) === '') {
            throw CredentialSaysNothing::about('setting');
        }

        if ($advisory !== '' && trim($advisory) === '') {
            throw CredentialSaysNothing::about('advisory');
        }

        return new self($name, $setting, $state, $origin, $consumers, $advisory, TheCertificateInForce::unsaid());
    }

    /** The same credential, as the media server's internal certificate with this one in force; any other credential is refused. */
    public function inForce(AnInternalCertificate $certificate): self
    {
        if (! $this->isTheInternalCertificate()) {
            throw NotTheInternalCertificate::named($this->setting);
        }

        return new self($this->name, $this->setting, $this->state, $this->origin, $this->consumers, $this->advisory, TheCertificateInForce::of($certificate));
    }

    /** Whether this is the media server's internal certificate. */
    public function isTheInternalCertificate(): bool
    {
        return $this->setting === AnInternalCertificate::SETTING;
    }

    /** The internal certificate in force, where this is that certificate and the stack said which; unsaid otherwise. */
    public function certificateInForce(): TheCertificateInForce
    {
        return $this->inForce;
    }

    /** What it is, in the operator's words. */
    public function name(): string
    {
        return $this->name;
    }

    /** The setting it is held under, a name and never a value, which a grant of it names. */
    public function setting(): string
    {
        return $this->setting;
    }

    /** Where it stands. */
    public function state(): WhereACredentialStands
    {
        return $this->state;
    }

    /** Who produced it. */
    public function origin(): WhoMadeACredential
    {
        return $this->origin;
    }

    /** Everything that authenticates with it, possibly nothing. */
    public function consumers(): WhatUsesIt
    {
        return $this->consumers;
    }

    /** What is worth saying about it, or empty. */
    public function advisory(): string
    {
        return $this->advisory;
    }
}
