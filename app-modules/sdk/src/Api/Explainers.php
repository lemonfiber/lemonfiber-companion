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
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundOfTheWords;
use Modules\Kernel\Api\WhatWasSaidOfOneWord;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack what lemonfiber's words mean.
 *
 * Written the way {@see Inspectors} is. It asks the explain endpoint naming no
 * word, which is how the whole glossary is asked for, and naming one, which is
 * how that one word is.
 *
 * **A word the stack does not explain is an answer.** It is refused as absent,
 * and that refusal is the stack saying it has no entry rather than a stack
 * that failed, so it is carried as {@see WhatWasSaidOfOneWord::unexplained()}.
 */
final readonly class Explainers implements Explaining
{
    /** The status a stack refuses a word it does not explain with. */
    private const int NO_ENTRY_FOR_IT = 404;

    public function __construct(private Clients $clients) {}

    public function glossaryOn(Stack $stack, Session $session): WhatWasFoundOfTheWords
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::EXPLAIN_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfTheWords::found(TheWordsExplained::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheWords::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|GlossaryIsUnreadable $why) {
            return WhatWasFoundOfTheWords::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function wordOn(Stack $stack, Session $session, AWordInUse $word): WhatWasSaidOfOneWord
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::EXPLAIN_ENDPOINT, [WireField::Word->value => $word->said()]);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasSaidOfOneWord::explained(TheWordsExplained::one($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refusedAWord($why);
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|GlossaryIsUnreadable $why) {
            return WhatWasSaidOfOneWord::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** A refusal as absent is the stack having no entry for the word; any other is what the operator met. */
    private function refusedAWord(CertificateWasRefused|RequestFailed $why): WhatWasSaidOfOneWord
    {
        return $why instanceof RequestFailed && $why->status() === self::NO_ENTRY_FOR_IT
            ? WhatWasSaidOfOneWord::unexplained()
            : WhatWasSaidOfOneWord::met(WhatARefusalMeant::obstacle($why));
    }
}
