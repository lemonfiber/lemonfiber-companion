<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** A key for an app the operator asked to mint: its name, what it admits and what it is for. */
final readonly class AKeyToMint
{
    private function __construct(private AKeysName $name, private WhatAKeyAdmits $admits, private WhatAKeyIsFor $for) {}

    public static function of(AKeysName $name, WhatAKeyAdmits $admits, WhatAKeyIsFor $for): self
    {
        return new self($name, $admits, $for);
    }

    /** What it is to be called. */
    public function name(): AKeysName
    {
        return $this->name;
    }

    /** What it is to admit. */
    public function admits(): WhatAKeyAdmits
    {
        return $this->admits;
    }

    /** What it is for. */
    public function for(): WhatAKeyIsFor
    {
        return $this->for;
    }
}
