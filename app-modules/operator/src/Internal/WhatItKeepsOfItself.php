<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where the screens are that read what one machine keeps about itself.
 *
 * What it changed, where what it runs came from, what it sends, what it will
 * wake somebody for, how it shares the line, what else already stands on it
 * and how good the media it fetches should be — screens answering one kind of
 * question, which is what the machine does and keeps on its own account while
 * nobody is looking — and the record it keeps of walking one thing through.
 * The screens that change what it keeps are {@see WhatItIsAskedToChange}'s.
 * Apart from {@see WhereAStackIs} because that is where the questions change
 * subject, and because that class had reached the twenty-method ceiling `H3`
 * refuses: one accessor there hands out this, and the next screen of this kind
 * costs it nothing.
 *
 * `Internal`, for {@see WhereAStackIs}' reason.
 */
final readonly class WhatItKeepsOfItself
{
    private function __construct(private StackId $stack) {}

    /** The screens of this kind for one stack this device holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack);
    }

    /** Which versions this machine runs, and what the running release changed. */
    public function versions(): string
    {
        return AStacksScreen::Versions->forTheStack($this->stack);
    }

    /** What this machine has changed about itself. */
    public function record(): string
    {
        return AStacksScreen::Record->forTheStack($this->stack);
    }

    /** Where every service on this machine comes from. */
    public function origins(): string
    {
        return AStacksScreen::Origins->forTheStack($this->stack);
    }

    /** What each service on this machine is for, and what became of any it dropped. */
    public function catalogue(): string
    {
        return AStacksScreen::Catalogue->forTheStack($this->stack);
    }

    /** Everything that leaves this machine. */
    public function leaving(): string
    {
        return AStacksScreen::Leaving->forTheStack($this->stack);
    }

    /** What this machine will tell its operator about. */
    public function told(): string
    {
        return AStacksScreen::Told->forTheStack($this->stack);
    }

    /** How this machine shares its line with the household. */
    public function line(): string
    {
        return AStacksScreen::Line->forTheStack($this->stack);
    }

    /** What this machine keeps, where, and why, and the copies it holds. */
    public function keeps(): string
    {
        return AStacksScreen::Keeps->forTheStack($this->stack);
    }

    /**
     * Where the screens are that change what this machine keeps, on a yes:
     * its copies, its runs, a download, its configuration, and lemonfiber itself.
     */
    public function changing(): WhatItIsAskedToChange
    {
        return WhatItIsAskedToChange::of($this->stack);
    }

    /** How full this machine is, and where the room went. */
    public function room(): string
    {
        return AStacksScreen::Room->forTheStack($this->stack);
    }

    /** What lemonfiber's words mean. */
    public function words(): string
    {
        return AStacksScreen::Words->forTheStack($this->stack);
    }

    /** What one of lemonfiber's words means, opened on its own. */
    public function wordAbout(AWordInUse $word): string
    {
        return AStacksScreen::WordAbout->forTheStacksWord($this->stack, $word);
    }

    /** Where a guard on this machine's data location is started, and held while that screen asks. */
    public function guard(): string
    {
        return AStacksScreen::Guard->forTheStack($this->stack);
    }
}
