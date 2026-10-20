<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The name this phone's key is registered under on each stack, kept so the phone knows its own
 * key in the stack's list and can name it when it proves a mint.
 *
 * It is the operator's to see on their own stack and is never written anywhere else.
 */
interface KeepingThePhoneKeysName extends ForgetsAStack
{
    /** The name this phone's key on the stack is registered under, or none. */
    public function nameOn(StackId $stack): ThePhoneKeysNameHeld;

    /** Keep the name this phone's key on the stack is registered under, in place of any before it. */
    public function keepTheName(StackId $stack, APhoneKeysName $name): Kept;

    /** Let go of the name kept for the stack, once the phone's key for it is thrown away. */
    public function letTheNameGo(StackId $stack): Kept;
}
