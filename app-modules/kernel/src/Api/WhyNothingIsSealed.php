<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why a value could not be sealed, or a key could not be had.
 *
 * Two answers with two remedies, which is why a boolean will not do. A phone
 * with no secure storage never keeps anything and says so; a store that would
 * not open is worth asking again, and says nothing has been lost.
 */
enum WhyNothingIsSealed
{
    /** There is no secure storage on this device, and so nowhere to keep a key. */
    case NoSecureStorage;

    /**
     * There is secure storage, and it would not give up the key or take a new one.
     *
     * Not a missing key: a missing key is made afresh. This is a store that is
     * there and would not open, so making a new key would throw away what the
     * old one sealed for a condition that may clear.
     */
    case KeyUnreadable;
}
