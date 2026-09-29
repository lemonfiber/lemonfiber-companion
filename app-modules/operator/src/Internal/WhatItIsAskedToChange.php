<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where the screens are that change what one machine keeps, on the operator's yes.
 *
 * Taking a copy and putting one back, putting back a run the record shows,
 * letting a download go, putting the configuration back to lemonfiber's own,
 * and taking lemonfiber off. Each asks before it changes anything.
 * Apart from {@see WhatItKeepsOfItself} because that is where the questions
 * change subject, from what the machine keeps to changing it, and because
 * that class had reached the twenty-method ceiling `H3` refuses: one accessor
 * there hands out this, and the next screen of this kind costs it nothing.
 *
 * `Internal`, for {@see WhereAStackIs}' reason.
 */
final readonly class WhatItIsAskedToChange
{
    private function __construct(private StackId $stack) {}

    /** The screens of this kind for one stack this device holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack);
    }

    /** Taking a copy of this machine's stack. */
    public function copy(): string
    {
        return AStacksScreen::Copy->forTheStack($this->stack);
    }

    /**
     * Putting one of this machine's copies back, by the name it was listed under.
     *
     * Text on the way in, because a template holds the names as text, and an
     * {@see ACopy} on the way out, which refuses a blank.
     */
    public function puttingBack(string $named): string
    {
        return AStacksScreen::PutBack->forTheStacksCopy($this->stack, ACopy::named($named));
    }

    /**
     * Putting back one run the record shows, by the stamp it keeps it under.
     *
     * Text on the way in, because a template holds the stamp as text, and an
     * {@see ARun} on the way out, which refuses a blank.
     */
    public function puttingARunBack(string $stamp): string
    {
        return AStacksScreen::RunBack->forTheStacksRun($this->stack, ARun::stamped($stamp));
    }

    /**
     * Stopping seeding one of this machine's completed downloads, by the name the account gave it.
     *
     * Text on the way in, because a template holds the names as text, and an
     * {@see ADownloadHeld} on the way out, which refuses a blank.
     */
    public function lettingGo(string $named): string
    {
        return AStacksScreen::LetGo->forTheStacksDownload($this->stack, ADownloadHeld::named($named));
    }

    /** Putting this machine's configuration back to lemonfiber's own, previewed before any yes. */
    public function reset(): string
    {
        return AStacksScreen::Reset->forTheStack($this->stack);
    }
}
