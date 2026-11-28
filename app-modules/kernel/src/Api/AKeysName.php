<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The name an integration key was minted under, which revoking it names.
 *
 * Not a secret: the listing shows it, and the journal records it.
 */
final readonly class AKeysName
{
    private function __construct(private string $name) {}

    /** The one place a string becomes a key's name; a blank one, or one that is no path segment, is refused. */
    public static function of(string $name): self
    {
        if (trim($name) === '' || NoPathSegment::tryFrom($name) instanceof NoPathSegment) {
            throw KeySaysNothing::about('name');
        }

        return new self($name);
    }

    /** The name as the stack wrote it. */
    public function shown(): string
    {
        return $this->name;
    }
}
