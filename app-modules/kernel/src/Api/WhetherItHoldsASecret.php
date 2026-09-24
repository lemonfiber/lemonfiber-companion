<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether something the stack keeps holds a credential.
 *
 * What decides how carefully a copy of it has to be treated, which is why it
 * is said beside the thing rather than left to be inferred from its name. The
 * value is never on the wire and never on a screen: this says a secret is
 * there, and nothing here could say what it is.
 */
enum WhetherItHoldsASecret: string
{
    /** It holds a credential. */
    case Secret = 'secret';

    /** It holds nothing that would let somebody in. */
    case Plain = 'plain';

    /** Read off the wire's boolean, which is the only place one is taken. */
    public static function said(bool $secret): self
    {
        return $secret ? self::Secret : self::Plain;
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.keeps.secret.%s', $this->value);
    }
}
