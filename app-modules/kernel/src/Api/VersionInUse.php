<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The release a stack is standing on, as the stack reported it.
 *
 * Deliberately not a {@see Release}, though the wire sends both under one shape
 * and one reader in the adapter reads them both. {@see TakingAnUpdate::agreed()}
 * takes a `Release`, so handing the version already in use out as one would make
 * *take this one* spellable against the thing the machine is already running —
 * and an update the stack did not report as pending is never
 * applied. Two types that cannot be substituted is what turns that from a rule
 * the update screen has to remember into a sentence that will not compile.
 *
 * **It refuses to answer with a `Release`, and that is the whole of it.**
 * Narrowing one on the way in is how it is built; there is no way back out,
 * because every way back out is a way to the apply path. It closes with
 * the same door: a stack standing on a release that has since been taken back
 * is a real state an operator has to be told about, and a value that could say
 * so *and* be applied is one somebody would eventually offer them as an update.
 *
 * It answers no comparison with a `Release` either. Whether the version in use
 * is also listed as waiting is a real question and it is not this type's — the
 * stack decides what is pending, and an app that worked that out by comparing
 * two version strings is refused by name.
 */
final readonly class VersionInUse
{
    private function __construct(private string $version, private bool $withdrawn) {}

    /**
     * The one place a release the stack named becomes the one it is standing on.
     *
     * Takes the {@see Release} rather than the fields under it, so the adapter
     * reads the running block with the reader it already reads the changelog
     * with: the wire sends one shape and a second reader for it is a second
     * place for it to drift. What crosses here is the reading. What does not
     * cross is the type.
     */
    public static function of(Release $release): self
    {
        return new self($release->version(), $release->wasWithdrawn());
    }

    /** The version, which is the one thing a screen draws from this. */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * Whether the release this stack is standing on has been taken back.
     *
     * Copied off the release rather than answered by reaching back to it, for
     * the reason the class docblock gives. There are two errands and this is
     * the second one: the list of what to take next leaves a withdrawn release
     * out, and somebody standing on one still has to be told.
     */
    public function wasWithdrawn(): bool
    {
        return $this->withdrawn;
    }
}
