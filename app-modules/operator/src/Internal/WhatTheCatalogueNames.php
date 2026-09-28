<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What took a dropped service's place, carried out of a fold.
 *
 * A fold hands back an object, so the name travels in one. Empty is the arm
 * where nothing took its place, and it can only be that: the value one layer
 * down refuses a blank replacement.
 */
final readonly class WhatTheCatalogueNames
{
    public function __construct(public string $said) {}
}
