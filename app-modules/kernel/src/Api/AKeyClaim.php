<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A key minted as a claim: the link that collects it once, and when that lapses.
 *
 * The link is shown as the stack gave it and is never built, changed or kept here.
 */
final readonly class AKeyClaim
{
    private function __construct(private AKeysName $name, private string $link, private AMomentAsWritten $lapses) {}

    public static function of(AKeysName $name, string $link, AMomentAsWritten $lapses): self
    {
        if (trim($link) === '') {
            throw KeySaysNothing::about('link');
        }

        return new self($name, $link, $lapses);
    }

    /** The key's name. */
    public function name(): AKeysName
    {
        return $this->name;
    }

    /** What a code of it carries, which is the link that collects the key, as the stack gave it. */
    public function carried(): string
    {
        return $this->link;
    }

    /** When the link lapses. */
    public function lapses(): AMomentAsWritten
    {
        return $this->lapses;
    }
}
