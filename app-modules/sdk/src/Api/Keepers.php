<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\HowTheHandoverWent;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatKeepsRunning;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a machine what it keeps running.
 *
 * Every call to a stack goes through the SDK, so this sits beside
 * {@see Stalls} and is written the same way: it asks {@see PinnedClients} for
 * the connection rather than building one, which is what keeps the certificate
 * pin in a single file. This class never names a client constructor, so it
 * cannot make a decision about whether a certificate is checked.
 *
 * **The endpoint takes nothing**, so there is no filter to get wrong and no
 * narrowing a screen could be tempted into asking for row by row.
 *
 * **Four raises, two answers**, which is {@see Stalls}' collapse for its
 * reason: the endpoint refusing, the answer being unreadable, and the two ends
 * disagreeing about the API version are three faults with one meaning for
 * somebody holding a phone — they cannot see what this machine keeps running,
 * and the machine is where to look. The exception is a refused session, which
 * is a different sentence and is told apart by {@see WhatARefusalMeant}.
 *
 * **A machine with no service manager is not one of the four.** It answers
 * normally, through the first arm, carrying the sentence saying what to do
 * instead — the reading a platform gave, not a failure to reach it. Folding it
 * in here would send an operator to check their network about a laptop that was
 * never going to run a launch agent.
 *
 * **Handing a command over answers now, with the same envelope.** The stack
 * carries an install or a removal out on the spot and answers with the
 * `hosting` envelope, read after the act and carrying what it changed — so
 * {@see Handovers} reads it rather than a name for work.
 *
 * **A refusal the stack put into words is carried in those words.** A guard
 * asked to guard no forms, or a manager that would not take a definition, is
 * the machine answering and saying why; reading it as a stack that did not
 * answer would send an operator to check their network. A refused session and
 * a refused account stay the obstacles {@see WhatARefusalMeant} names.
 *
 * **The key that names an attempt is minted at the moment of sending**, for
 * {@see Supervisors}' reason, and nothing here keeps it.
 */
final readonly class Keepers implements Hosting
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function keptRunningOn(Stack $stack, Session $session): WhatKeepsRunning
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::HOSTING_ENDPOINT);

            // Inside the same `try` as the request, deliberately — the argument
            // {@see Stalls::stoppedOn()} makes. A payload the client fetched
            // and this side could not read is the same thing to an operator as
            // one that never arrived, and reading it outside would put an
            // uncaught raise on a screen instead.
            return WhatKeepsRunning::keeps(Hosts::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatKeepsRunning::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HostingIsUnreadable) {
            return WhatKeepsRunning::met(Obstacle::StackDidNotAnswer);
        }
    }

    public function handOver(Stack $stack, Session $session, HostingAgreed $agreed): HowTheHandoverWent
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(
                Api::action($agreed->doing()->asked()),
                // Spelled here rather than through a field enum: `kept` is a
                // word this app sends and never reads back, and the enums
                // under `Fields` hold the words a reader reaches for.
                ['kept' => $agreed->named()],
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return HowTheHandoverWent::did(Handovers::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusedWith($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|HostingIsUnreadable) {
            return HowTheHandoverWent::met(Obstacle::StackDidNotAnswer);
        }
    }

    /**
     * The stack's own words for why it would not, or the obstacle the refusal was.
     *
     * A machine that is not the one paired, a refused session and a refused
     * account are read first, because any words beside them are about the
     * connection rather than about the command.
     */
    private function refusedWith(CertificateWasRefused|RequestFailed $why): HowTheHandoverWent
    {
        $met = WhatARefusalMeant::obstacle($why);
        $said = $why instanceof RequestFailed ? $why->said() : null;

        if ($met !== Obstacle::StackDidNotAnswer || $said === null) {
            return HowTheHandoverWent::met($met);
        }

        return HowTheHandoverWent::refused($said);
    }
}
