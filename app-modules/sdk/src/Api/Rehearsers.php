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
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Rehearsing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheRehearsalFound;
use Modules\Sdk\Api\Fields\PreviewField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/** Asks a stack what starting a form would come to, through the forms read naming that form. */
final readonly class Rehearsers implements Rehearsing
{
    public function __construct(private Clients $clients) {}

    public function whatStarting(Stack $stack, Session $session, Form $form): WhatTheRehearsalFound
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->read(Api::FORMS_ENDPOINT, [PreviewField::Form->value => $form->named()]);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatTheRehearsalFound::found(Rehearsals::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatTheRehearsalFound::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|RehearsalIsUnreadable) {
            return WhatTheRehearsalFound::met(Obstacle::StackDidNotAnswer);
        }
    }
}
