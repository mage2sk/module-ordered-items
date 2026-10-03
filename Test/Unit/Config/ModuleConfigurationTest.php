<?php
declare(strict_types=1);

namespace Panth\OrderedItems\Test\Unit\Config;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Config\Dom;
use Panth\OrderedItems\Plugin\Export\MetadataProviderPlugin;
use Panth\OrderedItems\Ui\Component\Listing\Column\OrderItems;
use PHPUnit\Framework\TestCase;

class ModuleConfigurationTest extends TestCase
{
    private const MODULE = 'Panth_OrderedItems';

    private string $moduleDir;

    protected function setUp(): void
    {
        $path = (new ComponentRegistrar())->getPath(ComponentRegistrar::MODULE, self::MODULE);
        $this->assertNotNull($path, 'Panth_OrderedItems is not registered');
        $this->moduleDir = realpath($path);
    }

    private function load(string $relative, ?string $schemaUrn = null): \DOMXPath
    {
        $file = $this->moduleDir . '/' . $relative;
        $this->assertFileExists($file);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->load($file), $relative . ' is not well formed');
        if ($schemaUrn !== null) {
            $errors = Dom::validateDomDocument($dom, $schemaUrn);
            $this->assertSame([], array_map('strval', $errors), $relative . ' violates ' . $schemaUrn);
        }
        return new \DOMXPath($dom);
    }

    private function values(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): array
    {
        $result = [];
        foreach ($xpath->query($query, $context) as $node) {
            $result[] = trim($node->nodeValue);
        }
        return $result;
    }

    public function testRegistrationPointsToModuleRoot(): void
    {
        $this->assertSame(realpath(dirname(__DIR__, 3)), $this->moduleDir);
    }

    public function testModuleLoadsAfterCoreSalesAndCatalog(): void
    {
        $xpath = $this->load('etc/module.xml', 'urn:magento:framework:Module/etc/module.xsd');

        $this->assertSame(
            ['Panth_Core', 'Magento_Sales', 'Magento_Catalog'],
            $this->values($xpath, '//module[@name="Panth_OrderedItems"]/sequence/module/@name')
        );
    }

    public function testDefaultsEnableEveryDisplayOption(): void
    {
        $xpath = $this->load('etc/config.xml');

        $this->assertSame(['1'], $this->values($xpath, '//panth_ordered_items/general/enabled'));
        $this->assertSame(['3'], $this->values($xpath, '//panth_ordered_items/general/max_visible'));
        $this->assertSame(['10'], $this->values($xpath, '//panth_ordered_items/general/popup_threshold'));
        foreach ($xpath->query('//panth_ordered_items/display/*') as $node) {
            $this->assertSame('1', trim($node->nodeValue), $node->nodeName . ' should default to Yes');
        }
        $this->assertSame(8, $xpath->query('//panth_ordered_items/display/*')->length);
    }

    public function testAclResourceSitsUnderStoresConfiguration(): void
    {
        $xpath = $this->load('etc/acl.xml', 'urn:magento:framework:Acl/etc/acl.xsd');

        $this->assertSame(
            ['Panth Ordered Items Grid'],
            $this->values(
                $xpath,
                '//resource[@id="Magento_Backend::stores"]/resource[@id="Magento_Backend::stores_settings"]'
                . '/resource[@id="Magento_Config::config"]/resource[@id="Panth_OrderedItems::config"]/@title'
            )
        );
    }

    public function testSystemConfigurationIsGuardedAndValidated(): void
    {
        $xpath = $this->load('etc/adminhtml/system.xml');

        $this->assertSame(['Panth_OrderedItems::config'], $this->values($xpath, '//section[@id="panth_ordered_items"]/resource'));
        $this->assertSame(['panth'], $this->values($xpath, '//section[@id="panth_ordered_items"]/tab'));
        foreach (['max_visible', 'popup_threshold'] as $field) {
            $validate = $this->values($xpath, '//field[@id="' . $field . '"]/validate')[0] ?? '';
            foreach (['required-entry', 'validate-digits', 'validate-greater-than-zero'] as $rule) {
                $this->assertStringContainsString($rule, $validate, $field . ' misses ' . $rule);
            }
        }
        $dependent = $xpath->query('//group/field[@id!="enabled"]');
        $this->assertSame(10, $dependent->length);
        foreach ($dependent as $field) {
            $this->assertSame(
                ['1'],
                $this->values($xpath, './depends/field[@id="panth_ordered_items/general/enabled"]', $field),
                $field->getAttribute('id') . ' must depend on the enable flag'
            );
        }
    }

    public function testExportPluginIsDeclaredForAdminOnly(): void
    {
        $xpath = $this->load('etc/adminhtml/di.xml', 'urn:magento:framework:ObjectManager/etc/config.xsd');

        $this->assertSame(
            [MetadataProviderPlugin::class],
            $this->values($xpath, '//type[@name="Magento\Ui\Model\Export\MetadataProvider"]/plugin/@type')
        );
        $this->assertTrue(class_exists(MetadataProviderPlugin::class));
        $this->assertSame(
            0,
            $this->load('etc/di.xml')->query('//plugin')->length,
            'The export plugin must not run outside the admin area'
        );
    }

    public function testGridColumnIsHtmlNotSortableNotFilterable(): void
    {
        $xpath = $this->load('view/adminhtml/ui_component/sales_order_grid.xml');
        $base = '//columns[@name="sales_order_columns"]/column[@name="order_items"]';

        $this->assertSame([OrderItems::class], $this->values($xpath, $base . '/@class'));
        $this->assertSame([MetadataProviderPlugin::FIELD], $this->values($xpath, $base . '/@name'));
        $this->assertSame(['ui/grid/cells/html'], $this->values($xpath, $base . '/settings/bodyTmpl'));
        $this->assertSame(['false'], $this->values($xpath, $base . '/settings/sortable'));
        $this->assertSame(['false'], $this->values($xpath, $base . '/settings/filter'));
        $this->assertSame(['Order Items'], $this->values($xpath, $base . '/settings/label'));
    }

    public function testOrderGridLayoutLoadsAssetsThatExist(): void
    {
        $xpath = $this->load('view/adminhtml/layout/sales_order_index.xml');

        $this->assertSame(['Panth_OrderedItems::css/order-items-grid.css'], $this->values($xpath, '//head/css/@src'));
        $this->assertSame(['Panth_OrderedItems::js/order-items-modal.js'], $this->values($xpath, '//head/script/@src'));
        $this->assertFileExists($this->moduleDir . '/view/adminhtml/web/css/order-items-grid.css');
        $this->assertFileExists($this->moduleDir . '/view/adminhtml/web/js/order-items-modal.js');
    }

    public function testModuleRendersNothingOnTheStorefront(): void
    {
        $this->assertDirectoryDoesNotExist($this->moduleDir . '/view/frontend');
        $this->assertDirectoryDoesNotExist($this->moduleDir . '/view/base');
    }

    public function testStylesheetKeepsTextAtLeastTwelvePixelsAndAccessibleLinkColour(): void
    {
        $css = (string) file_get_contents($this->moduleDir . '/view/adminhtml/web/css/order-items-grid.css');

        preg_match_all('/font-size:\s*(\d+(?:\.\d+)?)px/', $css, $sizes);
        $this->assertNotEmpty($sizes[1]);
        foreach ($sizes[1] as $size) {
            $this->assertGreaterThanOrEqual(12, (float) $size);
        }
        $this->assertStringNotContainsString('#007bdb', $css);
        $this->assertStringContainsString('.panth-oi-modal-backdrop[hidden]', $css);
        $this->assertStringContainsString(':focus-visible', $css);
    }

    public function testScriptHasNoInlineHandlersAndManagesDialogFocus(): void
    {
        $js = (string) file_get_contents($this->moduleDir . '/view/adminhtml/web/js/order-items-modal.js');

        foreach (["'Escape'", "'Tab'", 'data-panth-oi-open', 'data-panth-oi-close', 'data-panth-oi-toggle', 'aria-expanded', '.focus()'] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
    }
}
