<?php
/**
 * Standalone install + heartbeat reporter for Panth_OrderedItems.
 *
 * Every Panth module ships its own copy of this class so install
 * detection works even when sibling modules are disabled or absent.
 * No dependency on any other Panth_* class.
 *
 * Sends two kinds of notifications to https://kishansavaliya.com:
 *   - reportInstall(): fires from Setup/Patch/Data/ReportInstall on
 *     setup:upgrade. Deduped via Magento\Framework\Flag so re-running
 *     setup:upgrade on the same module version is a no-op.
 *   - reportHeartbeat(): daily, fires from Cron/SendHeartbeat. Deduped
 *     locally with a flag including today's UTC date; receiver also
 *     dedups per (site, day).
 *
 * Failures are swallowed and logged; they MUST NOT block setup:upgrade
 * or cron execution.
 */
declare(strict_types=1);

namespace Panth\OrderedItems\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class InstallReporter
{
    public const MAGENTO_MODULE = 'Panth_OrderedItems';
    public const COMPOSER_PACKAGE = 'mage2kishan/module-ordered-items';

    private const ENDPOINT_INSTALL   = 'https://kishansavaliya.com/panth/notifications/install';
    private const ENDPOINT_HEARTBEAT = 'https://kishansavaliya.com/panth/notifications/heartbeat';
    private const AUTH_USER = 'Kishan';
    private const AUTH_PASS = 'kishan123#';

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly ModuleListInterface $moduleList,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly FlagManager $flagManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function reportInstall(): void
    {
        try {
            $module = $this->moduleList->getOne(self::MAGENTO_MODULE);
            $version = (string)($module['setup_version'] ?? '');
            if ($version === '') {
                return;
            }
            $key = 'panth_' . strtolower(self::MAGENTO_MODULE);
            $flagCode = $key . '_reported_' . $version;
            if ($this->flagManager->getFlagData($flagCode)) {
                return;
            }
            $previous = $this->flagManager->getFlagData($key . '_last_version');
            $payload = $this->basePayload();
            $payload['event_type']       = $previous ? 'upgrade' : 'install';
            $payload['composer_package'] = self::COMPOSER_PACKAGE;
            $payload['magento_module']   = self::MAGENTO_MODULE;
            $payload['module_version']   = $version;
            $payload['previous_version'] = $previous ?: null;
            $this->post(self::ENDPOINT_INSTALL, $payload);
            $this->flagManager->saveFlag($flagCode, 1);
            $this->flagManager->saveFlag($key . '_last_version', $version);
        } catch (\Throwable $e) {
            $this->logger->warning('[Panth InstallReporter] ' . $e->getMessage());
        }
    }

    public function reportHeartbeat(): void
    {
        try {
            $today = gmdate('Ymd');
            $flagCode = 'panth_' . strtolower(self::MAGENTO_MODULE) . '_heartbeat_' . $today;
            if ($this->flagManager->getFlagData($flagCode)) {
                return;
            }
            $payload = $this->basePayload();
            $payload['panth_modules_active'] = $this->collectActivePanthModules();
            $this->post(self::ENDPOINT_HEARTBEAT, $payload);
            $this->flagManager->saveFlag($flagCode, 1);
        } catch (\Throwable $e) {
            $this->logger->warning('[Panth InstallReporter heartbeat] ' . $e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function basePayload(): array
    {
        $store = $this->storeManager->getDefaultStoreView();
        $baseUrl = $store ? $store->getBaseUrl(UrlInterface::URL_TYPE_WEB) : '';
        $siteName = $store ? (string)$store->getName() : '';
        $core = $this->moduleList->getOne('Panth_Core');
        $coreVersion = $core ? (string)($core['setup_version'] ?? '') : '';
        return [
            'site_url'           => $baseUrl,
            'site_name'          => $siteName,
            'magento_version'    => (string)$this->productMetadata->getVersion(),
            'magento_edition'    => (string)$this->productMetadata->getEdition(),
            'php_version'        => PHP_VERSION,
            'panth_core_present' => (bool)$core,
            'panth_core_version' => $coreVersion ?: null,
            'reported_at'        => gmdate('c'),
        ];
    }

    /** @return list<array{magento_module:string, version:string, composer_package:?string}> */
    private function collectActivePanthModules(): array
    {
        $out = [];
        foreach ($this->moduleList->getNames() as $name) {
            if (!str_starts_with($name, 'Panth_')) {
                continue;
            }
            $info = $this->moduleList->getOne($name) ?: [];
            $out[] = [
                'magento_module'   => $name,
                'version'          => (string)($info['setup_version'] ?? ''),
                'composer_package' => null,
            ];
        }
        sort($out);
        return $out;
    }

    /** @param array<string, mixed> $payload */
    private function post(string $url, array $payload): void
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \RuntimeException('payload encode failed');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(self::AUTH_USER . ':' . self::AUTH_PASS),
                'User-Agent: Panth-Module-Reporter/1.0',
            ],
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_NOSIGNAL => 1,
        ]);
        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            throw new \RuntimeException('curl error: ' . $err);
        }
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('non-2xx response: ' . $code);
        }
    }
}
