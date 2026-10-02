<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Attribute;

/**
 * What a screen says about whether its content changes while it is open.
 *
 * An attribute for {@see Concealed}'s reason: a screen extends a base class the
 * app does not own the shape of, and an attribute is the one declaration a rule
 * can insist on. `EveryCadenceIsDeclaredTest` insists on it for every screen,
 * and holds a screen that changes on its own to a cadence and one that does not
 * to none.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ItsContent
{
    public function __construct(public WhatItShowsDoes $does) {}
}
