<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a request is called: its title, or, where no service has named it yet, only what kind of thing it is.
 *
 * A request still waiting for a yes has been handed to nobody, so the core may
 * hold no title for it. It is still a request somebody can rule on.
 */
final readonly class WhatARequestIsCalled
{
    private function __construct(private string $title, private KindOfRequest $kind) {}

    /** A request with a title, refused when blank. */
    public static function named(string $title): self
    {
        $said = trim($title);

        if ($said === '') {
            throw RequestHasNobodyBehindIt::namedBlank();
        }

        return new self($said, KindOfRequest::Something);
    }

    /** A request nothing has named yet, by the kind of thing it is. */
    public static function notYetNamed(KindOfRequest $kind): self
    {
        return new self('', $kind);
    }

    /**
     * @template R of object
     *
     * @param Closure(string): R        $named
     * @param Closure(KindOfRequest): R $notYet
     *
     * @return R
     */
    public function either(Closure $named, Closure $notYet): object
    {
        return $this->title === '' ? $notYet($this->kind) : $named($this->title);
    }
}
