<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why what was handed over is not a join link this app opens.
 */
enum WhyAJoinLinkCannotBeUsed: string
{
    case NotAJoinLink = 'not_a_join_link';

    case WithoutAParameter = 'without_a_parameter';

    case WithAParameterItDoesNotKnow = 'with_a_parameter_it_does_not_know';

    case WithAParameterItCannotRead = 'with_a_parameter_it_cannot_read';

    case Lapsed = 'lapsed';
}
