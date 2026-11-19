<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What setup settled on a stack, read from its configuration as it stands, and how far setup got.
 */
final readonly class WhatSetupSettled
{
    private function __construct(
        private HowFarSetupGot $setup,
        private TheDataRoot $dataRoot,
        private TheWaysOfDownloading $downloading,
        private TheServiceUser $serviceUser,
    ) {}

    /** How far setup got. */
    public function setup(): HowFarSetupGot
    {
        return $this->setup;
    }

    /** What the stack said: how far setup got, where the library is kept, how it downloads and who its services run as. */
    public static function said(HowFarSetupGot $setup, TheDataRoot $dataRoot, TheWaysOfDownloading $downloading, TheServiceUser $serviceUser): self
    {
        return new self($setup, $dataRoot, $downloading, $serviceUser);
    }

    /** Where the library is kept. */
    public function dataRoot(): TheDataRoot
    {
        return $this->dataRoot;
    }

    /** Which ways of downloading it is set up for. */
    public function downloading(): TheWaysOfDownloading
    {
        return $this->downloading;
    }

    /** Who its services run as. */
    public function serviceUser(): TheServiceUser
    {
        return $this->serviceUser;
    }
}
