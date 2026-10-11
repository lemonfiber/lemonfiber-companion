<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Picture;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\Artwork;
use Modules\Kernel\Api\ArtworkSaysNothing;
use Modules\Kernel\Api\FetchingArtwork;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowArtworkCame;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Internal\AsTheHouseholdsDefaults;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatAPosterIsAskedWith;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * A title's pictures, asked of the core's `/api/held/{id}/poster`, `/api/held/{id}/backdrop` and `/api/askable/{id}/poster` on the session they are drawn for.
 *
 * Pinned as every request is, and bounded by the SDK to the picture types and
 * size the contract allows. A held title's pictures are asked as the member they are
 * drawn for, and as the household's defaults for the operator's preview; a
 * found title's as the session's own member, with the kind its page named.
 */
final readonly class Pictures implements FetchingArtwork
{
    public function __construct(private Clients $clients) {}

    #[Override]
    public function posterOfAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): HowArtworkCame
    {
        try {
            return $this->arrived(GatedClient::of($this->clients, $stack, $session)->poster($title->named(), $this->asWhom($whose)->query));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowArtworkCame::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|ConfigurationProblem|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|ArtworkSaysNothing $why) {
            return HowArtworkCame::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    #[Override]
    public function backdropOfAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): HowArtworkCame
    {
        try {
            return $this->arrived(GatedClient::of($this->clients, $stack, $session)->backdrop($title->named(), $this->asWhom($whose)->query));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowArtworkCame::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|ConfigurationProblem|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|ArtworkSaysNothing $why) {
            return HowArtworkCame::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    #[Override]
    public function posterOfAFoundTitle(Stack $stack, Session $session, AFoundTitle $title): HowArtworkCame
    {
        try {
            return $this->arrived(GatedClient::of($this->clients, $stack, $session)->askablePoster($title->id(), ['kind' => $title->kind()->value]));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return HowArtworkCame::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|ConfigurationProblem|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|ArtworkSaysNothing $why) {
            return HowArtworkCame::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /** What a held title's picture is asked with: as the member it is drawn for, or as the household's defaults for the operator. */
    private function asWhom(Whose $whose): WhatAPosterIsAskedWith
    {
        return $whose->either(
            operator: static fn(): WhatAPosterIsAskedWith => new WhatAPosterIsAskedWith(AsTheHouseholdsDefaults::QUERY),
            member: static fn(string $member): WhatAPosterIsAskedWith => new WhatAPosterIsAskedWith(['member' => $member]),
        );
    }

    private function arrived(Picture $picture): HowArtworkCame
    {
        return HowArtworkCame::arrived(Artwork::of($picture->bytes()));
    }
}
