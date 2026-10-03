# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.11] - 2026-10-03

### Fixed
- Product names that contain HTML entities (for example Lumaflex&trade;) are shown correctly in the grid and popup instead of the raw entity text.
- The "Show" selector in the popup now shows 20, matching the 20 items listed on the first page.
- Turning off "Enable Order Items Column" now removes the column from the grid, the column picker and the export instead of leaving an empty column.
- The CSV and XML export of the order grid now contains the ordered items as plain text instead of an empty "Order Items" column.
- The popup is now an accessible dialog: it has a dialog role and title, the close button has a label, focus moves into the popup and stays there while it is open, Escape closes it, focus returns to the "View all" button, and the page behind it no longer scrolls.
- "+ N more items", "Show less" and "View all" are keyboard buttons with a visible focus outline; the inline toggle reports whether it is expanded.
- Links in the column use a darker admin blue that meets AA contrast on white and striped rows; disabled popup buttons are easier to read.
- Decimal quantities (for example 1.5) are no longer cut to whole numbers, and "1 unit" / "0 units" use the right wording.
- Labels in the column and popup can now be translated.
- Max Visible Items and Popup Threshold must be whole numbers greater than 0, and the other settings are hidden while the column is disabled.
