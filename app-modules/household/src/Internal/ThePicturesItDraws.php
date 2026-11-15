<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use Closure;
use Modules\Household\Internal\Presenters\InWordsOrNone;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\Artwork;
use Modules\Kernel\Api\ArtworkKey;
use Modules\Kernel\Api\FetchingArtwork;
use Modules\Kernel\Api\HoldingArtwork;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowArtworkCame;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhichPicture;
use Modules\Kernel\Api\Whose;

/**
 * Where an image element draws a title's picture or a kept ask's cover from: the device's memory, fetched from the core first where it is not held yet.
 *
 * Answers the source an image element is given, or nothing where the core has
 * no such picture or could not be asked, which leaves the lettering drawn.
 * Fetching goes through the SDK on the session the screen is drawn for; what
 * arrives is held in memory only.
 */
final readonly class ThePicturesItDraws
{
    public function __construct(private HoldingArtwork $holding, private FetchingArtwork $fetching) {}

    /** The source of a held title's poster, as the member it is drawn for may see it, or null where the phone holds none. */
    public function ofAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): ?string
    {
        return $this->drawn(
            ArtworkKey::of($stack->id(), WhichPicture::HeldPoster, $title->named()),
            $stack,
            fn(): HowArtworkCame => $this->fetching->posterOfAHolding($stack, $session, $whose, $title),
        );
    }

    /** The source of a held title's backdrop, as the member it is drawn for may see it, or null where the phone holds none. */
    public function backdropOfAHolding(Stack $stack, Session $session, Whose $whose, HoldingId $title): ?string
    {
        return $this->drawn(
            ArtworkKey::of($stack->id(), WhichPicture::HeldBackdrop, $title->named()),
            $stack,
            fn(): HowArtworkCame => $this->fetching->backdropOfAHolding($stack, $session, $whose, $title),
        );
    }

    /** The source of the poster of a title a member's search offered, or null where the phone holds none. */
    public function ofAFoundTitle(Stack $stack, Session $session, AFoundTitle $title): ?string
    {
        return $this->drawn(
            ArtworkKey::of($stack->id(), WhichPicture::AskablePoster, $title->named()),
            $stack,
            fn(): HowArtworkCame => $this->fetching->posterOfAFoundTitle($stack, $session, $title),
        );
    }

    /** The source of the cover of a music or book ask the core keeps, as the member whose session asked it, or null where the phone holds none. */
    public function ofAKeptAsk(Stack $stack, Session $session, RequestId $ask): ?string
    {
        return $this->drawn(
            ArtworkKey::of($stack->id(), WhichPicture::KeptAskCover, $ask->reference()),
            $stack,
            fn(): HowArtworkCame => $this->fetching->coverOfAKeptAsk($stack, $session, $ask),
        );
    }

    /** @param Closure(): HowArtworkCame $fetched */
    private function drawn(ArtworkKey $key, Stack $stack, Closure $fetched): ?string
    {
        if ($this->holding->holds($key)) {
            return $key->said();
        }

        return $fetched()->either(
            arrived: fn(Artwork $artwork): InWordsOrNone => new InWordsOrNone($this->holding->hold($key, $stack->id(), $artwork) ? $key->said() : null),
            refused: static fn(): InWordsOrNone => new InWordsOrNone(null),
        )->said;
    }
}
