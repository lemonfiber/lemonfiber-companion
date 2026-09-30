<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The steps of a thousand a decimal unit is counted in, declared once.
 *
 * A kilobit, a megabyte and a second's thousand milliseconds are the same
 * thousand, and a size and a rate stop their figure short of it for the same
 * reason: past a thousand of one unit, the next one up is what it is said in.
 * Decimal rather than binary, because the operator compares a size with a
 * number printed on a box and a speed with the one their line was sold at.
 *
 * Backed by the count, so a constant can be declared from one.
 */
enum DecimalPrefix: int
{
    case Kilo = 1_000;

    case Mega = 1_000_000;

    case Giga = 1_000_000_000;

    case Tera = 1_000_000_000_000;
}
