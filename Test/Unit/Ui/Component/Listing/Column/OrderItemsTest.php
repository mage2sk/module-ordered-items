<?php
declare(strict_types=1);

namespace Panth\OrderedItems\Test\Unit\Ui\Component\Listing\Column;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Panth\OrderedItems\Ui\Component\Listing\Column\OrderItems;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class OrderItemsTest extends TestCase
{
    private const DEFAULTS = [
        'panth_ordered_items/general/enabled' => '1',
        'panth_ordered_items/general/max_visible' => '3',
        'panth_ordered_items/general/popup_threshold' => '10',
        'panth_ordered_items/display/show_thumbnail' => '1',
        'panth_ordered_items/display/show_sku' => '1',
        'panth_ordered_items/display/show_price' => '1',
        'panth_ordered_items/display/show_qty' => '1',
        'panth_ordered_items/display/show_options' => '1',
        'panth_ordered_items/display/show_fulfillment' => '1',
        'panth_ordered_items/display/show_summary' => '1',
        'panth_ordered_items/display/show_product_link' => '1',
    ];

    private OrderItemCollectionFactory&MockObject $itemFactory;
    private OrderCollectionFactory&MockObject $orderFactory;
    private ProductCollectionFactory&MockObject $productFactory;
    private ImageHelper&MockObject $imageHelper;
    private LoggerInterface&MockObject $logger;
    private ContextInterface&MockObject $context;
    private array $items = [];
    private array $orders = [];
    private array $products = [];

    protected function setUp(): void
    {
        $this->itemFactory = $this->createMock(OrderItemCollectionFactory::class);
        $this->orderFactory = $this->createMock(OrderCollectionFactory::class);
        $this->productFactory = $this->createMock(ProductCollectionFactory::class);
        $this->imageHelper = $this->createMock(ImageHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->context->method('getProcessor')->willReturn($this->createMock(Processor::class));

        $this->itemFactory->method('create')->willReturnCallback(
            fn () => $this->collection(OrderItemCollection::class, $this->items)
        );
        $this->orderFactory->method('create')->willReturnCallback(
            fn () => $this->collection(OrderCollection::class, $this->orders)
        );
        $this->productFactory->method('create')->willReturnCallback(
            fn () => $this->collection(ProductCollection::class, $this->products)
        );

        $this->imageHelper->method('init')->willReturnCallback(function (DataObject $product) {
            $helper = $this->createMock(ImageHelper::class);
            $helper->method('setImageFile')->willReturnSelf();
            $helper->method('resize')->willReturnSelf();
            $helper->method('getUrl')->willReturn('https://example.test/thumb/' . $product->getId() . '.jpg');
            return $helper;
        });
        $this->imageHelper->method('getDefaultPlaceholderUrl')->willReturn('https://example.test/placeholder.jpg');
    }

    private function collection(string $class, array $rows): MockObject
    {
        $collection = $this->createMock($class);
        foreach (['addFieldToFilter', 'setOrder', 'addFieldToSelect', 'addAttributeToSelect', 'addIdFilter'] as $method) {
            if (method_exists($class, $method)) {
                $collection->method($method)->willReturnSelf();
            }
        }
        $collection->method('getIterator')->willReturn(new \ArrayIterator($rows));
        return $collection;
    }

    private function column(array $config = []): OrderItems
    {
        $values = array_replace(self::DEFAULTS, $config);
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn (string $path) => $values[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn (string $path) => !empty($values[$path]));

        $priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $priceCurrency->method('format')->willReturnCallback(
            fn ($amount, $includeContainer, $precision, $scope, $currency) => ($currency ?? 'XXX') . ' '
                . number_format((float) $amount, 2, '.', '')
        );

        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            fn (string $route, array $params) => 'https://example.test/admin/' . $route . '/id/' . $params['id'] . '/'
        );

        return new OrderItems(
            $this->context,
            $this->createMock(UiComponentFactory::class),
            $this->itemFactory,
            $this->orderFactory,
            $this->productFactory,
            $this->imageHelper,
            $priceCurrency,
            $url,
            $scopeConfig,
            $this->logger,
            [],
            ['name' => 'order_items']
        );
    }

    private function item(int $orderId, int $productId, string $name, array $extra = []): DataObject
    {
        return new DataObject(array_replace([
            'order_id' => $orderId,
            'product_id' => $productId,
            'name' => $name,
            'sku' => 'SKU-' . $productId,
            'qty_ordered' => 1,
            'price' => 10,
            'row_total' => 10,
            'qty_invoiced' => 0,
            'qty_shipped' => 0,
            'qty_refunded' => 0,
            'qty_canceled' => 0,
            'product_options' => [],
        ], $extra));
    }

    private function order(int $id, string $increment, float $total = 100.0): DataObject
    {
        return new DataObject([
            'id' => $id,
            'increment_id' => $increment,
            'grand_total' => $total,
            'order_currency_code' => 'USD',
        ]);
    }

    private function render(OrderItems $column, array $rows): array
    {
        return $column->prepareDataSource(['data' => ['items' => $rows]])['data']['items'];
    }

    public function testDataSourceWithoutItemsIsReturnedUnchanged(): void
    {
        $source = ['data' => ['totalRecords' => 0]];
        $this->itemFactory->expects($this->never())->method('create');

        $this->assertSame($source, $this->column()->prepareDataSource($source));
    }

    public function testDisabledColumnLeavesRowsUntouchedAndRunsNoQueries(): void
    {
        $rows = [['entity_id' => 5, 'increment_id' => '000000005']];
        $this->itemFactory->expects($this->never())->method('create');
        $this->orderFactory->expects($this->never())->method('create');

        $this->assertSame($rows, $this->render($this->column(['panth_ordered_items/general/enabled' => '0']), $rows));
    }

    public function testRowsWithoutOrderIdAreSkipped(): void
    {
        $rows = [['increment_id' => 'x'], ['entity_id' => 0]];
        $this->itemFactory->expects($this->never())->method('create');

        $this->assertSame($rows, $this->render($this->column(), $rows));
    }

    public function testSingleItemOrderRendersAllDetails(): void
    {
        $this->items = [$this->item(7, 11, 'Blue Tee', ['qty_ordered' => 2, 'price' => 24, 'row_total' => 48])];
        $this->orders = [$this->order(7, '000000007', 53)];
        $this->products = [new DataObject(['id' => 11, 'thumbnail' => '/b/l/blue.jpg'])];

        $html = $this->render($this->column(), [['entity_id' => 7]])[0]['order_items'];

        $this->assertStringContainsString('<div class="panth-oi-wrap" id="panth-oi-wrap-7">', $html);
        $this->assertStringContainsString('<span class="panth-oi-badge">1 item</span>', $html);
        $this->assertStringContainsString('<span class="panth-oi-qty-total">2 units</span>', $html);
        $this->assertStringContainsString('src="https://example.test/thumb/11.jpg"', $html);
        $this->assertStringContainsString(
            'href="https://example.test/admin/catalog/product/edit/id/11/" target="_blank" rel="noopener" class="panth-oi-name"',
            $html
        );
        $this->assertStringContainsString('>Blue Tee</a>', $html);
        $this->assertStringContainsString('SKU: SKU-11', $html);
        $this->assertStringContainsString('Qty: 2', $html);
        $this->assertStringContainsString('<span class="panth-oi-price">USD 24.00</span>', $html);
        $this->assertStringContainsString('<span class="panth-oi-row-total">USD 48.00</span>', $html);
        $this->assertStringContainsString('panth-oi-pending">Pending</span>', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('panth-oi-modal', $html);
        $this->assertStringNotContainsString('data-panth-oi-toggle', $html);
    }

    public function testPlaceholderIsUsedWhenProductHasNoImage(): void
    {
        $this->items = [$this->item(3, 20, 'No Image'), $this->item(3, 0, 'Deleted Product')];
        $this->orders = [$this->order(3, '000000003')];
        $this->products = [new DataObject(['id' => 20, 'thumbnail' => 'no_selection'])];

        $html = $this->render($this->column(), [['entity_id' => 3]])[0]['order_items'];

        $this->assertSame(2, substr_count($html, 'src="https://example.test/placeholder.jpg"'));
        $this->assertStringContainsString('<span class="panth-oi-name">Deleted Product</span>', $html);
    }

    public function testNamesAreEscapedWithoutDoubleEncodingEntities(): void
    {
        $this->items = [
            $this->item(4, 1, 'Lumaflex&trade; Band'),
            $this->item(4, 2, '<script>alert(1)</script> "Quoted"'),
        ];
        $this->orders = [$this->order(4, '000000004')];

        $html = $this->render($this->column(), [['entity_id' => 4]])[0]['order_items'];

        $this->assertStringContainsString('>Lumaflex&trade; Band</a>', $html);
        $this->assertStringNotContainsString('&amp;trade;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; &quot;Quoted&quot;', $html);
    }

    public function testInlineToggleHidesItemsAboveMaxVisible(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->items[] = $this->item(9, $i, 'Item ' . $i);
        }
        $this->orders = [$this->order(9, '000000009')];

        $html = $this->render($this->column(), [['entity_id' => 9]])[0]['order_items'];

        $this->assertSame(5, substr_count($html, 'class="panth-oi-item"'));
        $this->assertSame(2, substr_count($html, 'data-panth-oi-hidden hidden'));
        $this->assertStringContainsString('data-panth-oi-toggle="panth-oi-wrap-9"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('data-label-more="+ 2 more items"', $html);
        $this->assertStringContainsString('data-label-less="Show less"', $html);
        $this->assertStringContainsString('<button type="button" class="panth-oi-more"', $html);
    }

    public function testSingleRemainingItemUsesSingularLabel(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->items[] = $this->item(2, $i, 'Item ' . $i);
        }
        $this->orders = [$this->order(2, '000000002')];

        $html = $this->render($this->column(), [['entity_id' => 2]])[0]['order_items'];

        $this->assertStringContainsString('>+ 1 more item</button>', $html);
    }

    public function testOrdersAbovePopupThresholdRenderAccessibleDialog(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->items[] = $this->item(32, $i, 'Product ' . $i, ['qty_ordered' => 2]);
        }
        $this->orders = [$this->order(32, '000000032', 6529.78)];

        $html = $this->render($this->column(), [['entity_id' => 32]])[0]['order_items'];

        $this->assertSame(3, substr_count($html, 'class="panth-oi-item"'));
        $this->assertSame(12, substr_count($html, 'class="panth-oi-modal-item"'));
        $this->assertStringNotContainsString('data-panth-oi-toggle', $html);
        $this->assertStringContainsString(
            'data-panth-oi-open="panth-oi-modal-32" aria-haspopup="dialog" aria-controls="panth-oi-modal-32">View all 12 items</button>',
            $html
        );
        $this->assertStringContainsString('<div id="panth-oi-modal-32" class="panth-oi-modal-backdrop" hidden>', $html);
        $this->assertStringContainsString(
            'role="dialog" aria-modal="true" aria-labelledby="panth-oi-modal-32-title"',
            $html
        );
        $this->assertStringContainsString('<h3 id="panth-oi-modal-32-title">Order #000000032</h3>', $html);
        $this->assertStringContainsString('12 items &middot; 24 units &middot; USD 6529.78', $html);
        $this->assertStringContainsString('data-panth-oi-close aria-label="Close"', $html);
        $this->assertStringContainsString('<label for="panth-oi-modal-32-perpage">Show:</label>', $html);
        $this->assertStringContainsString('<option value="20" selected>20</option>', $html);
        $this->assertStringContainsString('<option value="10">10</option>', $html);
        $this->assertStringContainsString('<option value="all">All</option>', $html);
        $this->assertStringContainsString('data-text-range="Showing %1-%2 of %3 items"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('data-total="12"', $html);
    }

    public function testFulfillmentBadgesShowEveryNonZeroStateWithDecimalQuantities(): void
    {
        $this->items = [$this->item(6, 1, 'Partial', [
            'qty_ordered' => 3,
            'qty_invoiced' => 2.5,
            'qty_shipped' => 1,
            'qty_refunded' => 0.5,
            'qty_canceled' => 1,
        ])];
        $this->orders = [$this->order(6, '000000006')];

        $html = $this->render($this->column(), [['entity_id' => 6]])[0]['order_items'];

        $this->assertStringContainsString('panth-oi-invoiced">Invoiced: 2.5</span>', $html);
        $this->assertStringContainsString('panth-oi-shipped">Shipped: 1</span>', $html);
        $this->assertStringContainsString('panth-oi-refunded">Refunded: 0.5</span>', $html);
        $this->assertStringContainsString('panth-oi-canceled">Canceled: 1</span>', $html);
        $this->assertStringNotContainsString('Pending', $html);
    }

    public function testDecimalQuantitiesAreNotTruncated(): void
    {
        $this->items = [$this->item(8, 1, 'Fabric', ['qty_ordered' => 1.5])];
        $this->orders = [$this->order(8, '000000008')];

        $html = $this->render($this->column(), [['entity_id' => 8]])[0]['order_items'];

        $this->assertStringContainsString('Qty: 1.5', $html);
        $this->assertStringContainsString('1.5 units', $html);
    }

    public function testConfigurableAndBundleOptionsAreListedUpToFour(): void
    {
        $this->items = [
            $this->item(10, 1, 'Hoodie', ['product_options' => ['attributes_info' => [
                ['label' => 'Size', 'value' => 'M'],
                ['label' => 'Color', 'value' => 'Purple'],
            ]]]),
            $this->item(10, 2, 'Kit', ['product_options' => ['bundle_options' => [
                ['label' => 'Ball', 'value' => [['title' => 'Red', 'qty' => 2], ['title' => 'Blue', 'qty' => 1]]],
                ['label' => 'Bag', 'value' => [['title' => 'Large', 'qty' => 1], ['title' => 'Small', 'qty' => 1]]],
                ['label' => 'Broken'],
            ]]]),
        ];
        $this->orders = [$this->order(10, '000000010')];

        $html = $this->render($this->column(), [['entity_id' => 10]])[0]['order_items'];

        $this->assertStringContainsString('Size: <strong>M</strong>', $html);
        $this->assertStringContainsString('Color: <strong>Purple</strong>', $html);
        $this->assertStringContainsString('Ball: <strong>Red x2</strong>', $html);
        $this->assertStringContainsString('Bag: <strong>Small x1</strong>', $html);
        $this->assertSame(6, substr_count($html, 'class="panth-oi-option"'));
    }

    public function testDisplayFlagsHideOptionalParts(): void
    {
        $this->items = [$this->item(12, 1, 'Plain', ['qty_invoiced' => 1])];
        $this->orders = [$this->order(12, '000000012')];
        $this->productFactory->expects($this->never())->method('create');

        $html = $this->render($this->column([
            'panth_ordered_items/display/show_thumbnail' => '0',
            'panth_ordered_items/display/show_sku' => '0',
            'panth_ordered_items/display/show_price' => '0',
            'panth_ordered_items/display/show_qty' => '0',
            'panth_ordered_items/display/show_fulfillment' => '0',
            'panth_ordered_items/display/show_summary' => '0',
            'panth_ordered_items/display/show_product_link' => '0',
        ]), [['entity_id' => 12]])[0]['order_items'];

        $this->assertStringContainsString('<span class="panth-oi-name">Plain</span>', $html);
        foreach (['<img', 'SKU:', 'panth-oi-price', 'Qty:', 'Invoiced', 'panth-oi-summary', '<a '] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
    }

    public function testOrderWithoutVisibleItemsShowsEmptyState(): void
    {
        $this->items = [$this->item(1, 1, 'Other Order')];
        $this->orders = [$this->order(1, '000000001')];

        $rows = $this->render($this->column(), [['entity_id' => 1], ['entity_id' => 99]]);

        $this->assertSame('<span class="panth-oi-empty">No items</span>', $rows[1]['order_items']);
        $this->assertStringContainsString('Other Order', $rows[0]['order_items']);
    }

    public function testInvalidLimitsFallBackToDefaults(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->items[] = $this->item(14, $i, 'Item ' . $i);
        }
        $this->orders = [$this->order(14, '000000014')];

        $html = $this->render($this->column([
            'panth_ordered_items/general/max_visible' => '0',
            'panth_ordered_items/general/popup_threshold' => '',
        ]), [['entity_id' => 14]])[0]['order_items'];

        $this->assertSame(1, substr_count($html, 'data-panth-oi-hidden'));
        $this->assertStringNotContainsString('panth-oi-modal', $html);
    }

    public function testLoadFailureFallsBackToDashAndLogs(): void
    {
        $this->itemFactory = $this->createMock(OrderItemCollectionFactory::class);
        $this->itemFactory->method('create')->willThrowException(new \RuntimeException('db down'));
        $this->logger->expects($this->once())->method('warning')->with('Panth_OrderedItems: db down');

        $rows = $this->render($this->column(), [['entity_id' => 1], ['increment_id' => 'x']]);

        $this->assertSame('<span class="panth-oi-empty">-</span>', $rows[0]['order_items']);
        $this->assertArrayNotHasKey('order_items', $rows[1]);
    }

    public function testPrepareDisablesComponentWhenFeatureIsOff(): void
    {
        $column = $this->column(['panth_ordered_items/general/enabled' => '0']);
        $column->prepare();

        $this->assertTrue($column->getData('config')['componentDisabled']);
    }

    public function testPrepareKeepsComponentWhenFeatureIsOn(): void
    {
        $column = $this->column();
        $column->prepare();

        $this->assertArrayNotHasKey('componentDisabled', (array) $column->getData('config'));
    }
}
