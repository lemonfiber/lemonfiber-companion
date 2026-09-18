<?php

declare(strict_types=1);

return [
    // Where a request stands, in the household's own words rather than the
    // wire's. Only the first of these is required; the rest are here because a
    // screen that showed only what is waiting would leave somebody wondering
    // what became of the thing they asked for last week.
    'waiting-for-approval' => 'Wacht op je akkoord',
    'declined' => 'Afgewezen',
    'failed' => 'Ophalen is mislukt',
    'getting' => 'Wordt opgehaald',
    'partly-here' => 'Gedeeltelijk binnen',
    'here' => 'Binnen',
    'gone' => 'Niet meer aanwezig',
    'waiting_count' => '{0} Niets wacht op je akkoord|{1} Eén verzoek wacht op je akkoord|[2,*] :count verzoeken wachten op je akkoord',
    'asked_by' => 'Gevraagd door :who',
    'size_measured' => ':size :unit',
    'size_guessed' => 'Ongeveer :size :unit',
    'size_unknown' => 'Grootte nog onbekend',
    'megabytes' => 'MB',
    'gigabytes' => 'GB',
    'terabytes' => 'TB',
    // A waiting request is approvable and refusable from here, and the sentence
    // is part of turning one down rather than something beside it.
    'approve' => 'Goedkeuren',
    'approve_that' => ':title goedkeuren',
    'turn_down' => 'Afwijzen',
    'turning_down' => ':title afwijzen',
    'turning_down_owes' => ':who ziet wat je hier schrijft, dus zeg genoeg zodat diegene het niet hoeft te komen vragen.',
    'reason_label' => 'Waarom niet',
    'reason_placeholder' => 'Deze maand is er geen ruimte voor',
    'reason_is_shown' => 'Zichtbaar voor wie erom vroeg.',
    'turn_it_down' => 'Afwijzen',
    'never_mind' => 'Laat maar',
    'nothing_asked' => 'Niemand heeft iets gevraagd.',
    'nothing_asked_action' => 'Wat het huishouden vraagt, verschijnt hier zodra iemand iets aanvraagt.',
    'asked_for' => 'Wat het huishouden vroeg',
    'refused_because' => 'Afgewezen: :reason',
    'refused_at' => 'Afgewezen op :when',
];
