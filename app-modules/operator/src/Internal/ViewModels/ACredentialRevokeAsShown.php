<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What ending one grant would come to, or came to, flattened for a template.
 */
final readonly class ACredentialRevokeAsShown
{
    /**
     * @param bool          $wasEnded    whether the grant was ended, rather than only read
     * @param AGrantAsShown $grant       the grant it ends
     * @param string        $restarts    the plugin's services it restarts, listed, or empty where none
     * @param string        $unrestarted why they did not restart, or empty
     */
    public function __construct(
        public bool $wasEnded,
        public AGrantAsShown $grant,
        public string $restarts,
        public string $unrestarted,
    ) {}
}
