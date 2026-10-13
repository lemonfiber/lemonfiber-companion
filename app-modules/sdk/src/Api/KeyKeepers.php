<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\KeepingKeysForApps;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasFoundOfTheKeys;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * The one place this application lists a stack's integration keys and revokes one.
 *
 * A list that could not be read is never an empty one. Minting is not asked for
 * here: the app mints a key only as a claim proved by a device key, which the
 * stack has to serve first.
 */
final readonly class KeyKeepers implements KeepingKeysForApps
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function listedOn(Stack $stack, Session $session): WhatWasFoundOfTheKeys
    {
        try {
            return WhatWasFoundOfTheKeys::found(KeysListed::in(GatedClient::of($this->clients, $stack, $session)->keys()));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheKeys::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|KeysAreUnreadable $why) {
            return WhatWasFoundOfTheKeys::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    #[Override]
    public function revokedOn(Stack $stack, Session $session, AKeysName $name): WhatWasFoundOfTheKeys
    {
        try {
            return WhatWasFoundOfTheKeys::found(KeysListed::in(GatedClient::of($this->clients, $stack, $session)->keyring()->revoke($name->shown())));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfTheKeys::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|KeysAreUnreadable $why) {
            return WhatWasFoundOfTheKeys::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }
}
