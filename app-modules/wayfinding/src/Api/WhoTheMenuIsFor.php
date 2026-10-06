<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use Modules\Design\Api\WhoseTheme;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\StackId;

/** Whose menu a screen about a stack draws, from whose session this phone holds for it. */
enum WhoTheMenuIsFor: string
{
    case Anyone = 'anyone';
    case AMember = 'a_member';
    case TheOperator = 'the_operator';

    /** Read from the same answer the list of stacks reads to decide where a tap leads. */
    public static function for(SecureStorage $storage, StackId $stack): self
    {
        return match (WhereTappingLeads::for($storage, $stack)) {
            WhereTappingLeads::TheSignIn => self::Anyone,
            WhereTappingLeads::TheirHome => self::AMember,
            WhereTappingLeads::TheReport => self::TheOperator,
        };
    }

    /**
     * The theme a screen about this stack is drawn in.
     *
     * The operator's for the operator's session, and the member's for a
     * member's and for nobody's: a screen drawn for no session is drawn as a
     * member's is.
     */
    public function theme(): WhoseTheme
    {
        return match ($this) {
            self::TheOperator => WhoseTheme::Operator,
            self::AMember, self::Anyone => WhoseTheme::Member,
        };
    }
}
