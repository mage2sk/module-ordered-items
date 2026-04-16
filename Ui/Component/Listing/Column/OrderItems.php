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
        $popupThreshold = (int) ($this->scopeConfig->getValue(self::CFG . 'general/popup_threshold') ?: 10);
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
        $totalQty = 0;
        foreach ($items as $i) {
            $totalQty += (int) $i->getQtyOrdered();
        }

        $inlineLimit = $usePopup ? $maxVisible : $total;
        $wrapId = 'panth-oi-wrap-' . $orderId;
        $html = '<div class="panth-oi-wrap" id="' . $wrapId . '" onclick="event.stopPropagation();">';

        if ($showSummary) {
            $html .= '<div class="panth-oi-summary">';
            $html .= '<span class="panth-oi-badge">' . $total . ' item' . ($total > 1 ? 's' : '') . '</span>';
            $html .= '<span class="panth-oi-qty-total">' . $totalQty . ' unit' . ($totalQty > 1 ? 's' : '') . '</span>';
            $html .= '</div>';
        }

        $count = 0;
        foreach ($items as $orderItem) {
            $count++;
            if ($count > $inlineLimit) {
                break;
            }

            $isHidden = $count > $maxVisible && !$usePopup;
            $hidden = $isHidden ? ' data-panth-oi-hidden style="display:none;"' : '';
            if ($usePopup && $count > $maxVisible) {
                break; // Don't render more than maxVisible inline for popup mode
            }

            $name = $this->esc((string) $orderItem->getName());
            $sku = $this->esc((string) $orderItem->getSku());
            $qty = (int) $orderItem->getQtyOrdered();
            $productId = $orderItem->getProductId();
            $productUrl = $productId ? $this->backendUrl->getUrl('catalog/product/edit', ['id' => $productId]) : '';

            $html .= '<div class="panth-oi-item"' . $hidden . '>';

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
            if ($showProductLink && $productUrl) {
                $html .= '<a href="' . $this->esc($productUrl) . '" target="_blank" class="panth-oi-name" title="' . $name . '">' . $name . '</a>';
            } else {
                $html .= '<span class="panth-oi-name">' . $name . '</span>';
            }

            if ($showSku) {
                $html .= '<div class="panth-oi-meta"><span class="panth-oi-sku">SKU: ' . $sku . '</span></div>';
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
                    $html .= '<span class="panth-oi-qty-badge">Qty: ' . $qty . '</span>';
                }
                if ($showPrice) {
                    $price = $this->priceCurrency->format((float) $orderItem->getPrice(), false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode);
                    $rowTotal = $this->priceCurrency->format((float) $orderItem->getRowTotal(), false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode);
                    $html .= '<span class="panth-oi-price">' . $price . '</span>';
                    $html .= '<span class="panth-oi-row-total">' . $rowTotal . '</span>';
                }
                $html .= '</div>';
            }

            if ($showFulfillment) {
                $html .= $this->renderFulfillment($orderItem);
            }

            $html .= '</div>'; // info
            $html .= '</div>'; // item
        }

        // Show more/less toggle (for non-popup mode: 4 to popupThreshold items)
        if ($total > $maxVisible && !$usePopup) {
            $remaining = $total - $maxVisible;
            $html .= '<div class="panth-oi-toggle">';
            $html .= '<a href="#" class="panth-oi-more" '
                . 'onclick="event.stopPropagation();var w=document.getElementById(\'' . $wrapId . '\');w.querySelectorAll(\'[data-panth-oi-hidden]\').forEach(function(e){e.style.display=\'flex\';});this.style.display=\'none\';this.nextElementSibling.style.display=\'inline\';return false;">'
                . '+ ' . $remaining . ' more item' . ($remaining > 1 ? 's' : '') . '</a>';
            $html .= '<a href="#" class="panth-oi-less" style="display:none;" '
                . 'onclick="event.stopPropagation();var w=document.getElementById(\'' . $wrapId . '\');w.querySelectorAll(\'[data-panth-oi-hidden]\').forEach(function(e){e.style.display=\'none\';});this.style.display=\'none\';this.previousElementSibling.style.display=\'inline\';return false;">'
                . 'Show less</a>';
            $html .= '</div>';
        }

        if ($usePopup) {
            $modalId = 'panth-oi-modal-' . $orderId;
            $incrementId = $this->esc((string) $order->getIncrementId());
            $orderTotal = $this->priceCurrency->format((float) $order->getGrandTotal(), false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode);

            $html .= '<a href="#" class="panth-oi-more" '
                . 'onclick="event.stopPropagation();var m=document.getElementById(\'' . $modalId . '\');m.style.display=\'flex\';panthOiPaginate(\'' . $modalId . '\',1);return false;">'
                . 'View all ' . $total . ' items</a>';

            // Modal
            $html .= '<div id="' . $modalId . '" class="panth-oi-modal-backdrop" onclick="if(event.target===this){this.style.display=\'none\';}">';
            $html .= '<div class="panth-oi-modal">';

            // Header
            $html .= '<div class="panth-oi-modal-header">';
            $html .= '<div class="panth-oi-modal-title">';
            $html .= '<h3>Order #' . $incrementId . '</h3>';
            $html .= '<span class="panth-oi-modal-stats">' . $total . ' Items &middot; ' . $totalQty . ' Units &middot; ' . $orderTotal . '</span>';
            $html .= '</div>';
            $html .= '<button type="button" class="panth-oi-modal-close" onclick="event.stopPropagation();this.closest(\'.panth-oi-modal-backdrop\').style.display=\'none\';">&times;</button>';
            $html .= '</div>';

            // Toolbar: page size + pagination info
            $html .= '<div class="panth-oi-modal-toolbar">';
            $html .= '<div class="panth-oi-modal-perpage">';
            $html .= '<label>Show:</label>';
            $html .= '<select onchange="this.closest(\'.panth-oi-modal\').dataset.perPage=this.value;panthOiPaginate(\'' . $modalId . '\',1);">';
            $html .= '<option value="10">10</option>';
            $html .= '<option value="20" selected>20</option>';
            $html .= '<option value="50">50</option>';
            $html .= '<option value="all">All</option>';
            $html .= '</select>';
            $html .= '<span>per page</span>';
            $html .= '</div>';
            $html .= '<div class="panth-oi-modal-pageinfo" data-pageinfo></div>';
            $html .= '<div class="panth-oi-modal-nav">';
            $html .= '<button type="button" class="panth-oi-nav-btn" data-prev onclick="event.stopPropagation();panthOiPaginate(\'' . $modalId . '\',\'prev\');">&lsaquo; Prev</button>';
            $html .= '<button type="button" class="panth-oi-nav-btn" data-next onclick="event.stopPropagation();panthOiPaginate(\'' . $modalId . '\',\'next\');">Next &rsaquo;</button>';
            $html .= '</div>';
            $html .= '</div>';

            // Body with all items (pagination controlled via JS)
            $html .= '<div class="panth-oi-modal-body" data-total="' . $total . '">';

            $idx = 0;
            foreach ($items as $modalItem) {
                $mName = $this->esc((string) $modalItem->getName());
                $mSku = $this->esc((string) $modalItem->getSku());
                $mQty = (int) $modalItem->getQtyOrdered();
                $mProductId = $modalItem->getProductId();
                $mProductUrl = $mProductId ? $this->backendUrl->getUrl('catalog/product/edit', ['id' => $mProductId]) : '';

                $html .= '<div class="panth-oi-modal-item" data-idx="' . $idx . '">';

                if ($showThumb) {
                    $thumbUrl = $this->getThumbUrl($modalItem);
                    $html .= '<img src="' . $this->esc($thumbUrl) . '" alt="' . $mName . '" class="panth-oi-modal-thumb" loading="lazy">';
                }

                $html .= '<div class="panth-oi-modal-info">';
                if ($showProductLink && $mProductUrl) {
                    $html .= '<a href="' . $this->esc($mProductUrl) . '" target="_blank" class="panth-oi-modal-name">' . $mName . '</a>';
                } else {
                    $html .= '<span class="panth-oi-modal-name">' . $mName . '</span>';
                }
                $html .= '<div class="panth-oi-modal-details">';
                if ($showSku) {
                    $html .= '<span class="panth-oi-modal-sku">SKU: ' . $mSku . '</span>';
                }
                if ($showOptions) {
                    $options = $this->getItemOptions($modalItem);
                    foreach ($options as $opt) {
                        $html .= '<span class="panth-oi-modal-option">' . $this->esc($opt['label']) . ': ' . $this->esc($opt['value']) . '</span>';
                    }
                }
                $html .= '</div></div>';

                $html .= '<div class="panth-oi-modal-right">';
                if ($showQty) {
                    $html .= '<span class="panth-oi-modal-qty">&times;' . $mQty . '</span>';
                }
                if ($showPrice) {
                    $price = $this->priceCurrency->format((float) $modalItem->getPrice(), false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode);
                    $rowTotal = $this->priceCurrency->format((float) $modalItem->getRowTotal(), false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode);
                    $html .= '<span class="panth-oi-modal-price">' . $price . '</span>';
                    $html .= '<span class="panth-oi-modal-rowtotal">' . $rowTotal . '</span>';
                }
                if ($showFulfillment) {
                    $html .= $this->renderFulfillment($modalItem);
                }
                $html .= '</div>';
                $html .= '</div>';
                $idx++;
            }

            $html .= '</div>'; // modal-body
            $html .= '</div>'; // modal
            $html .= '</div>'; // backdrop
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
