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
use Lemonfiber\Sdk\Generated\RefusalCode;
use Modules\Kernel\Api\AFillAgreed;
use Modules\Kernel\Api\AFillTurnedDown;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ChoosingAFiller;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\FillSaysNothing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheFill;
use Modules\Kernel\Api\WhatToDoAboutWiring;
use Modules\Kernel\Api\WhyTheFillWasTurnedDown;
use Modules\Sdk\Api\Fields\LifecycleField;
use Modules\Sdk\Api\Fields\RestoreField;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see ChoosingAFiller}, answered by asking the stack.
 *
 * **One action, two calls, and the difference is the `offer`.** The core
 * reads `wiring-fill` without one as the reading — the whole choice worked
 * out, with the name it goes by, and nothing written — and with that name as
 * the choice made, where the reading worked out again still agrees. The
 * reading is also sent as a rehearsal, so nothing about it can write. Both
 * answer at once with the `substitution` envelope.
 *
 * **A refusal the stack names for a choice is read by its code**, never by
 * its sentence, so each reaches the operator in words of its own. One code
 * covers two answers, told apart by how much the stack says it matters: a
 * service that cannot fill the capability is an error, and one that already
 * fills it is advice that nothing needed to change. Every other refusal is
 * the stack's words or what stood in the way.
 */
final readonly class Fillers implements ChoosingAFiller
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function whatItWouldComeTo(Stack $stack, Session $session, Capability $capability, ServiceId $service): WhatBecameOfTheFill
    {
        return $this->asking($stack, $session, [
            WireField::Capability->value => $capability->named(),
            WireField::Service->value => $service->named(),
            LifecycleField::DryRun->value => true,
        ]);
    }

    public function choose(Stack $stack, Session $session, AFillAgreed $agreed): WhatBecameOfTheFill
    {
        $asked = [
            WireField::Capability->value => $agreed->capability()->named(),
            WireField::Service->value => $agreed->service()->named(),
            RestoreField::Offer->value => $agreed->offer(),
        ];

        // A blank reason is none, and the stack is not handed an empty one.
        return $this->asking($stack, $session, $agreed->reason() === '' ? $asked : [...$asked, WireField::Reason->value => $agreed->reason()]);
    }

    /**
     * The one call both methods make.
     *
     * @param array<string, bool|string> $asked
     */
    private function asking(Stack $stack, Session $session, array $asked): WhatBecameOfTheFill
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->act(
                Api::action(WhatToDoAboutWiring::Fill->asked()),
                $asked,
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            // Inside the same `try` as the call, for {@see Adjustments}'
            // reason: on a write, an answer this side could not read leaves
            // the operator not knowing whether it happened.
            return WhatBecameOfTheFill::fill(Substitutions::fillIn($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $this->refused($why);
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|SubstitutionIsUnreadable|FillSaysNothing $why) {
            return WhatBecameOfTheFill::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** A refusal the stack names for a choice, in words of its own, and any other as the stack said it or as what was met. */
    private function refused(CertificateWasRefused|RequestFailed $why): WhatBecameOfTheFill
    {
        $because = $why instanceof RequestFailed ? $this->because($why) : null;

        return WhatARefusalMeant::inItsWords(
            $why,
            refused: static fn(ARefusalInItsWords $said): WhatBecameOfTheFill => $because instanceof WhyTheFillWasTurnedDown
                ? WhatBecameOfTheFill::turnedDown(AFillTurnedDown::because($because, $said))
                : WhatBecameOfTheFill::refused($said),
            met: WhatBecameOfTheFill::met(...),
        );
    }

    /** Which refusal of a choice the stack's code names, or null where it names none of them. */
    private function because(RequestFailed $why): ?WhyTheFillWasTurnedDown
    {
        return match ($why->code()) {
            RefusalCode::NoSuchFiller => WhyTheFillWasTurnedDown::NoSuchService,
            RefusalCode::CannotFill => Severity::tryFrom((string) $why->refusal()?->severity()) === Severity::Advisory
                ? WhyTheFillWasTurnedDown::AlreadyFills
                : WhyTheFillWasTurnedDown::CannotFill,
            RefusalCode::NothingAsks => WhyTheFillWasTurnedDown::NothingAsks,
            RefusalCode::ChoiceUnwritable => WhyTheFillWasTurnedDown::NowhereToKeepIt,
            RefusalCode::WiringMoved => WhyTheFillWasTurnedDown::Moved,
            RefusalCode::Unreasonable => WhyTheFillWasTurnedDown::ReasonCannotBeKept,
            default => null,
        };
    }
}
