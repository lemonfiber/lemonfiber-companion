<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * What the gate asks of the repository it is judging.
 *
 * Every answer is about committed content, relative to the repository's root,
 * and an answer that cannot be given is said as such — null, or an empty
 * string — never guessed at, because each caller turns "cannot tell" into
 * "mutate everything".
 */
interface TheRepository
{
    /**
     * Every path a change since that commit touched, or null where git cannot say.
     *
     * @return list<string>|null
     */
    public function changedSince(string $since): ?array;

    /** The lines one path's change since that commit touched, as a zero-context diff, or null where git cannot say. */
    public function changeTo(string $since, string $path): ?string;

    /**
     * Every file under the test trees that names this word.
     *
     * @return list<string>
     */
    public function testFilesNaming(string $name): array;

    /**
     * Every file git tracks, with the blob it holds.
     *
     * @return array<string, string>
     */
    public function tracked(): array;

    /** Whether every tracked file on disk is what git holds for it. */
    public function isUnchanged(): bool;

    /** What a file holds, or an empty string where it cannot be read. */
    public function contentsOf(string $path): string;

    public function exists(string $path): bool;
}
