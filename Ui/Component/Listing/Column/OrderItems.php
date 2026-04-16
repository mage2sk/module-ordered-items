<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 *
 * Renders ordered items (thumbnail + name + SKU + qty) inside the
 * admin Sales → Orders grid as a rich HTML column.
 */
declare(strict_types=1);

namespace Panth\OrderedItems\Ui\Component\Listing\Column;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Psr\Log\LoggerInterface;

class OrderItems extends Column
{
    /**
     * Maximum items shown before "show more" link
     */
    private const MAX_VISIBLE = 3;

    private OrderRepositoryInterface $orderRepository;
    private ImageHelper $imageHelper;
    private LoggerInterface $logger;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        ImageHelper $imageHelper,
        LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->imageHelper = $imageHelper;
        $this->logger = $logger;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare data source — inject rendered HTML for each order row.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
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
                $item[$this->getData('name')] = '<span style="color:#999;">—</span>';
            }
        }

        return $dataSource;
    }

    /**
     * Build the HTML for a single order's items.
     */
    private function renderOrderItems(int $orderId): string
    {
        $order = $this->orderRepository->get($orderId);
        $items = $order->getAllVisibleItems();

        if (empty($items)) {
            return '<span style="color:#999;">No items</span>';
        }

        $html = '<div class="panth-order-items">';
        $count = 0;
        $total = count($items);

        foreach ($items as $orderItem) {
            $count++;
            $hidden = $count > self::MAX_VISIBLE ? ' style="display:none;" data-panth-hidden="1"' : '';

            $name = $this->escapeHtml((string) $orderItem->getName());
            $sku = $this->escapeHtml((string) $orderItem->getSku());
            $qty = (int) $orderItem->getQtyOrdered();
            $thumbUrl = $this->getProductThumbnailUrl($orderItem);

            $html .= '<div class="panth-order-item"' . $hidden . '>';
            $html .= '<img src="' . $this->escapeHtml($thumbUrl) . '" '
                    . 'alt="' . $name . '" '
                    . 'class="panth-order-item-thumb" '
                    . 'loading="lazy" width="36" height="36">';
            $html .= '<div class="panth-order-item-info">';
            $html .= '<span class="panth-order-item-name">' . $name . '</span>';
            $html .= '<span class="panth-order-item-meta">&times; ' . $qty . ' <em>(' . $sku . ')</em></span>';
            $html .= '</div>';
            $html .= '</div>';
        }

        if ($total > self::MAX_VISIBLE) {
            $remaining = $total - self::MAX_VISIBLE;
            $html .= '<a href="#" class="panth-order-items-more" '
                    . 'onclick="this.parentNode.querySelectorAll(\'[data-panth-hidden]\').forEach(function(e){e.style.display=\'flex\';});this.style.display=\'none\';return false;">'
                    . '+' . $remaining . ' more (click to view)'
                    . '</a>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get product thumbnail URL for an order item.
     */
    private function getProductThumbnailUrl($orderItem): string
    {
        try {
            $product = $orderItem->getProduct();
            if ($product && $product->getId()) {
                return $this->imageHelper
                    ->init($product, 'product_listing_thumbnail')
                    ->setImageFile($product->getThumbnail())
                    ->resize(36, 36)
                    ->getUrl();
            }
        } catch (\Throwable $e) {
            // Product may have been deleted — use placeholder
        }

        return $this->imageHelper
            ->getDefaultPlaceholderUrl('thumbnail');
    }

    /**
     * Simple HTML escape helper.
     */
    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
