<?php
/**
 * Setup data patch — fires the install/upgrade reporter on every
 * setup:upgrade. The InstallReporter handles its own dedup via
 * Magento\Framework\Flag, so this patch is safe to re-apply.
 *
 * Failures are swallowed inside InstallReporter so a setup:upgrade
 * never fails because the receiver is unreachable.
 */
declare(strict_types=1);

namespace Panth\OrderedItems\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Panth\OrderedItems\Service\InstallReporter;

class ReportInstall implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly InstallReporter $reporter
    ) {
    }

    public function apply(): void
    {
        $this->reporter->reportInstall();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
