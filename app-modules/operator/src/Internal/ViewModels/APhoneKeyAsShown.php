<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** One phone key, as the card that draws it. */
final readonly class APhoneKeyAsShown
{
    /**
     * @param string     $name         the name it was registered under, which revoking it names
     * @param string     $livesSaid    the catalogue key for where it lives
     * @param AgoAsShown $registered   when it was registered
     * @param AgoAsShown $used         when it last proved a mint, unsaid where it never has
     * @param AgoAsShown $revoked      when it was revoked, unsaid where it stands
     * @param bool       $mayBeRevoked whether it still stands, so revoking it is offered
     * @param bool       $isThisPhone  whether it is the key this phone holds for the stack
     */
    public function __construct(
        public string $name,
        public string $livesSaid,
        public AgoAsShown $registered,
        public AgoAsShown $used,
        public AgoAsShown $revoked,
        public bool $mayBeRevoked,
        public bool $isThisPhone,
    ) {}
}
