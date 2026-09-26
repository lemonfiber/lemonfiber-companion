<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\AnUpgradeDescribed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\UpgradingTheLibrary;
use Modules\Kernel\Api\WhatTheUpgradeCameTo;
use Modules\Kernel\Api\WhatToDoAboutQuality;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * {@see UpgradingTheLibrary}, answered by asking the stack.
 *
 * {@see Graders}' shape one action along: `quality-upgrade` unconfirmed only
 * says what it would come to, and confirmed asks each service to search again.
 * `confirm` is the one argument it takes, and the only difference between the
 * two methods.
 */
final readonly class Upgraders implements UpgradingTheLibrary
{
    public function __construct(private Clients $clients) {}

    public function whatItWouldComeTo(Stack $stack, Session $session): WhatTheUpgradeCameTo
    {
        return $this->asking($stack, $session, confirmed: false);
    }

    public function upgrade(Stack $stack, Session $session, AnUpgradeDescribed $agreed): WhatTheUpgradeCameTo
    {
        return $this->asking($stack, $session, confirmed: true);
    }

    /** The one call both methods make, the yes named at both call sites. */
    private function asking(Stack $stack, Session $session, bool $confirmed): WhatTheUpgradeCameTo
    {
        $client = $this->clients->client($stack, $session);

        try {
            $envelope = $client->act(
                Api::action(WhatToDoAboutQuality::Upgrade->asked()),
                [UpdateField::Confirm->value => $confirmed],
            );

            return WhatTheUpgradeCameTo::said(WhatAnUpgradeComesTo::in($envelope));
        } catch (RequestFailed $why) {
            return WhatTheUpgradeCameTo::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|QualityIsUnreadable|QualitySaysNothing) {
            return WhatTheUpgradeCameTo::met(Obstacle::StackDidNotAnswer);
        }
    }
}
