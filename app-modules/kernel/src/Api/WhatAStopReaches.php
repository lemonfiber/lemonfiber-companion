<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What an action would stop, as the stack is asked what is still coming down inside it.
 */
final readonly class WhatAStopReaches
{
    private function __construct(private Forms $forms, private Services $services) {}

    /** A whole form. */
    public static function theForm(Form $form): self
    {
        return new self(Forms::these($form), Services::none());
    }

    /** These services. */
    public static function theServices(ServiceId ...$services): self
    {
        return new self(Forms::none(), Services::these(...$services));
    }

    /** The forms it names. */
    public function forms(): Forms
    {
        return $this->forms;
    }

    /** The services it names. */
    public function services(): Services
    {
        return $this->services;
    }
}
