<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_values;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\StreamInterrupted;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\PullEnvelope;
use Lemonfiber\Sdk\Generated\StartEnvelope;
use Lemonfiber\Sdk\Generated\StepEnvelope;
use Modules\Kernel\Api\AJobSaidNothing;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheJobsSaid;
use Modules\Kernel\Api\WhatWasHeardFromAJob;
use Modules\Sdk\Internal\TheStreamsHeld;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Modules\Sdk\Internal\WhoseLines;
use Modules\Sdk\Internal\Wire;

/**
 * The lines the jobs a screen follows say on a stack's event stream: their `start`, `pull` and `step` lines, each job's in the order they arrived.
 *
 * Every job is heard in the one reading, because a reading takes what arrived
 * since the last: a line read for one job is not there to read for another.
 *
 * Held as {@see StartLines} holds its stream, and bound fresh for each screen for its reason.
 */
final readonly class JobLines implements HearingTheJobs
{
    private TheStreamsHeld $streams;

    public function __construct(private Clients $clients)
    {
        $this->streams = new TheStreamsHeld($clients);
    }

    public function whatTheySaid(Stack $stack, Session $session, Job $job, Job ...$others): WhatTheJobsSaid
    {
        $jobs = [$job, ...array_values($others)];

        try {
            $arrived = $this->streams->taken($stack, $session);
            $lines = [];

            foreach ($arrived->everySaidBy(WhoseLines::anyOf(...$jobs), StartEnvelope::KIND, PullEnvelope::KIND, StepEnvelope::KIND) as $envelope) {
                $lines[(string) $envelope->job][] = $this->lineIn($envelope);
            }

            if ($arrived->ended) {
                $this->streams->letGoOf($stack);
            }

            return $this->heardIn($lines, $jobs);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheJobsSaid::met(WhatARefusalMeant::obstacle($why));
        } catch (Unreachable|ApiVersionMismatch|UnreadableResponse|UnexpectedKind|StreamInterrupted|AJobSaidNothing|WalkthroughIsUnreadable|EnvelopeIsNotRead|JobHasNoName $why) {
            $this->streams->letGoOf($stack);

            return WhatTheJobsSaid::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function letGo(): WhatTheJobsSaid
    {
        $this->streams->letGoOfEvery();

        return WhatTheJobsSaid::nothingNew();
    }

    /**
     * What each job asked about said, by the name the stack gave it.
     *
     * @param array<string, non-empty-list<string>> $lines
     * @param list<Job>                             $jobs
     */
    private function heardIn(array $lines, array $jobs): WhatTheJobsSaid
    {
        $heard = WhatTheJobsSaid::nothingNew();

        foreach ($jobs as $job) {
            $heard = array_key_exists($job->shown(), $lines) ? $heard->with($job, WhatWasHeardFromAJob::said(...$lines[$job->shown()])) : $heard;
        }

        return $heard;
    }

    /**
     * The sentence one line carries: a step's, or the bare text a start or a pull line is.
     *
     * @param Envelope<mixed> $envelope
     */
    private function lineIn(Envelope $envelope): string
    {
        if ($envelope->kind === StepEnvelope::KIND->value) {
            return Walkthroughs::said($envelope)->said();
        }

        $said = $this->payloadOf($envelope);

        return is_string($said) ? $said : throw AJobSaidNothing::inALine();
    }

    /**
     * A start or pull line's payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Origins::payload()}'s reason: the
     * generated envelope asserts its shape without checking it.
     *
     * @param Envelope<mixed> $envelope
     */
    private function payloadOf(Envelope $envelope): mixed
    {
        $checked = Wire::checked($envelope);

        return ($checked->kind === StartEnvelope::KIND->value ? StartEnvelope::in($checked) : PullEnvelope::in($checked))->data;
    }
}
