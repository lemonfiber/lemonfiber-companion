<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatAScreenIsAbout;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where one stack's screens live, which is the only place that knows.
 *
 * The paths themselves are {@see AStacksScreen}'s, which the provider registers
 * from. This is the half that knows *which machine*; that one knows *which
 * screen*, and neither can be renamed without the other following. A screen
 * that sends somebody to another of the machine's screens asks here rather
 * than building `/stacks/%s/…` for itself, so a rename has one place to land.
 *
 * **One route table.** A screen reached by naming the machine alone is
 * {@see self::to()} with its case, so the next such screen costs no method
 * here. A screen about one thing is {@see self::about()} with its case and
 * that thing as the value already named. The rest each take that thing as the
 * text a template holds, and give it its type on the way through.
 *
 * **It takes what a pairing wrote down and gives it a name here.** Two screens
 * reach this holding only the identifier and nothing else, before anything has
 * looked the stack back up. The string is admitted at {@see self::rememberedAs()}
 * and becomes a {@see StackId} there, where it is checked and given a name.
 *
 * `Internal` because where a screen lives is a detail of this module's own
 * surface, which can be renamed without reading another.
 */
final readonly class WhereAStackIs
{
    private function __construct(private StackId $stack) {}

    /** A stack this device holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack);
    }

    /**
     * A stack named by what a pairing wrote down.
     *
     * For the two screens that finish a pairing and send the operator onwards
     * before anything has read the stack back. They hold the identifier and
     * nothing else, which is enough to say where to go.
     */
    public static function rememberedAs(string $stored): self
    {
        return new self(StackId::rememberedAs($stored));
    }

    /**
     * One of this machine's screens that naming the machine is enough to reach.
     *
     * A case whose path carries more than the machine is refused by
     * {@see AStacksScreen::forTheStack()}, so a button cannot be handed a path
     * that resolves to nothing; those screens are the methods below.
     */
    public function to(AStacksScreen $screen): string
    {
        return $screen->forTheStack($this->stack);
    }

    /** One of this machine's screens about one thing; a case whose path names only the machine is refused. */
    public function about(AStacksScreen $screen, WhatAScreenIsAbout $about): string
    {
        return $screen->forTheStacks($this->stack, $about);
    }

    /**
     * One whole form of this machine, and the verbs about it.
     *
     * The screen a service opens, because what an operator is choosing
     * between is identical and only the name the stack is told differs.
     *
     * Text on the way in and a {@see Form} on the way out. A form reaches a
     * screen as the name the stack sent — the listing carries `list<string>`
     * and the verb has always been asked for by that name — and what puts those
     * strings there is {@see Form::named()}, so a blank cannot arrive by that
     * road. {@see Form::called()} refuses one anyway, which is the check the
     * boundary is entitled to rather than a raise waiting on a tap.
     */
    public function doingWithTheForm(string $named): string
    {
        return AStacksScreen::Doing->forTheStacks($this->stack, Form::called($named));
    }

    /**
     * Where one member is taken out of the household, by the name their account is held under.
     *
     * Text on the way in, because a template holds the names as text, and a
     * {@see SomebodyInTheHousehold} on the way out, which refuses a blank. The
     * same holds for every method below that takes text.
     */
    public function takingOut(string $named): string
    {
        return AStacksScreen::TakeOut->forTheStacks($this->stack, SomebodyInTheHousehold::called($named));
    }

    /** Where accepting the warning this check raises is weighed, before anything is sent. */
    public function accepting(string $check): string
    {
        return AStacksScreen::Accept->forTheStacks($this->stack, Check::of($check));
    }

    /** Where running this check, which disturbs something, is weighed before anything is sent. */
    public function running(string $check): string
    {
        return AStacksScreen::Run->forTheStacks($this->stack, Check::of($check));
    }

    /** Where somebody is asked in with their name already typed. */
    public function inviting(string $named): string
    {
        return AStacksScreen::InviteNamed->forTheStacks($this->stack, SomebodyInTheHousehold::called($named));
    }

    /** Where one member's device is connected, by the name their account is held under. */
    public function connecting(string $named): string
    {
        return AStacksScreen::Device->forTheStacks($this->stack, SomebodyInTheHousehold::called($named));
    }

    /** Putting one of this machine's copies back, by the name it was listed under. */
    public function puttingBack(string $named): string
    {
        return AStacksScreen::PutBack->forTheStacks($this->stack, ACopy::named($named));
    }

    /** One plugin's settings and secrets, by the id it is installed under. */
    public function aPluginsSettings(string $plugin): string
    {
        return AStacksScreen::PluginSettings->forTheStacks($this->stack, APluginId::called($plugin));
    }

    /** Putting back one run the record shows, by the stamp it keeps it under. */
    public function puttingARunBack(string $stamp): string
    {
        return AStacksScreen::RunBack->forTheStacks($this->stack, ARun::stamped($stamp));
    }

    /** Stopping seeding one of this machine's completed downloads, by the name the account gave it. */
    public function lettingGo(string $named): string
    {
        return AStacksScreen::LetGo->forTheStacks($this->stack, ADownloadHeld::named($named));
    }
}
