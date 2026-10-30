<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A link that opens an app at this server, with the link as a code. */
final readonly class ALinkWithItsCode
{
    /**
     * @param string           $link    the link
     * @param list<list<bool>> $squares the link as a code, rows of squares dark where true, or none where it could not be drawn
     */
    public function __construct(public string $link, public array $squares) {}
}
