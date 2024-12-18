=== Paid Memberships Pro PDF Invoices ===
Contributors: andrewza, yoohooplugins
Tags: pdf, pdf invoice, invoices
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4GC4JEZH7KSKL
Requires at least: 5.2
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.23
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
= 1.23 - 2024-11-06 =
* ENHANCEMENT: Added more template variables, see documentation - https://yoohooplugins.com/documentation/getting-started-with-paid-memberships-pro-pdf-invoices/ (Thanks @GCalToolkit)
* BUG FIX: Fixed warning where $content was undefined in cases. (Thanks @toby-pondr)
* BUG FIX: Fixed warnings for PHP 8.0+ (Thanks @mircobabini)
* SECURITY: Improved security on escaping output within the shortcode output.

= 1.22.1 - 2023-08-15 =
* BUG FIX: Fixed an issue where the "Download Sample PDF" would cause a fatal error.

= 1.22 - 2023-07-12 =
* ENHANCEMENT: Added support for Pay By Check Add On email reminders.
* ENHANCEMENT: Improved license checking functionality more often and regularly.
* BUG FIX: Fixed an issue where {{billing_address}} wasn't displaying correctly.
* BUG FIX: Fixed an issue in some cases when saving an order in the admin, would cause the PDF date to show the current date and not the order date.
* BUG FIX/REFACTOR: Fixed an issue where the "Generate PDF" option was not working due to sanitization updates to Paid Memberships Pro orders table.

= 1.21 - 2023-03-13 =
* REFACTOR: Reverted the 'pmpropdf_download_all_zip' shortcode that went missing from previous update and neatened it up slightly.
* ENHANCEMENT: Added a small loading icon when generating the PDF from the Member Orders admin page.

= 1.20 - 2023-03-07 =
* ENHANCEMENT: Added new templates: green, blank and split.
* ENHANCEMENT: Adjusted logic for [pmpropdf_download_list], this now allows logged-in non-members to access past PDF invoices.
* ENHANCEMENT: Added logic to generate single PDF Invoices from the Paid Memberships Pro Orders admin table.
* ENHANCEMENT: Added more PDF variables: {{admin_email}}, {{membership_description}} and {{membership_level_confirmation_message}}.
* ENHANCEMENT: Only show the NGINX nudge message on PMPro dashboard pages.
* ENHANCEMENT: Added a filter to adjust the DOMPDF object before it's used to generate the PDF: pmpropdf_dompdf_before_render.
* BUG FIX: Fixed an issue when regenerating PDF invoices would give you the current date and not the date of the order.

= 1.11 - 2022-09-14 =
* ENHANCEMENT: Added dynamic logic to try and get user_meta from any non-predefined variables. For example, if you have a custom field of "company" you may now pass {{company}} into the PDF to automatically generate the data from this variable.
* BUG FIX: Fixed an issue where {{invoice_date}} would be set to the current date when regenerating PDF's.

= 1.10 - 2022-01-11 =
* ENHANCEMENT: Added feature to download a preview of the PDF invoice template created. This uses a dummy order that Paid Memberships Pro provides.
* ENHANCEMENT: Improved localization for strings that were excluded.
* ENHANCEMENT: Improved handling of the payment_method label. Automatically detect the frontend label used.

= 1.9.1 - 19-08-2021 =
* Enhancement: Filter added 'pmpro_should_generate_pdf'. Allows developers to stop generating PDFs for certain cases.
* Enhancement: Filter added 'pmpropdf_invoice_table_requires_active_membership'. Restricts PDF invoices table/shortcode to members only. Thanks @mircobabini
* Bug Fix/Enhancement: Make sure an order exists when generating PDF invoice. Thanks @mircobabini

= 1.9 - 10-05-2021 =
* Enhancement: Changed custom template storage path to make use of the uploads directory instead (Directory: pmpro-invoice-templates/order.html)
* Enhancement: Added automated migration for custom templates from child theme directory to the uploads directory, automatically deletes original 
* Enhancement: Improved tempalte content retrieval functionality and path generation
* Enhancement: Added auto-regeneration when order is updated. Can be controlled by filter
* Enhancement: Added option to enabled admin checkout email attachments
* Enhancement: Added tool to allow administrators to download a ZIP file of all stored invoices
* Bug Fix/Enhancement: Auto delete ZIP files once downloaded by end user
* Bug Fix: Improved date processing for order data, is some cases DateTime constructor would fail, causing invoice generation to fail. Fallbacks in place for this now
* Bug Fix: Improved error handling in general to improve how gracefully errors are handled during creation of PDF documents
* Bug Fix: Added Exception/Error catches to the bulk regeneration ajax loop, to be used with graceful error output

= 1.8 - 01-02-2021 =
* Enhancement: Added in a filter to allow changing of the gateway name 'pmpro_pdf_gateway_string'
* Enhancement: Localize and escape strings that were missing. @mircobabini
* Enhancement: Added new filters 'pmpropdf_can_attach_pdf_email', 'pmpropdf_can_generate_pdf_on_added_order' and 'pmpro_pdf_invoice_name'. @mircobabini
* Enhancement: Adjusted the formatting of the {{billing_details}} template. @mircobabini
* Bug Fix/Enhancement: Get order level when generating templates instead of user's current level. @mircobabini
* Bug Fix: Fixed an issue with license key expiration date output not showing correctly.
* Bug Fix: Fixed issue where Yoohoo Plugins license key wasn't activating correctly.

= 1.7 - 15-05-2020 =
* Enhancement: Added fullscreen template editor
* Enhancement: Added custom 'unsaved changes' prompt, removed browser dialog (Still used if custom prompt cannot be shown for any reason)
* Enhancement: General improvements to the editor
* Enhancement: Added logic to attach invoice PDF's to billable invoice emails (previously: invoice and checkout emails only)
* Enhancement: Notice clarity added to the 'generate missing invoice logs'
* Enhancement: Added hint text to draw attention to built in templates
* Bug Fix: Fixed an issue where clicking 'Save Template' would sometimes result in 'unsaved changes' confirm dialog to show


= 1.6 - 01-04-2020 =
* Enhancement: General UI/UX improvements.
* Enhancement: Support localization/internationalization. Thanks @mircobabini
* Bug Fix: Fixed issue with PDF table. Thakns @mircobabini

= 1.5 - 10-03-2020 =
Improvements to the download shortcode table. Thanks @mircobabini
Enhancement to show the order name as the download link. Thanks @mircobabini
Fixed issue where recurring invoice emails weren't attaching PDF.
Generates a PDF whenever an order is added.
Added ability to add custom variables to PDF template with a filter `pmpro_pdf_invoice_custom_variables`.
Show 15 latest orders for PDF downloads, filterable via `pmpropdf_invoice_table_limit`
Added filter to adjust prefix of "INV" `pmpro_pdf_invoice_prefix`

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