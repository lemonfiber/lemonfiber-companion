<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ADownloadClientAsked;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\ViewModels\ADownloadClientAsShown;
use Modules\Operator\Internal\ViewModels\HowPausingWent;
use Modules\Operator\Internal\ViewModels\WhatPausingSays;
use Modules\Operator\Internal\ViewModels\WhatPausingWouldShow;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

use function sprintf;

/**
 * Pausing or resuming the downloads, as the template draws it: the rehearsal first, and what became of the yes after.
 *
 * Two families of method for two values, for {@see HowLettingItGoReads}'s
 * reason: a rehearsal and a report of what happened never share a model.
 */
final readonly class HowPausingReads
{
    /** Where the line for what a client said is kept, by what it said. */
    private const string WHAT_A_CLIENT_SAID = 'stacks.pausing.said.%s';

    /** The row that opens it, by which of the two it opens. */
    private const string ROAD = 'stacks.pausing.road.%s';

    /** The heading over the rehearsal, by which of the two was asked. */
    private const string WHAT_IT_DOES = 'stacks.pausing.what_it_does.%s';

    /** What carrying it out does, by which of the two was asked. */
    private const string EXPLAINED = 'stacks.pausing.explained.%s';

    /** The yes, by which of the two was asked. */
    private const string AGREE = 'stacks.pausing.agree.%s';

    /** While the stack asks the clients, by which of the two was asked. */
    private const string WORKING = 'stacks.pausing.working.%s';

    /** The heading over what each client read back, by which of the two was asked. */
    private const string DONE = 'stacks.pausing.done.%s';

    /** The row on the list of services that opens pausing or resuming. */
    public function road(WhatToDoWithTheDownloads $asked): string
    {
        return sprintf(self::ROAD, $asked->value);
    }

    /** The lines that differ between pausing and resuming. */
    public function lines(WhatToDoWithTheDownloads $asked): WhatPausingSays
    {
        return new WhatPausingSays(
            whatItDoes: sprintf(self::WHAT_IT_DOES, $asked->value),
            explained: sprintf(self::EXPLAINED, $asked->value),
            agree: sprintf(self::AGREE, $asked->value),
            working: sprintf(self::WORKING, $asked->value),
            done: sprintf(self::DONE, $asked->value),
        );
    }

    /** The stack's rehearsal, with what every client is doing now. */
    public function offering(WhatPausingComesTo $offer): WhatPausingWouldShow
    {
        return new WhatPausingWouldShow(
            stands: WhereTheWorkStands::answered(),
            namesWhat: true,
            clients: $this->clients($offer),
            caution: $offer->caution(),
        );
    }

    /** The stack is still reading the clients. */
    public function stillWorkingItOut(): WhatPausingWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for the rehearsal any more. */
    public function offerEnded(): WhatPausingWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::ended());
    }

    /** The rehearsal met something on the way. */
    public function notOffered(Obstacle $why): WhatPausingWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::met($why));
    }

    /** No session is held for the stack, so nothing was asked. */
    public function notAsked(): WhatPausingWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::signedOut());
    }

    /** Opened on neither pausing nor resuming, so there is nothing to rehearse. */
    public function namesNothing(): WhatPausingWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::answered(), namesWhat: false);
    }

    /** The stack is still asking the clients. */
    public function running(): HowPausingWent
    {
        return $this->following(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for the yes any more. */
    public function ended(): HowPausingWent
    {
        return $this->following(WhereTheWorkStands::ended());
    }

    /** Asking after the yes met something on the way. */
    public function met(Obstacle $why): HowPausingWent
    {
        return $this->following(WhereTheWorkStands::met($why));
    }

    /** No session is held for the stack. */
    public function signedOut(): HowPausingWent
    {
        return $this->following(WhereTheWorkStands::signedOut());
    }

    /** What every client read back after it was asked. */
    public function done(WhatPausingComesTo $report): HowPausingWent
    {
        return new HowPausingWent(
            stands: WhereTheWorkStands::answered(),
            wasRehearsed: $report->wasRehearsed() === WhetherItWasRehearsed::Rehearsed,
            clients: $this->clients($report),
            caution: $report->caution(),
        );
    }

    /** @return list<ADownloadClientAsShown> */
    private function clients(WhatPausingComesTo $report): array
    {
        $shown = [];

        foreach ($report as $client) {
            $shown[] = $this->client($client);
        }

        return $shown;
    }

    private function client(ADownloadClientAsked $client): ADownloadClientAsShown
    {
        return new ADownloadClientAsShown(
            name: $client->client(),
            wasSaid: $this->said($client->was()),
            nowSaid: $client->now() === WhatADownloadClientSaid::Unsaid ? '' : $this->said($client->now()),
            reached: $client->wasReached(),
            why: $client->whyItWasNotReached(),
        );
    }

    private function said(WhatADownloadClientSaid $said): string
    {
        return sprintf(self::WHAT_A_CLIENT_SAID, $said->value);
    }

    private function nothingOffered(
        WhereTheWorkStands $stands,
        bool $namesWhat = true,
    ): WhatPausingWouldShow {
        return new WhatPausingWouldShow(
            stands: $stands,
            namesWhat: $namesWhat,
            clients: [],
            caution: '',
        );
    }

    private function following(WhereTheWorkStands $stands): HowPausingWent
    {
        return new HowPausingWent(
            stands: $stands,
            wasRehearsed: false,
            clients: [],
            caution: '',
        );
    }
}
