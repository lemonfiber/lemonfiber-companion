<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where a plugin comes from, as the operator typed it.
 *
 * One of three shapes, and the stack tells them apart rather than this app: a
 * name in the catalogue, a directory on the machine, or a git repository with
 * the branch, tag or commit after its last `@`. Whether it holds a plugin is
 * the stack's answer.
 */
final readonly class APluginSource
{
    private function __construct(private string $said) {}

    /** What was typed, trimmed; blank is refused. */
    public static function typed(string $said): self
    {
        $trimmed = trim($said);

        if ($trimmed === '') {
            throw PluginSaysNothing::about('source');
        }

        return new self($trimmed);
    }

    /** The source, as it is sent and shown. */
    public function said(): string
    {
        return $this->said;
    }
}
