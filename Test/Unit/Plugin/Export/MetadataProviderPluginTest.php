<?php
declare(strict_types=1);

namespace Panth\OrderedItems\Test\Unit\Plugin\Export;

use Magento\Framework\Api\AttributeValue;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Ui\Component\Listing\Columns;
use Magento\Ui\Model\Export\MetadataProvider;
use Panth\OrderedItems\Plugin\Export\MetadataProviderPlugin;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class MetadataProviderPluginTest extends TestCase
{
    private const FIELDS = ['increment_id', 'status', 'order_items', 'actions_label'];

    private OrderItemCollectionFactory&MockObject $factory;
    private LoggerInterface&MockObject $logger;
    private MetadataProvider&MockObject $subject;
    private array $items = [];
    private array $filters = [];

    protected function setUp(): void
    {
        $this->factory = $this->createMock(OrderItemCollectionFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subject = $this->createMock(MetadataProvider::class);
        $this->factory->method('create')->willReturnCallback(function () {
            $collection = $this->createMock(OrderItemCollection::class);
            $collection->method('addFieldToFilter')->willReturnCallback(function ($field, $value) use ($collection) {
                $this->filters[$field] = $value;
                return $collection;
            });
            $collection->method('setOrder')->willReturnSelf();
            $collection->method('getIterator')->willReturn(new \ArrayIterator($this->items));
            return $collection;
        });
    }

    private function plugin(bool $enabled): MetadataProviderPlugin
    {
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->with('panth_ordered_items/general/enabled')->willReturn($enabled);
        return new MetadataProviderPlugin($config, $this->factory, $this->logger);
    }

    private function document(int $entityId): Document
    {
        $document = new Document();
        $document->setId((string) $entityId);
        $document->setCustomAttribute(
            'entity_id',
            new AttributeValue([AttributeValue::ATTRIBUTE_CODE => 'entity_id', AttributeValue::VALUE => $entityId])
        );
        return $document;
    }

    private function column(string $name, ?string $label, ?string $dataType = null): UiComponentInterface
    {
        $column = $this->createMock(UiComponentInterface::class);
        $column->method('getName')->willReturn($name);
        $column->method('getData')->willReturnCallback(
            fn (string $key) => $key === 'config/label' ? $label : ($key === 'config/dataType' ? $dataType : null)
        );
        return $column;
    }

    private function grid(string $name = 'sales_order_grid'): UiComponentInterface
    {
        $columns = $this->createMock(Columns::class);
        $columns->method('getChildComponents')->willReturn([
            $this->column('ids', null),
            $this->column('increment_id', 'ID'),
            $this->column('status', 'Status'),
            $this->column('order_items', 'Order Items'),
            $this->column('actions', 'Action', 'actions'),
            $this->column('billing_address', 'Billing Address'),
        ]);
        $grid = $this->createMock(UiComponentInterface::class);
        $grid->method('getName')->willReturn($name);
        $grid->method('getChildComponents')->willReturn([$this->createMock(UiComponentInterface::class), $columns]);
        return $grid;
    }

    public function testRowDataGetsPlainTextItemList(): void
    {
        $this->items = [
            new DataObject(['name' => ' Lumaflex&trade; Band ', 'sku' => '24-UG01', 'qty_ordered' => 2]),
            new DataObject(['name' => 'Fabric <Roll>', 'sku' => '', 'qty_ordered' => 1.5]),
        ];
        $row = ['000000032', 'Pending', '', ''];

        $result = $this->plugin(true)->afterGetRowData($this->subject, $row, $this->document(32), self::FIELDS, []);

        $this->assertSame(
            ['000000032', 'Pending', "Lumaflex\u{2122} Band (24-UG01) x 2; Fabric <Roll> x 1.5", ''],
            $result
        );
        $this->assertSame(32, $this->filters['order_id']);
        $this->assertSame(['null' => true], $this->filters['parent_item_id']);
    }

    public function testRowDataUsesDocumentIdWhenEntityAttributeIsMissing(): void
    {
        $this->items = [new DataObject(['name' => 'Tee', 'sku' => 'T', 'qty_ordered' => 1])];
        $document = new Document();
        $document->setId('15');

        $result = $this->plugin(true)->afterGetRowData($this->subject, ['a', 'b', '', 'c'], $document, self::FIELDS, []);

        $this->assertSame('Tee (T) x 1', $result[2]);
        $this->assertSame(15, $this->filters['order_id']);
    }

    public function testRowDataUntouchedWhenFieldNotExported(): void
    {
        $this->factory->expects($this->never())->method('create');
        $row = ['000000032', 'Pending'];

        $this->assertSame(
            $row,
            $this->plugin(true)->afterGetRowData($this->subject, $row, $this->document(32), ['increment_id', 'status'], [])
        );
    }

    public function testRowDataUntouchedWhenDisabled(): void
    {
        $this->factory->expects($this->never())->method('create');
        $row = ['000000032', 'Pending', '', ''];

        $this->assertSame(
            $row,
            $this->plugin(false)->afterGetRowData($this->subject, $row, $this->document(32), self::FIELDS, [])
        );
    }

    public function testRowDataUntouchedWithoutOrderId(): void
    {
        $this->factory->expects($this->never())->method('create');
        $row = ['', '', '', ''];

        $this->assertSame(
            $row,
            $this->plugin(true)->afterGetRowData($this->subject, $row, new Document(), self::FIELDS, [])
        );
    }

    public function testRowDataFailureIsLoggedAndRowKept(): void
    {
        $factory = $this->createMock(OrderItemCollectionFactory::class);
        $factory->method('create')->willThrowException(new \RuntimeException('gone'));
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn(true);
        $this->logger->expects($this->once())->method('warning')->with('Panth_OrderedItems: gone');
        $row = ['000000032', 'Pending', '', ''];

        $plugin = new MetadataProviderPlugin($config, $factory, $this->logger);

        $this->assertSame($row, $plugin->afterGetRowData($this->subject, $row, $this->document(32), self::FIELDS, []));
    }

    public function testHeadersAndFieldsKeepColumnWhenEnabled(): void
    {
        $headers = ['ID', 'Status', 'Order Items', 'Billing Address'];
        $fields = ['increment_id', 'status', 'order_items', 'billing_address'];
        $plugin = $this->plugin(true);

        $this->assertSame($headers, $plugin->afterGetHeaders($this->subject, $headers, $this->grid()));
        $this->assertSame($fields, $plugin->afterGetFields($this->subject, $fields, $this->grid()));
    }

    public function testHeadersAndFieldsDropColumnWhenDisabled(): void
    {
        $plugin = $this->plugin(false);

        $this->assertSame(
            ['ID', 'Status', 'Billing Address'],
            $plugin->afterGetHeaders($this->subject, ['ID', 'Status', 'Order Items', 'Billing Address'], $this->grid())
        );
        $this->assertSame(
            ['increment_id', 'status', 'billing_address'],
            $plugin->afterGetFields(
                $this->subject,
                ['increment_id', 'status', 'order_items', 'billing_address'],
                $this->grid()
            )
        );
    }

    public function testOtherGridsAreNeverChanged(): void
    {
        $headers = ['ID', 'Status', 'Order Items', 'Billing Address'];

        $this->assertSame(
            $headers,
            $this->plugin(false)->afterGetHeaders($this->subject, $headers, $this->grid('sales_invoice_grid'))
        );
    }
}
