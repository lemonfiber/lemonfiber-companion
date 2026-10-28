<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

use Throwable;

/**
 * What was handed over as a join link, refused, saying why.
 */
final class JoinLinkCannotBeUsed extends InvalidArgumentException
{
    private function __construct(private readonly WhyAJoinLinkCannotBeUsed $why, string $message, ?Throwable $previous)
    {
        parent::__construct($message, previous: $previous);
    }

    /** Refused for this reason. */
    public static function because(WhyAJoinLinkCannotBeUsed $why): self
    {
        return new self($why, sprintf('The join link was refused: %s.', $why->value), null);
    }

    /** Refused for this reason, over the parameter to blame, named and never carrying what it said. */
    public static function over(WhyAJoinLinkCannotBeUsed $why, string $parameter): self
    {
        return new self($why, sprintf('The join link was refused: %s %s.', $why->value, $parameter), null);
    }

    /** Refused because a parameter would not read as what it names, for the reason its own type gave. */
    public static function unreadable(InvalidArgumentException $why): self
    {
        return new self(WhyAJoinLinkCannotBeUsed::WithAParameterItCannotRead, 'The join link was refused: a parameter would not read.', $why);
    }

    /** Why it was refused. */
    public function why(): WhyAJoinLinkCannotBeUsed
    {
        return $this->why;
    }
}
