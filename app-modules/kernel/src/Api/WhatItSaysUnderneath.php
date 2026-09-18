<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * The technical detail under a finding, where the core gave one.
 *
 * Two things are asked for at once and they pull against each other: the
 * plain explanation must lead, and the underlying detail must be **available**.
 * A screen that shows only the plain sentence fails the second half quietly —
 * an operator who knows what a socket is gets the same paragraph as everybody
 * else and no way through to the line that would have told them.
 *
 * **A value rather than a nullable string, which is `C2`'s cure.** The detail is
 * absent on most findings, and a screen handed `null` prints an empty line
 * where a heading is — or worse, a heading with nothing under it, which reads
 * as *we know something and are not telling you*. Two constructors and a
 * two-arm reader mean a caller cannot render one without saying what happens
 * when there is none.
 *
 * **Blank is absent.** A core that sends an empty string has said nothing, and
 * a screen that told them apart would be drawing a distinction the operator
 * cannot see.
 */
final readonly class WhatItSaysUnderneath
{
    private function __construct(private string $said) {}

    /** The core gave a technical detail. */
    public static function said(string $detail): self
    {
        return new self(trim($detail));
    }

    /** It gave none, which is the ordinary case. */
    public static function none(): self
    {
        return new self('');
    }

    /**
     * Say the detail, or say there is none.
     *
     * Two arms rather than a nullable getter, for the reason {@see Daemon::exit()}
     * gives — and here the stakes are the rule's own: the half of it a
     * screen forgets is the half where nothing is there.
     *
     * @template TSaid of object
     * @template TNone of object
     *
     * @param  Closure(string): TSaid  $said
     * @param  Closure(): TNone  $none
     * @return TSaid|TNone
     */
    public function either(Closure $said, Closure $none): object
    {
        return $this->said === '' ? $none() : $said($this->said);
    }
}
