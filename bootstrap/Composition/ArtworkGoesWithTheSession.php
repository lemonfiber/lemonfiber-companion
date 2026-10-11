<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HoldingArtwork;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Resumed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;

/**
 * The secure store, letting go of a stack's artwork whenever the session it was drawn for ends or is replaced.
 *
 * A session forgotten, by a refusal, a sign-out or the stack's removal, and a
 * session kept in its place, for somebody else whose limits may differ, both
 * leave no picture behind that the earlier session fetched.
 */
final readonly class ArtworkGoesWithTheSession implements SecureStorage
{
    public function __construct(private SecureStorage $store, private HoldingArtwork $artwork) {}

    public function isAvailable(): bool
    {
        return $this->store->isAvailable();
    }

    public function keep(StackId $stack, Session $session, Whose $whose): Kept
    {
        $this->artwork->letGoOf($stack);

        return $this->store->keep($stack, $session, $whose);
    }

    public function forget(StackId $stack): Kept
    {
        $this->artwork->letGoOf($stack);

        return $this->store->forget($stack);
    }

    public function resume(StackId $stack): Resumed
    {
        return $this->store->resume($stack);
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $this->artwork->letGoOf($stack);

        return $this->store->forgetTheStack($stack);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->store->keepsAnythingOf($stack);
    }
}
