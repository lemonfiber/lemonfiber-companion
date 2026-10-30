<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Attribute;

/** A value that is only ever compared with `hash_equals`, never with `===` or `!==` (S1). */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class ComparedInConstantTime {}
