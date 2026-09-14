<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * Nothing, carried out of an `either()` arm.
 *
 * {@see \Modules\Kernel\Api\Handed::either()} answers with an object so that a
 * caller cannot take the happy path without writing the other one. Here both
 * arms do their work by setting the screen's state and have nothing to hand
 * back, and this is the smallest honest way to say so — an arm returning `null`
 * would not satisfy the signature, and inventing a value neither arm means
 * would be worse than an empty one.
 *
 * `Internal` because it is a detail of how this surface reads an outcome, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhatTheSharingDid {}
