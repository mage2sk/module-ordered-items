<?php
declare(strict_types=1);

namespace Panth\OrderedItems\Plugin\Export;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Ui\Component\Listing\Columns;
use Magento\Ui\Model\Export\MetadataProvider;
use Psr\Log\LoggerInterface;

class MetadataProviderPlugin
{
    public const GRID = 'sales_order_grid';
    public const FIELD = 'order_items';
    private const XML_ENABLED = 'panth_ordered_items/general/enabled';

    private ScopeConfigInterface $scopeConfig;
    private OrderItemCollectionFactory $orderItemCollectionFactory;
    private LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->logger = $logger;
    }

    public function afterGetHeaders(MetadataProvider $subject, array $result, UiComponentInterface $component): array
    {
        return $this->removeColumn($result, $component);
    }

    public function afterGetFields(MetadataProvider $subject, array $result, UiComponentInterface $component): array
    {
        return $this->removeColumn($result, $component);
    }

    public function afterGetRowData(
        MetadataProvider $subject,
        array $result,
        DocumentInterface $document,
        $fields,
        $options
    ): array {
        if (!is_array($fields) || !$this->isEnabled()) {
            return $result;
        }
        $index = array_search(self::FIELD, array_values($fields), true);
        if ($index === false || !array_key_exists($index, $result)) {
            return $result;
        }
        $orderId = $this->orderId($document);
        if ($orderId <= 0) {
            return $result;
        }
        try {
            $result[$index] = $this->buildText($orderId);
        } catch (\Throwable $e) {
            $this->logger->warning('Panth_OrderedItems: ' . $e->getMessage());
        }
        return $result;
    }

    private function removeColumn(array $result, UiComponentInterface $component): array
    {
        if ($component->getName() !== self::GRID || $this->isEnabled()) {
            return $result;
        }
        $index = $this->columnIndex($component);
        if ($index === null || !array_key_exists($index, $result)) {
            return $result;
        }
        unset($result[$index]);
        return array_values($result);
    }

    private function columnIndex(UiComponentInterface $component): ?int
    {
        foreach ($component->getChildComponents() as $child) {
            if (!$child instanceof Columns) {
                continue;
            }
            $index = 0;
            foreach ($child->getChildComponents() as $column) {
                if (!$column->getData('config/label') || $column->getData('config/dataType') === 'actions') {
                    continue;
                }
                if ($column->getName() === null) {
                    continue;
                }
                if ((string) $column->getName() === self::FIELD) {
                    return $index;
                }
                $index++;
            }
        }
        return null;
    }

    private function orderId(DocumentInterface $document): int
    {
        $attribute = $document->getCustomAttribute('entity_id');
        if ($attribute !== null && (int) $attribute->getValue() > 0) {
            return (int) $attribute->getValue();
        }
        return (int) $document->getId();
    }

    private function buildText(int $orderId): string
    {
        $collection = $this->orderItemCollectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId);
        $collection->addFieldToFilter('parent_item_id', ['null' => true]);
        $collection->setOrder('item_id', 'ASC');

        $parts = [];
        foreach ($collection as $item) {
            $name = trim(html_entity_decode((string) $item->getName(), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $sku = trim((string) $item->getSku());
            $qty = rtrim(rtrim(number_format((float) $item->getQtyOrdered(), 4, '.', ''), '0'), '.');
            $parts[] = $name . ($sku !== '' ? ' (' . $sku . ')' : '') . ' x ' . ($qty === '' ? '0' : $qty);
        }
        return implode('; ', $parts);
    }

    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED);
    }
}
