<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KeepingPhoneKeys;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundOfThePhoneKeys;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * The one place this application lists a stack's phone keys and revokes one.
 *
 * A list that could not be read is never an empty one, and a revoke the stack refused is said in its words.
 */
final readonly class PhoneKeyKeepers implements KeepingPhoneKeys
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function listedOn(Stack $stack, Session $session): WhatWasFoundOfThePhoneKeys
    {
        try {
            return WhatWasFoundOfThePhoneKeys::found(PhoneKeysListed::in(GatedClient::of($this->clients, $stack, $session)->phoneKeys()));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfThePhoneKeys::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|PhoneKeysAreUnreadable $why) {
            return WhatWasFoundOfThePhoneKeys::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    #[Override]
    public function revokedOn(Stack $stack, Session $session, APhoneKeysName $name): WhatWasFoundOfThePhoneKeys
    {
        try {
            return WhatWasFoundOfThePhoneKeys::found(PhoneKeysListed::in(GatedClient::of($this->clients, $stack, $session)->keyring()->revokeDevice($name->shown())));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: static fn(ARefusalInItsWords $said): WhatWasFoundOfThePhoneKeys => WhatWasFoundOfThePhoneKeys::refused($said),
                met: static fn(Obstacle $met): WhatWasFoundOfThePhoneKeys => WhatWasFoundOfThePhoneKeys::met($met),
            );
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|PhoneKeysAreUnreadable $why) {
            return WhatWasFoundOfThePhoneKeys::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
