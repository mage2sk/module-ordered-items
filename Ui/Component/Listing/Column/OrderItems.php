<?php
declare(strict_types=1);

namespace Panth\OrderedItems\Ui\Component\Listing\Column;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Psr\Log\LoggerInterface;

class OrderItems extends Column
{
    private const CFG = 'panth_ordered_items/';

    private OrderItemCollectionFactory $orderItemCollectionFactory;
    private OrderCollectionFactory $orderCollectionFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private ImageHelper $imageHelper;
    private PriceCurrencyInterface $priceCurrency;
    private UrlInterface $backendUrl;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private ?string $placeholderUrl = null;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        ImageHelper $imageHelper,
        PriceCurrencyInterface $priceCurrency,
        UrlInterface $backendUrl,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->priceCurrency = $priceCurrency;
        $this->backendUrl = $backendUrl;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepare()
    {
        parent::prepare();
        if (!$this->cfg('general/enabled')) {
            $config = (array) $this->getData('config');
            $config['componentDisabled'] = true;
            $this->setData('config', $config);
        }
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        if (!$this->cfg('general/enabled')) {
            return $dataSource;
        }

        $orderIds = [];
        foreach ($dataSource['data']['items'] as $row) {
            $rowOrderId = (int) ($row['entity_id'] ?? 0);
            if ($rowOrderId > 0) {
                $orderIds[$rowOrderId] = $rowOrderId;
            }
        }
        if (empty($orderIds)) {
            return $dataSource;
        }

        try {
            $itemsByOrder = $this->loadVisibleItems(array_values($orderIds));
            $thumbs = $this->cfg('display/show_thumbnail') ? $this->loadThumbnails($itemsByOrder) : [];
            $orderData = $this->loadOrderData($itemsByOrder);
        } catch (\Throwable $e) {
            $this->logger->warning('Panth_OrderedItems: ' . $e->getMessage());
            foreach ($dataSource['data']['items'] as &$failedItem) {
                if ((int) ($failedItem['entity_id'] ?? 0) > 0) {
                    $failedItem[$this->getData('name')] = '<span class="panth-oi-empty">-</span>';
                }
            }
            unset($failedItem);
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            try {
                $orderId = (int) ($item['entity_id'] ?? 0);
                if ($orderId === 0) {
                    continue;
                }
                $item[$this->getData('name')] = $this->renderOrderItems(
                    $orderId,
                    $itemsByOrder[$orderId] ?? [],
                    $orderData[$orderId] ?? [],
                    $thumbs
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Panth_OrderedItems: ' . $e->getMessage());
                $item[$this->getData('name')] = '<span class="panth-oi-empty">-</span>';
            }
        }
        unset($item);

        return $dataSource;
    }

    private function loadVisibleItems(array $orderIds): array
    {
        $collection = $this->orderItemCollectionFactory->create();
        $collection->addFieldToFilter('order_id', ['in' => $orderIds]);
        $collection->addFieldToFilter('parent_item_id', ['null' => true]);
        $collection->setOrder('item_id', 'ASC');

        $result = [];
        foreach ($collection as $orderItem) {
            $result[(int) $orderItem->getOrderId()][] = $orderItem;
        }
        return $result;
    }

    private function loadOrderData(array $itemsByOrder): array
    {
        $ids = array_map('intval', array_keys($itemsByOrder));
        if (empty($ids)) {
            return [];
        }

        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToSelect(['entity_id', 'increment_id', 'grand_total', 'order_currency_code']);
        $collection->addFieldToFilter('entity_id', ['in' => $ids]);

        $result = [];
        foreach ($collection as $order) {
            $result[(int) $order->getId()] = [
                'increment_id' => (string) $order->getIncrementId(),
                'grand_total' => (float) $order->getGrandTotal(),
                'currency' => (string) $order->getOrderCurrencyCode(),
            ];
        }
        return $result;
    }

    private function loadThumbnails(array $itemsByOrder): array
    {
        $productIds = [];
        foreach ($itemsByOrder as $orderItems) {
            foreach ($orderItems as $orderItem) {
                $productId = (int) $orderItem->getProductId();
                if ($productId > 0) {
                    $productIds[$productId] = $productId;
                }
            }
        }
        if (empty($productIds)) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('thumbnail');
        $collection->addIdFilter(array_values($productIds));

        $urls = [];
        foreach ($collection as $product) {
            $thumbnail = $product->getThumbnail();
            if (!$thumbnail || $thumbnail === 'no_selection') {
                continue;
            }
            try {
                $urls[(int) $product->getId()] = $this->imageHelper->init($product, 'product_listing_thumbnail')
                    ->setImageFile($thumbnail)->resize(44, 44)->getUrl();
            } catch (\Throwable $e) {
                $this->logger->warning('Panth_OrderedItems: ' . $e->getMessage());
            }
        }
        return $urls;
    }

    private function renderOrderItems(int $orderId, array $items, array $orderData, array $thumbs): string
    {
        $currencyCode = (string) ($orderData['currency'] ?? '');

        if (empty($items)) {
            return '<span class="panth-oi-empty">' . $this->esc((string) __('No items')) . '</span>';
        }

        $maxVisible = max(1, (int) ($this->scopeConfig->getValue(self::CFG . 'general/max_visible') ?: 3));
        $popupThreshold = max(1, (int) ($this->scopeConfig->getValue(self::CFG . 'general/popup_threshold') ?: 10));
        $showThumb = $this->cfg('display/show_thumbnail');
        $showSku = $this->cfg('display/show_sku');
        $showPrice = $this->cfg('display/show_price');
        $showQty = $this->cfg('display/show_qty');
        $showOptions = $this->cfg('display/show_options');
        $showFulfillment = $this->cfg('display/show_fulfillment');
        $showSummary = $this->cfg('display/show_summary');
        $showProductLink = $this->cfg('display/show_product_link');

        $total = count($items);
        $usePopup = $total > $popupThreshold;
        $totalQty = 0.0;
        foreach ($items as $i) {
            $totalQty += (float) $i->getQtyOrdered();
        }

        $wrapId = 'panth-oi-wrap-' . $orderId;
        $html = '<div class="panth-oi-wrap" id="' . $wrapId . '">';

        if ($showSummary) {
            $html .= '<div class="panth-oi-summary">';
            $html .= '<span class="panth-oi-badge">' . $this->esc($this->countLabel($total)) . '</span>';
            $html .= '<span class="panth-oi-qty-total">' . $this->esc($this->unitLabel($totalQty)) . '</span>';
            $html .= '</div>';
        }

        $count = 0;
        foreach ($items as $orderItem) {
            $count++;
            if ($usePopup && $count > $maxVisible) {
                break;
            }

            $hidden = ($count > $maxVisible && !$usePopup) ? ' data-panth-oi-hidden hidden' : '';
            $name = $this->esc((string) $orderItem->getName());
                        $productUrl = $this->productUrl($orderItem);

            $html .= '<div class="panth-oi-item"' . $hidden . '>';

            if ($showThumb) {
                $img = '<img src="' . $this->esc($this->getThumbUrl($orderItem, $thumbs)) . '" alt="" '
                    . 'class="panth-oi-thumb" width="32" height="32" loading="lazy">';
                if ($showProductLink && $productUrl) {
                    $html .= '<a href="' . $this->esc($productUrl) . '" target="_blank" rel="noopener" '
                        . 'class="panth-oi-thumb-link" tabindex="-1" aria-hidden="true">' . $img . '</a>';
                } else {
                    $html .= $img;
                }
            }

            $html .= '<div class="panth-oi-info">';
            if ($showProductLink && $productUrl) {
                $html .= '<a href="' . $this->esc($productUrl) . '" target="_blank" rel="noopener" '
                    . 'class="panth-oi-name" title="' . $name . '">' . $name . '</a>';
            } else {
                $html .= '<span class="panth-oi-name">' . $name . '</span>';
            }

            if ($showSku) {
                $html .= '<div class="panth-oi-meta"><span class="panth-oi-sku">'
                    . $this->esc((string) __('SKU: %1', (string) $orderItem->getSku())) . '</span></div>';
            }

            if ($showOptions) {
                $options = $this->getItemOptions($orderItem);
                if (!empty($options)) {
                    $html .= '<div class="panth-oi-options">';
                    foreach ($options as $opt) {
                        $html .= '<span class="panth-oi-option">'
                            . $this->esc($opt['label']) . ': <strong>' . $this->esc($opt['value']) . '</strong></span>';
                    }
                    $html .= '</div>';
                }
            }

            if ($showQty || $showPrice) {
                $html .= '<div class="panth-oi-price-line">';
                if ($showQty) {
                    $html .= '<span class="panth-oi-qty-badge">'
                        . $this->esc((string) __('Qty: %1', $this->formatQty((float) $orderItem->getQtyOrdered())))
                        . '</span>';
                }
                if ($showPrice) {
                    $html .= '<span class="panth-oi-price">'
                        . $this->formatPrice((float) $orderItem->getPrice(), $currencyCode) . '</span>';
                    $html .= '<span class="panth-oi-row-total">'
                        . $this->formatPrice((float) $orderItem->getRowTotal(), $currencyCode) . '</span>';
                }
                $html .= '</div>';
            }

            if ($showFulfillment) {
                $html .= $this->renderFulfillment($orderItem);
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        if ($total > $maxVisible && !$usePopup) {
            $remaining = $total - $maxVisible;
            $moreLabel = $remaining === 1 ? (string) __('+ 1 more item') : (string) __('+ %1 more items', $remaining);
            $html .= '<div class="panth-oi-toggle">';
            $html .= '<button type="button" class="panth-oi-more" data-panth-oi-toggle="' . $wrapId . '" '
                . 'aria-expanded="false" data-label-more="' . $this->esc($moreLabel) . '" '
                . 'data-label-less="' . $this->esc((string) __('Show less')) . '">'
                . $this->esc($moreLabel) . '</button>';
            $html .= '</div>';
        }

        if ($usePopup) {
            $html .= $this->renderModal($orderId, $items, $orderData, $thumbs, $totalQty);
        }

        $html .= '</div>';
        return $html;
    }

    private function renderModal(int $orderId, array $items, array $orderData, array $thumbs, float $totalQty): string
    {
        $currencyCode = (string) ($orderData['currency'] ?? '');
        $showThumb = $this->cfg('display/show_thumbnail');
        $showSku = $this->cfg('display/show_sku');
        $showPrice = $this->cfg('display/show_price');
        $showQty = $this->cfg('display/show_qty');
        $showOptions = $this->cfg('display/show_options');
        $showFulfillment = $this->cfg('display/show_fulfillment');
        $showProductLink = $this->cfg('display/show_product_link');

        $total = count($items);
        $modalId = 'panth-oi-modal-' . $orderId;
        $titleId = $modalId . '-title';
        $selectId = $modalId . '-perpage';
        $orderTotal = $this->formatPrice((float) ($orderData['grand_total'] ?? 0), $currencyCode);

        $html = '<button type="button" class="panth-oi-more" data-panth-oi-open="' . $modalId . '" '
            . 'aria-haspopup="dialog" aria-controls="' . $modalId . '">'
            . $this->esc((string) __('View all %1 items', $total)) . '</button>';

        $html .= '<div id="' . $modalId . '" class="panth-oi-modal-backdrop" hidden>';
        $html .= '<div class="panth-oi-modal" role="dialog" aria-modal="true" aria-labelledby="' . $titleId . '" '
            . 'data-per-page="20">';

        $html .= '<div class="panth-oi-modal-header">';
        $html .= '<div class="panth-oi-modal-title">';
        $html .= '<h3 id="' . $titleId . '">' . $this->esc((string) __('Order #%1', (string) ($orderData['increment_id'] ?? '')))
            . '</h3>';
        $html .= '<span class="panth-oi-modal-stats">' . $this->esc($this->countLabel($total)) . ' &middot; '
            . $this->esc($this->unitLabel($totalQty)) . ' &middot; ' . $orderTotal . '</span>';
        $html .= '</div>';
        $html .= '<button type="button" class="panth-oi-modal-close" data-panth-oi-close '
            . 'aria-label="' . $this->esc((string) __('Close')) . '"><span aria-hidden="true">&times;</span></button>';
        $html .= '</div>';

        $html .= '<div class="panth-oi-modal-toolbar">';
        $html .= '<div class="panth-oi-modal-perpage">';
        $html .= '<label for="' . $selectId . '">' . $this->esc((string) __('Show:')) . '</label>';
        $html .= '<select id="' . $selectId . '" data-panth-oi-perpage>';
        foreach (['10' => '10', '20' => '20', '50' => '50', 'all' => (string) __('All')] as $value => $label) {
            $value = (string) $value;
            $html .= '<option value="' . $value . '"' . ($value === '20' ? ' selected' : '') . '>'
                . $this->esc($label) . '</option>';
        }
        $html .= '</select>';
        $html .= '<span>' . $this->esc((string) __('per page')) . '</span>';
        $html .= '</div>';
        $html .= '<div class="panth-oi-modal-pageinfo" data-pageinfo aria-live="polite" '
            . 'data-text-all="' . $this->esc((string) __('Showing all %1 items')) . '" '
            . 'data-text-range="' . $this->esc((string) __('Showing %1-%2 of %3 items')) . '"></div>';
        $html .= '<div class="panth-oi-modal-nav">';
        $html .= '<button type="button" class="panth-oi-nav-btn" data-prev>&lsaquo; '
            . $this->esc((string) __('Prev')) . '</button>';
        $html .= '<button type="button" class="panth-oi-nav-btn" data-next>'
            . $this->esc((string) __('Next')) . ' &rsaquo;</button>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="panth-oi-modal-body" data-total="' . $total . '">';

        $idx = 0;
        foreach ($items as $modalItem) {
            $mName = $this->esc((string) $modalItem->getName());
            $mProductUrl = $this->productUrl($modalItem);

            $html .= '<div class="panth-oi-modal-item" data-idx="' . $idx . '">';

            if ($showThumb) {
                $html .= '<img src="' . $this->esc($this->getThumbUrl($modalItem, $thumbs)) . '" alt="" '
                    . 'class="panth-oi-modal-thumb" width="44" height="44" loading="lazy">';
            }

            $html .= '<div class="panth-oi-modal-info">';
            if ($showProductLink && $mProductUrl) {
                $html .= '<a href="' . $this->esc($mProductUrl) . '" target="_blank" rel="noopener" '
                    . 'class="panth-oi-modal-name" title="' . $mName . '">' . $mName . '</a>';
            } else {
                $html .= '<span class="panth-oi-modal-name" title="' . $mName . '">' . $mName . '</span>';
            }
            $html .= '<div class="panth-oi-modal-details">';
            if ($showSku) {
                $html .= '<span class="panth-oi-modal-sku">'
                    . $this->esc((string) __('SKU: %1', (string) $modalItem->getSku())) . '</span>';
            }
            if ($showOptions) {
                foreach ($this->getItemOptions($modalItem) as $opt) {
                    $html .= '<span class="panth-oi-modal-option">'
                        . $this->esc($opt['label']) . ': ' . $this->esc($opt['value']) . '</span>';
                }
            }
            $html .= '</div></div>';

            $html .= '<div class="panth-oi-modal-right">';
            if ($showQty) {
                $html .= '<span class="panth-oi-modal-qty">'
                    . $this->esc((string) __('Qty: %1', $this->formatQty((float) $modalItem->getQtyOrdered())))
                    . '</span>';
            }
            if ($showPrice) {
                $html .= '<span class="panth-oi-modal-price">'
                    . $this->formatPrice((float) $modalItem->getPrice(), $currencyCode) . '</span>';
                $html .= '<span class="panth-oi-modal-rowtotal">'
                    . $this->formatPrice((float) $modalItem->getRowTotal(), $currencyCode) . '</span>';
            }
            if ($showFulfillment) {
                $html .= $this->renderFulfillment($modalItem);
            }
            $html .= '</div>';
            $html .= '</div>';
            $idx++;
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function renderFulfillment($orderItem): string
    {
        $labels = [];
        $invoiced = (float) $orderItem->getQtyInvoiced();
        $shipped = (float) $orderItem->getQtyShipped();
        $refunded = (float) $orderItem->getQtyRefunded();
        $canceled = (float) $orderItem->getQtyCanceled();
        if ($invoiced > 0) {
            $labels['invoiced'] = (string) __('Invoiced: %1', $this->formatQty($invoiced));
        }
        if ($shipped > 0) {
            $labels['shipped'] = (string) __('Shipped: %1', $this->formatQty($shipped));
        }
        if ($refunded > 0) {
            $labels['refunded'] = (string) __('Refunded: %1', $this->formatQty($refunded));
        }
        if ($canceled > 0) {
            $labels['canceled'] = (string) __('Canceled: %1', $this->formatQty($canceled));
        }

        $html = '<div class="panth-oi-fulfillment">';
        foreach ($labels as $code => $label) {
            $html .= '<span class="panth-oi-status panth-oi-' . $code . '">' . $this->esc($label) . '</span>';
        }
        if (empty($labels)) {
            $html .= '<span class="panth-oi-status panth-oi-pending">' . $this->esc((string) __('Pending')) . '</span>';
        }
        $html .= '</div>';
        return $html;
    }

    private function getItemOptions($orderItem): array
    {
        $options = [];
        $productOptions = $orderItem->getProductOptions();

        if (!is_array($productOptions)) {
            return [];
        }
        if (isset($productOptions['attributes_info']) && is_array($productOptions['attributes_info'])) {
            foreach ($productOptions['attributes_info'] as $attr) {
                $options[] = ['label' => (string) ($attr['label'] ?? ''), 'value' => (string) ($attr['value'] ?? '')];
            }
        }
        if (isset($productOptions['bundle_options']) && is_array($productOptions['bundle_options'])) {
            foreach ($productOptions['bundle_options'] as $bundle) {
                if (!isset($bundle['value']) || !is_array($bundle['value'])) {
                    continue;
                }
                foreach ($bundle['value'] as $val) {
                    $options[] = [
                        'label' => (string) ($bundle['label'] ?? ''),
                        'value' => (string) ($val['title'] ?? '') . ' x' . $this->formatQty((float) ($val['qty'] ?? 1)),
                    ];
                }
            }
        }

        return array_slice($options, 0, 4);
    }

    private function productUrl($orderItem): string
    {
        $productId = (int) $orderItem->getProductId();
        if ($productId <= 0) {
            return '';
        }
        return (string) $this->backendUrl->getUrl('catalog/product/edit', ['id' => $productId]);
    }

    private function getThumbUrl($orderItem, array $thumbs): string
    {
        $productId = (int) $orderItem->getProductId();
        if ($productId > 0 && isset($thumbs[$productId])) {
            return $thumbs[$productId];
        }
        if ($this->placeholderUrl === null) {
            $this->placeholderUrl = (string) $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
        }
        return $this->placeholderUrl;
    }

    private function countLabel(int $count): string
    {
        return $count === 1 ? (string) __('1 item') : (string) __('%1 items', $count);
    }

    private function unitLabel(float $qty): string
    {
        $formatted = $this->formatQty($qty);
        return $formatted === '1' ? (string) __('1 unit') : (string) __('%1 units', $formatted);
    }

    private function formatQty(float $qty): string
    {
        $formatted = rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
        return $formatted === '' || $formatted === '-0' ? '0' : $formatted;
    }

    private function formatPrice(float $amount, string $currencyCode): string
    {
        return (string) $this->priceCurrency->format(
            $amount,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $currencyCode !== '' ? $currencyCode : null
        );
    }

    private function cfg(string $path): bool
    {
        return $this->scopeConfig->isSetFlag(self::CFG . $path);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }
}
