<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\ConnectingADevice;
use Modules\Kernel\Api\HandingOverADevice;
use Modules\Kernel\Api\HandoffSaysNothing;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheDoorSaysNothing;
use Modules\Kernel\Api\WhatBecameOfTheHandoff;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhatTheReachMet;

/**
 * The one place this application asks a stack to hand one person's device over, and follows it.
 *
 * Built the way {@see Removers} is: the action is asked by the name
 * {@see ConnectingADevice} spells, with the person's name, and the stack
 * answers with a handle the hand-off arrives through. A name the stack turns
 * down is said in its own words and handed on as the refusal.
 *
 * **No key rides on the asking**, for {@see HandingOverADevice}' reason.
 */
final readonly class Connectors implements HandingOverADevice
{
    public function __construct(private Clients $clients) {}

    public function handOver(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheHandoff
    {
        try {
            return $this->underway($this->clients->client($stack, $session)->act(
                Api::action(ConnectingADevice::HandOver->asked()),
                [WireField::Name->value => $who->name()],
            ));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse $why) {
            return WhatBecameOfTheHandoff::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheHandoff
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusal($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HandoffIsUnreadable|HandoffSaysNothing|InvitationSaysNothing|TheDoorSaysNothing $why) {
            return WhatBecameOfTheHandoff::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for
     * {@see Ushers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): WhatBecameOfTheHandoff
    {
        try {
            return $this->clients->client($stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): WhatBecameOfTheHandoff => WhatBecameOfTheHandoff::underway($job),
                finished: static fn(Envelope $envelope): WhatBecameOfTheHandoff
                    => WhatBecameOfTheHandoff::answered(Handoffs::in($envelope)),
                ended: static fn(): WhatBecameOfTheHandoff => WhatBecameOfTheHandoff::ended(),
            );
        } catch (NoSuchJob) {
            return WhatBecameOfTheHandoff::ended();
        }
    }

    /**
     * The handle the asking was answered with, or the stack not having answered in a way this can follow.
     *
     * @param Envelope<mixed> $envelope
     */
    private function underway(Envelope $envelope): WhatBecameOfTheHandoff
    {
        try {
            return WhatBecameOfTheHandoff::underway(Handles::in($envelope));
        } catch (ApiVersionMismatch|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return WhatBecameOfTheHandoff::met(WhatTheReachMet::byItself($why));
        }
    }

    /** What a request the stack turned down means: its own sentence, or an obstacle. */
    private function refusal(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheHandoff
    {
        $said = $why instanceof RequestFailed ? WhatARefusalMeant::inItsOwnWords($why) : null;

        return $said === null
            ? WhatBecameOfTheHandoff::met(WhatARefusalMeant::obstacle($why))
            : WhatBecameOfTheHandoff::refused($said);
    }
}
