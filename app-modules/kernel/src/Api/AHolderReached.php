<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service that holds the credential, and how far a replacement reached it.
 *
 * `detail` is what still has to happen, or why it could not be reached; empty
 * where it holds the replacement.
 */
final readonly class AHolderReached
{
    private function __construct(private string $holder, private HowFarAHolderWasReached $reach, private string $detail) {}

    /** What the stack said of one holder; a blank name, or a pending or failed one that says nothing, is refused. */
    public static function said(string $holder, HowFarAHolderWasReached $reach, string $detail): self
    {
        if (trim($holder) === '') {
            throw CredentialSaysNothing::about('consumer');
        }

        if ($reach !== HowFarAHolderWasReached::Updated && trim($detail) === '') {
            throw CredentialSaysNothing::about('detail');
        }

        return new self($holder, $reach, trim($detail));
    }

    /** The service, by name. */
    public function holder(): string
    {
        return $this->holder;
    }

    /** How far the replacement reached it. */
    public function reach(): HowFarAHolderWasReached
    {
        return $this->reach;
    }

    /** What still has to happen, or why it could not be reached; empty where it holds the replacement. */
    public function detail(): string
    {
        return $this->detail;
    }
}
