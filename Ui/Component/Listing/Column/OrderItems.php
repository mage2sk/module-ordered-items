<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 *
 * Renders ordered items with thumbnails, product details, options,
 * pricing, and fulfillment status inside the admin Sales Order Grid.
 * All display elements are admin-configurable via system.xml.
 */
declare(strict_types=1);

namespace Panth\OrderedItems\Ui\Component\Listing\Column;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Psr\Log\LoggerInterface;

class OrderItems extends Column
{
    private const CFG = 'panth_ordered_items/';

    private OrderRepositoryInterface $orderRepository;
    private ImageHelper $imageHelper;
    private PriceCurrencyInterface $priceCurrency;
    private UrlInterface $backendUrl;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        ImageHelper $imageHelper,
        PriceCurrencyInterface $priceCurrency,
        UrlInterface $backendUrl,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->imageHelper = $imageHelper;
        $this->priceCurrency = $priceCurrency;
        $this->backendUrl = $backendUrl;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        if (!$this->cfg('general/enabled')) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            try {
                $orderId = (int) ($item['entity_id'] ?? 0);
                if ($orderId === 0) {
                    continue;
                }
                $item[$this->getData('name')] = $this->renderOrderItems($orderId);
            } catch (\Throwable $e) {
                $this->logger->debug('Panth_OrderedItems: ' . $e->getMessage());
                $item[$this->getData('name')] = '<span style="color:#999">—</span>';
            }
        }

        return $dataSource;
    }

    private function renderOrderItems(int $orderId): string
    {
        $order = $this->orderRepository->get($orderId);
        $items = $order->getAllVisibleItems();
        $currencyCode = (string) $order->getOrderCurrencyCode();

        if (empty($items)) {
            return '<span style="color:#999">No items</span>';
        }

        $maxVisible = (int) ($this->scopeConfig->getValue(self::CFG . 'general/max_visible') ?: 3);
        $showThumb = $this->cfg('display/show_thumbnail');
        $showSku = $this->cfg('display/show_sku');
        $showPrice = $this->cfg('display/show_price');
        $showQty = $this->cfg('display/show_qty');
        $showOptions = $this->cfg('display/show_options');
        $showFulfillment = $this->cfg('display/show_fulfillment');
        $showSummary = $this->cfg('display/show_summary');
        $showProductLink = $this->cfg('display/show_product_link');

        $total = count($items);
        $totalQty = 0;
        foreach ($items as $i) {
            $totalQty += (int) $i->getQtyOrdered();
        }

        $html = '<div class="panth-oi-wrap" onclick="event.stopPropagation();">';

        // Summary
        if ($showSummary) {
            $html .= '<div class="panth-oi-summary">';
            $html .= '<span class="panth-oi-badge">' . $total . ' item' . ($total > 1 ? 's' : '') . '</span>';
            $html .= '<span class="panth-oi-qty-total">' . $totalQty . ' unit' . ($totalQty > 1 ? 's' : '') . '</span>';
            $html .= '</div>';
        }

        $count = 0;
        foreach ($items as $orderItem) {
            $count++;
            $hidden = $count > $maxVisible ? ' data-panth-oi-hidden style="display:none;"' : '';

            $name = $this->esc((string) $orderItem->getName());
            $sku = $this->esc((string) $orderItem->getSku());
            $qty = (int) $orderItem->getQtyOrdered();
            $productId = $orderItem->getProductId();
            $productUrl = $productId ? $this->backendUrl->getUrl('catalog/product/edit', ['id' => $productId]) : '';

            $html .= '<div class="panth-oi-item"' . $hidden . '>';

            // Thumbnail
            if ($showThumb) {
                $thumbUrl = $this->getThumbUrl($orderItem);
                if ($showProductLink && $productUrl) {
                    $html .= '<a href="' . $this->esc($productUrl) . '" target="_blank" class="panth-oi-thumb-link" title="Edit product">';
                }
                $html .= '<img src="' . $this->esc($thumbUrl) . '" alt="' . $name . '" class="panth-oi-thumb" width="44" height="44" loading="lazy">';
                if ($showProductLink && $productUrl) {
                    $html .= '</a>';
                }
            }

            $html .= '<div class="panth-oi-info">';

            // Name
            if ($showProductLink && $productUrl) {
                $html .= '<a href="' . $this->esc($productUrl) . '" target="_blank" class="panth-oi-name" title="' . $name . '">' . $name . '</a>';
            } else {
                $html .= '<span class="panth-oi-name">' . $name . '</span>';
            }

            // SKU
            if ($showSku) {
                $html .= '<div class="panth-oi-meta"><span class="panth-oi-sku">SKU: ' . $sku . '</span></div>';
            }

            // Options
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

            // Price + Qty
            if ($showQty || $showPrice) {
                $html .= '<div class="panth-oi-price-line">';
                if ($showQty) {
                    $html .= '<span class="panth-oi-qty-badge">Qty: ' . $qty . '</span>';
                }
                if ($showPrice) {
                    $price = $this->priceCurrency->format(
                        (float) $orderItem->getPrice(),
                        false,
                        PriceCurrencyInterface::DEFAULT_PRECISION,
                        null,
                        $currencyCode
                    );
                    $rowTotal = $this->priceCurrency->format(
                        (float) $orderItem->getRowTotal(),
                        false,
                        PriceCurrencyInterface::DEFAULT_PRECISION,
                        null,
                        $currencyCode
                    );
                    $html .= '<span class="panth-oi-price">' . $price . '</span>';
                    $html .= '<span class="panth-oi-row-total">' . $rowTotal . '</span>';
                }
                $html .= '</div>';
            }

            // Fulfillment
            if ($showFulfillment) {
                $html .= $this->renderFulfillment($orderItem);
            }

            $html .= '</div>'; // info
            $html .= '</div>'; // item
        }

        if ($total > $maxVisible) {
            $remaining = $total - $maxVisible;
            $html .= '<a href="#" class="panth-oi-more" '
                . 'onclick="event.stopPropagation();this.parentNode.querySelectorAll(\'[data-panth-oi-hidden]\').forEach(function(e){e.style.display=\'flex\';});this.style.display=\'none\';return false;">'
                . '+ ' . $remaining . ' more item' . ($remaining > 1 ? 's' : '') . '</a>';
        }

        $html .= '</div>';
        return $html;
    }

    private function renderFulfillment($orderItem): string
    {
        $inv = (int) $orderItem->getQtyInvoiced();
        $ship = (int) $orderItem->getQtyShipped();
        $ref = (int) $orderItem->getQtyRefunded();
        $can = (int) $orderItem->getQtyCanceled();

        $html = '<div class="panth-oi-fulfillment">';
        if ($inv > 0) {
            $html .= '<span class="panth-oi-status panth-oi-invoiced">Invoiced: ' . $inv . '</span>';
        }
        if ($ship > 0) {
            $html .= '<span class="panth-oi-status panth-oi-shipped">Shipped: ' . $ship . '</span>';
        }
        if ($ref > 0) {
            $html .= '<span class="panth-oi-status panth-oi-refunded">Refunded: ' . $ref . '</span>';
        }
        if ($can > 0) {
            $html .= '<span class="panth-oi-status panth-oi-canceled">Canceled: ' . $can . '</span>';
        }
        if ($inv === 0 && $ship === 0 && $ref === 0 && $can === 0) {
            $html .= '<span class="panth-oi-status panth-oi-pending">Pending</span>';
        }
        $html .= '</div>';
        return $html;
    }

    private function getItemOptions($orderItem): array
    {
        $options = [];
        $productOptions = $orderItem->getProductOptions();

        if (isset($productOptions['attributes_info'])) {
            foreach ($productOptions['attributes_info'] as $attr) {
                $options[] = ['label' => (string) ($attr['label'] ?? ''), 'value' => (string) ($attr['value'] ?? '')];
            }
        }
        if (isset($productOptions['bundle_options'])) {
            foreach ($productOptions['bundle_options'] as $bundle) {
                foreach ($bundle['value'] as $val) {
                    $options[] = ['label' => (string) ($bundle['label'] ?? ''), 'value' => (string) ($val['title'] ?? '') . ' x' . (int) ($val['qty'] ?? 1)];
                }
            }
        }

        return array_slice($options, 0, 4);
    }

    private function getThumbUrl($orderItem): string
    {
        try {
            $product = $orderItem->getProduct();
            if ($product && $product->getId()) {
                $thumbnail = $product->getThumbnail();
                if ($thumbnail && $thumbnail !== 'no_selection') {
                    return $this->imageHelper->init($product, 'product_listing_thumbnail')
                        ->setImageFile($thumbnail)->resize(44, 44)->getUrl();
                }
            }
        } catch (\Throwable $e) {
            // placeholder
        }
        return $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
    }

    private function cfg(string $path): bool
    {
        return $this->scopeConfig->isSetFlag(self::CFG . $path);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
