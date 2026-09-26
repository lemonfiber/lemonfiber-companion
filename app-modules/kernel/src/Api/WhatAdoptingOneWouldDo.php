<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What adopting one existing service would come to: whether it wants a copy first, and whether it is refused.
 */
final readonly class WhatAdoptingOneWouldDo
{
    private function __construct(
        private string $service,
        private string $because,
        private bool $backupFirst,
        private bool $refused,
    ) {}

    /** What the stack said of adopting one service; the service and what it means for its data are required. */
    public static function said(string $service, string $because, bool $backupFirst, bool $refused): self
    {
        foreach (['service' => $service, 'because' => $because] as $field => $said) {
            if (trim($said) === '') {
                throw TheSurveySaysNothing::about($field);
            }
        }

        return new self($service, $because, $backupFirst, $refused);
    }

    /** The service, by the name lemonfiber runs it under. */
    public function service(): string
    {
        return $this->service;
    }

    /** What adopting it means for its data, in the stack's words. */
    public function because(): string
    {
        return $this->because;
    }

    /** Whether its database must be copied before lemonfiber opens it. */
    public function wantsACopyFirst(): bool
    {
        return $this->backupFirst;
    }

    /** Whether lemonfiber will not adopt it at all. */
    public function isRefused(): bool
    {
        return $this->refused;
    }
}
