=== Paid Memberships Pro PDF Invoices ===
Contributors: andrewza, yoohooplugins
Tags: pdf, pdf invoice, invoices
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4GC4JEZH7KSKL
Requires at least: 4.5
Tested up to: 5.0
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generates PDF Invoices for Paid Memberships Pro plugin.

== Description ==
Paid Memberships Pro PDF Invoices plugin will generate PDF Invoices for members after every checkout. This will automatically attach the invoice to the checkout emails that Paid Memberships Pro sends out.

== Installation ==
1. Upload the plugin files to the \'/wp-content/plugins\' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the \'Plugins\' screen in WordPress.
3. New checkouts will automatically get PDF invoices attached when they signup for a membership level.


== Frequently Asked Questions ==
= Is the PDF template customizable? =
Yes the PDF templates are customizable and use general HTML code with custom tags to replace values in the template.

== Changelog ==

= 1.4 =
Resolved bug where custom templates did not store custom CSS changes

= 1.3 =
Added check to remove empty tables rows from custom templates as this causes tables to be misplaced within the DomPDF handler
Added additional placement styles to prevent any divs from being placed where they do not belong
Added a formatter to the save call to improve the readability of the custom template HTML once it has been saved
Resolved a bug in DomPDF handler which would cause it to fail when a row does not have a cell present within it

= 1.2 =
Added a 'pmpropdf_download_list' shortcode which shows a table of invoice dates with download links for the current user
Added functionality to prevent/block access to download links not owner by the customer from the shortcode
Added ability to automatically append new shortcode to the PMPro Account Page
Added Shortcode tab to settings area with controls and descriptions of new functionality
Added a 'pmpropdf_download_all_zip' shortcode which allows the user to download a ZIP file of all their PDF's
Added functionality to ZIP the current users PDF's into a temporary archive file depending on request
Added checks to ensure ZipArchive module is available in the environment, along with visual feedback when it is not available
Added adbility to select a bundled invoice theme from the settings area
Added needed functionality to create a template override when a bundled theme is selected
Added a new 'Corporate' invoice theme and theme selector modal
Improved license status styling

= 1.1 =
Added custom template editor/creator using GrapeJS
Added custom shortcode components for template editor
Added custom shortcode extended hook
Changed Batch processor to process all orders, regardless of status
Added ability to set invoice logo
Added ability to batch process missing invoices via AJAX
Added tools area to settings
Added general settings area to settings
Added admin stylesheet and scripts
Modified default template to support logo if provided
Added reusable invoice path and name generators
Added additional code comments to function declarations
Modified settings area to support tab views
Code refactor (Modular functionality)
Improved invoice data handling

= 1.0 =
Initial release.

== Upgrade Notice ==
= 1.1 =
Update recommended for improved functionality and stability

= 1.0 =
Initial release.