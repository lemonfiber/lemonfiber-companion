<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatToFollow;
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
 * here. The rest each take the one more thing their path carries, as text from
 * a template or as the value already named, and give it its type on the way
 * through.
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

    /** Where one item got to on this machine. */
    public function traceOf(WhatToFollow $item): string
    {
        return AStacksScreen::Trace->forTheStacksItem($this->stack, $item);
    }

    /** What one of this machine's services has been saying. */
    public function logsOf(ServiceId $service): string
    {
        return AStacksScreen::Logs->forTheStacksService($this->stack, $service);
    }

    /** One service of this machine, and the verbs about it. */
    public function doingWith(ServiceId $service): string
    {
        return AStacksScreen::Doing->forTheStacksService($this->stack, $service);
    }

    /**
     * One whole form of this machine, and the verbs about it.
     *
     * The same screen as the one above, because what an operator is choosing
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
        return AStacksScreen::Doing->forTheStacksForm($this->stack, Form::called($named));
    }

    /** What one of lemonfiber's words means, opened on its own. */
    public function wordAbout(AWordInUse $word): string
    {
        return AStacksScreen::WordAbout->forTheStacksWord($this->stack, $word);
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
        return AStacksScreen::TakeOut->forTheStacksMember($this->stack, SomebodyInTheHousehold::called($named));
    }

    /** Where somebody is asked in with their name already typed. */
    public function inviting(string $named): string
    {
        return AStacksScreen::InviteNamed->forTheStacksMember($this->stack, SomebodyInTheHousehold::called($named));
    }

    /** Where one member's device is connected, by the name their account is held under. */
    public function connecting(string $named): string
    {
        return AStacksScreen::Device->forTheStacksMember($this->stack, SomebodyInTheHousehold::called($named));
    }

    /** Putting one of this machine's copies back, by the name it was listed under. */
    public function puttingBack(string $named): string
    {
        return AStacksScreen::PutBack->forTheStacksCopy($this->stack, ACopy::named($named));
    }

    /** Putting back one run the record shows, by the stamp it keeps it under. */
    public function puttingARunBack(string $stamp): string
    {
        return AStacksScreen::RunBack->forTheStacksRun($this->stack, ARun::stamped($stamp));
    }

    /** Stopping seeding one of this machine's completed downloads, by the name the account gave it. */
    public function lettingGo(string $named): string
    {
        return AStacksScreen::LetGo->forTheStacksDownload($this->stack, ADownloadHeld::named($named));
    }
}
