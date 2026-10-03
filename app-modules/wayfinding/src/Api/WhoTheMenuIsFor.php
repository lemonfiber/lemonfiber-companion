<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

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
            WhereTappingLeads::WhatTheyAreOwed => self::AMember,
            WhereTappingLeads::TheReport => self::TheOperator,
        };
    }
}
