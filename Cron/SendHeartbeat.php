<?php
/**
 * Daily heartbeat cron — only fires when Panth_Core is NOT enabled.
 * When Core is present and enabled it owns the site-level heartbeat;
 * this module short-circuits to avoid duplicate pings.
 */
declare(strict_types=1);

namespace Panth\OrderedItems\Cron;

use Magento\Framework\Module\Manager as ModuleManager;
use Panth\OrderedItems\Service\InstallReporter;

class SendHeartbeat
{
    public function __construct(
        private readonly InstallReporter $reporter,
        private readonly ModuleManager $moduleManager
    ) {
    }

    public function execute(): void
    {
        if ($this->moduleManager->isEnabled('Panth_Core')) {
            return; // Core handles the heartbeat for the whole site
        }
        $this->reporter->reportHeartbeat();
    }
}
