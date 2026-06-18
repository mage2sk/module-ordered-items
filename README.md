<!-- SEO Meta -->
<!--
  Title: Magento 2 Ordered Items Extension: Order Items Column in Admin Sales Grid | Panth Infotech
  Description: Panth Ordered Items adds a rich Order Items column to the Magento 2 admin Sales Order Grid. See product thumbnails, names, SKUs, quantities, prices, configurable options, and per-item fulfillment status badges without opening each order. Fully configurable from admin. Works on Magento 2.4.4 to 2.4.8 and PHP 8.1 to 8.4. Built by Top Rated Plus Magento developer Kishan Savaliya.
  Keywords: magento 2 ordered items, magento 2 order grid column, magento 2 admin order grid, order items column magento 2, magento 2 order grid thumbnails, magento 2 order grid fulfillment status, magento 2 sales grid customization, magento 2 order items extension, admin order grid product details, mage2kishan ordered items
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://kishansavaliya.com/magento-2-ordered-items.html
-->

# Magento 2 Ordered Items Extension: Order Items Column in Admin Sales Grid

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Hyva + Luma](https://img.shields.io/badge/Themes-Hyva%20%2B%20Luma-14b8a6)](https://www.hyva.io)
[![Live Demo & Details](https://img.shields.io/badge/Live%20Demo%20%26%20Details-magento--2--ordered--items-0D9488?style=flat)](https://kishansavaliya.com/magento-2-ordered-items.html)
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--ordered--items-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-ordered-items)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)

> **See what every order contains without opening it.** Panth Ordered Items adds a rich "Order Items" column to the Magento 2 admin Sales Order Grid, showing product thumbnails, names, SKUs, quantities, prices, configurable options, and per-item fulfillment status badges right in the list.

**Product page:** [kishansavaliya.com/magento-2-ordered-items.html](https://kishansavaliya.com/magento-2-ordered-items.html)

---

## Quick Answer

**What is Panth Ordered Items?** It is a Magento 2 extension that adds an Order Items column to the admin Sales Order Grid, so you can see exactly what was purchased in each order without clicking into the order detail page.

**What does it add to my store?**

- An **Order Items column** in Sales > Orders showing product thumbnails, names, SKUs, quantities, prices, and configurable options.
- **Per-item fulfillment status badges** (Pending, Invoiced, Shipped, Refunded, Canceled) color-coded inline.
- An **items summary badge** showing the total number of distinct items and total units at the top of each cell.
- A **paginated popup** for large orders: when an order exceeds the configured threshold (default 10 items), a "View all" link opens a paginated popup with page size selector and navigation.

**Which themes are supported?** The extension works on both **Hyva** and **Luma** storefronts. It only affects the admin panel, so the storefront theme does not matter.

**What does it need?** Magento 2.4.4 to 2.4.8, PHP 8.1 to 8.4, and the free `mage2kishan/module-core` package.

---

## Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** for custom modules, Hyva themes, performance work, M1 to M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success • 10+ Years Magento Experience
Adobe Certified • Hyva Specialist

</td>
<td width="50%" align="center">

### Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules • Theme Design • Migrations
Performance • SEO • Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Who Is It For](#who-is-it-for)
- [Key Features](#key-features)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [How It Works](#how-it-works)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Who Is It For

- **Store managers who process high order volumes** and need to know at a glance what each order contains, without opening every order individually.
- **Fulfillment teams** who want to see invoiced, shipped, and refunded quantities per item directly from the order list.
- **Merchants selling configurable products** (clothing sizes, colors, variants) who need to see the exact options selected in each order.
- **Admins running reports or audits** who want product thumbnail recognition instead of reading SKU codes line by line.
- **Any Magento store** that finds the default order grid too bare and wants richer order information without custom development.

---

## Key Features

### Order Items Column in the Sales Grid

- **Product thumbnails** so you recognize ordered items at a glance.
- **Product names** that link to the product editor in a new tab (optional).
- **SKU display** so you can identify products by their codes.
- **Quantity, unit price, and row total** with the order's actual currency (USD, EUR, GBP, etc.).
- **Configurable product options** such as Size and Color shown inline below the product name.
- **Items summary badge** showing the total distinct items and total units at the top of each cell.
- **Collapsible "show more" link** when an order has more inline items than the configured limit (default 3).

### Fulfillment Status Badges

- **Color-coded per-item status**: Pending (gray), Invoiced (blue), Shipped (green), Refunded (amber), Canceled (red).
- **Multiple badges per item** when quantities are split across statuses (e.g. Invoiced: 2, Shipped: 1, Refunded: 1).
- **Instant fulfillment check** without navigating into the order.

### Paginated Popup for Large Orders

- **"View all" popup** opens when an order exceeds the popup threshold (default 10 items).
- **Page size selector** (10, 20, 50, All) and navigation controls inside the popup.
- **Thumbnails, SKUs, prices, and fulfillment badges** all available inside the popup too.
- **Grid stays compact** no matter how many items are in the order.

### Fully Admin-Configurable

- **Every display element is independently toggleable** from Stores > Configuration > Panth Extensions > Ordered Items Grid.
- **No code changes** needed to customize what the column shows.
- **Works out of the box** with sensible defaults from the moment you enable it.

### Built to Last

- **No database tables** added. The column is purely virtual and renders at display time.
- **No frontend impact** whatsoever. Only the admin panel is affected.
- **Constructor dependency injection only**. No ObjectManager calls.
- **Translation ready**. Every label uses Magento's `__()` function.

---

## Preview

![Panth Ordered Items -- Order Grid Preview](docs/order-items-grid-preview.png)

*Order Items column showing product thumbnails, names, SKUs, quantities, prices, configurable options, and fulfillment status badges inline in the admin order grid.*

### Paginated Popup for Large Orders

https://github.com/mage2sk/module-ordered-items/raw/main/docs/order-items-popup-demo.mp4

*Orders with more than 10 items show a "View all" link that opens a paginated popup with page size selector, navigation, thumbnails, SKUs, prices, and fulfillment badges.*

![Admin Configuration -- Ordered Items Grid](docs/admin-config-preview.png)

*Every element in the Order Items column is independently toggleable from admin configuration.*

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 to 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| Required Dependency | `mage2kishan/module-core` (free) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-ordered-items
bin/magento module:enable Panth_Core Panth_OrderedItems
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release from [Packagist](https://packagist.org/packages/mage2kishan/module-ordered-items) or from the [product page](https://kishansavaliya.com/magento-2-ordered-items.html).
2. Extract it to `app/code/Panth/OrderedItems/` in your Magento install.
3. Make sure `Panth_Core` is installed too (required dependency).
4. Run the commands above starting from `bin/magento module:enable`.

### Verify Installation

```bash
bin/magento module:status Panth_OrderedItems
# Expected: Module is enabled
```

After install, open:
```
Admin -> Sales -> Orders
```
The "Order Items" column appears automatically in the grid.

---

## Configuration

Go to **Stores -> Configuration -> Panth Extensions -> Ordered Items Grid**.

### General Settings

| Setting | Group | Default | Description |
|---|---|---|---|
| Enable Order Items Column | General | Yes | Master toggle. Set to No to hide the column entirely. |
| Max Visible Items (Inline) | General | 3 | Number of items shown inline before the "show more" link appears. |
| Popup Threshold | General | 10 | When an order has more items than this, "View all" opens a paginated popup instead of expanding inline. |

### Display Options

| Setting | Group | Default | Description |
|---|---|---|---|
| Show Product Thumbnail | Display | Yes | Product image in each item row. |
| Show SKU | Display | Yes | Product SKU code. |
| Show Price | Display | Yes | Unit price and row total with the order's currency. |
| Show Quantity | Display | Yes | Ordered quantity. |
| Show Product Options | Display | Yes | Size, Color, and other configurable attributes. |
| Show Fulfillment Status | Display | Yes | Invoiced, Shipped, Refunded, Canceled badges per item. |
| Show Items Summary | Display | Yes | Total items count and total units badge at the top of the cell. |
| Link Product Name to Product Edit | Display | Yes | Click product name to open the product editor in a new tab. |

---

## How It Works

1. The module adds a virtual `OrderItems` column to the `sales_order_grid` UI component through layout XML.
2. When the grid renders, the column's `prepareDataSource()` method receives each visible order row's `entity_id`.
3. For each order, it loads the visible items via Magento's `OrderRepositoryInterface`.
4. It renders HTML for each item: thumbnail, name, SKU, options, quantity, price, and fulfillment badges.
5. `event.stopPropagation()` prevents clicks on the column from navigating to the order detail page.
6. All display toggles from admin configuration are respected at render time.
7. Prices are formatted using Magento's `PriceCurrencyInterface` with the order's actual currency.

For orders exceeding the popup threshold, a "View all" link is rendered instead. Clicking it opens a paginated popup that loads items via an admin AJAX request, with page size controls and navigation.

**Performance note:** The column loads order items for each visible row when the grid page renders. For stores with very high order volumes, limit the grid page size to 20-50 rows or disable thumbnails to reduce image loading time.

---

## FAQ

### Does the column slow down the order grid?

Minimally. Items are loaded for each visible row when the page renders. For large stores, set the grid page size to 20-50 rows and disable thumbnails if image loading is a concern.

### Does it work with configurable, bundle, and grouped products?

Yes. Configurable products show their selected options (Size, Color, and other attributes). Bundle products show the bundle item selections. Grouped products show each individual item.

### Can I turn off specific elements like prices or thumbnails?

Yes. Every element in the column has its own toggle in Stores > Configuration > Panth Extensions > Ordered Items Grid. You can show only names and SKUs if you prefer a minimal view.

### Does it modify the sales_order_grid database table?

No. The column is purely virtual. It renders at display time using the order repository. No database schema changes are made.

### Does it conflict with other grid customization extensions?

No. It adds a new column without touching existing columns. It uses Magento's standard UI component extension mechanism.

### Does it work with third-party order management extensions?

Yes. It reads from Magento's standard `OrderRepositoryInterface` and does not modify any order data.

### Is it translation ready?

Yes. Every label uses Magento's `__()` function, so you can add translations through a language pack or theme.

### Does it have any frontend impact?

No. The module only adds a column to the admin order grid. Storefront pages, checkout, and customer accounts are not affected at all.

### Does Panth Ordered Items need Panth Core?

Yes. `mage2kishan/module-core` is a free, required dependency that Composer installs automatically.

---

## Support

| Channel | Contact |
|---|---|
| Product Page | [kishansavaliya.com/magento-2-ordered-items.html](https://kishansavaliya.com/magento-2-ordered-items.html) |
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-ordered-items/issues](https://github.com/mage2sk/module-ordered-items/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### Need Custom Magento Development?

Looking for **custom Magento module development**, **Hyva theme work**, **store migrations**, or **performance tuning**? Get a free quote in 24 hours:

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%92%AC%20Get%20a%20Free%20Quote-kishansavaliya.com%2Fget--quote-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<p align="center">
  <a href="https://www.upwork.com/freelancers/~016dd1767321100e21">
    <img src="https://img.shields.io/badge/Hire%20Kishan-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Hire on Upwork" />
  </a>
  &nbsp;&nbsp;
  <a href="https://www.upwork.com/agencies/1881421506131960778/">
    <img src="https://img.shields.io/badge/Visit-Panth%20Infotech%20Agency-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Visit Agency" />
  </a>
  &nbsp;&nbsp;
  <a href="https://kishansavaliya.com/magento-2-ordered-items.html">
    <img src="https://img.shields.io/badge/View%20Product%20Page-magento--2--ordered--items-0D9488?style=for-the-badge" alt="View Product Page" />
  </a>
</p>

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** ([kishansavaliya.com](https://kishansavaliya.com)), a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency that builds high quality, security focused extensions and themes for both Hyva and Luma storefronts. The extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management, with each module built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on our [Magento extensions page](https://kishansavaliya.com/magento-extensions.html) or on [Packagist](https://packagist.org/packages/mage2kishan/).

---

## Quick Links

| Resource | Link |
|---|---|
| **Product Page** | [magento-2-ordered-items.html](https://kishansavaliya.com/magento-2-ordered-items.html) |
| **Packagist** | [mage2kishan/module-ordered-items](https://packagist.org/packages/mage2kishan/module-ordered-items) |
| **GitHub** | [mage2sk/module-ordered-items](https://github.com/mage2sk/module-ordered-items) |
| **Website** | [kishansavaliya.com](https://kishansavaliya.com) |
| **Free Quote** | [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote) |
| **Upwork (Top Rated Plus)** | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| **Upwork Agency** | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |
| **Email** | kishansavaliyakb@gmail.com |
| **WhatsApp** | +91 84012 70422 |

---

<p align="center">
  <strong>Ready to see order contents at a glance?</strong><br/>
  <a href="https://kishansavaliya.com/magento-2-ordered-items.html">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20See%20Ordered%20Items%20%E2%86%92-Product%20Page%20%26%20Demo-DC2626?style=for-the-badge" alt="See Ordered Items" />
  </a>
</p>

---

**SEO Keywords:** magento 2 ordered items, magento 2 order grid column, magento 2 admin order grid, order items column magento 2, magento 2 order grid thumbnails, magento 2 order grid fulfillment status, magento 2 sales grid customization, magento 2 order items extension, admin order grid product details, magento 2 order grid sku, magento 2 order grid product images, magento 2 order grid configurable options, magento 2 fulfillment status badges, magento 2 sales order grid, panth ordered items, mage2kishan ordered items, magento 2 order management, magento 2 admin grid customization, magento 2.4.8 order grid, php 8.4 order grid, magento 2 order grid popup, magento 2 sales grid extension, hire magento developer, top rated plus upwork, kishan savaliya magento, custom magento development, panth infotech
