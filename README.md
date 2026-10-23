# PrintFlow — Printing Service Management System

**Release 1: PHP 8.2+ · MySQL 8+ · HTML/CSS/JavaScript**

A responsive printing-request system for campus printing counters and small print shops. Customer and administrator roles, secure PDFs, automatic estimated quote, approval queue, cash pickup, and printable receipts.

## What is included

| Customer | Administrator |
|---|---|
| Create account / sign in | Sign in with a separately created admin account |
| Upload one PDF (15 MB max) | Search/filter request queue |
| Choose paper size, color and sides (tappable options) | Open customer PDF (permission checked) |
| Use `+` and `−` to select copies | Correct/verify page count and approve or reject |
| Automatically detect PDF pages when browser script loads | Move request through Printing → Ready |
| Review live cost, submit and track orders | Confirm exact cash payment and finish pickup |
| Cancel pending orders; view paid receipt | Change per-page print rates; print receipts |

**Kept to minimum required fields:** no unnecessary customer phone number, address, delivery instructions, or payment form. PDF page count is pre-filled when supported, and manually editable if the detector is unavailable. Rejection needs a reason; ordinary admin actions do not demand unnecessary notes. Cash completion is a confirmation checkbox, not another amount field.

## Install — XAMPP or Laragon (local development)

1. Install **PHP 8.2 or later**, **MySQL 8+**, Apache (for example XAMPP or Laragon). Ensure PHP extensions `pdo_mysql`, `fileinfo`, and `mbstring` are enabled.
2. Copy the entire folder `printing-system-release1` to `C:\xampp\htdocs\` or your Laragon `www` folder. Do not copy only `public` unless you also configure the sibling `app`, `database`, and `storage` folders.
3. Start **Apache** and **MySQL**.
4. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and choose **Import**:
   - First import `database/schema.sql` (creates the `printing_system` database and tables).
   - Then import `database/seed_rates.sql` (inserts *example* prices; edit them before real business use).
5. If your MySQL credentials differ from local default `root` with an empty password, copy `app/config.local.example.php` to **`app/config.local.php`** and set the values there. This local file is excluded by `.gitignore`.
6. In XAMPP's `php.ini`, adjust settings for 15 MB uploads if needed:
   ```ini
   file_uploads = On
   upload_max_filesize = 16M
   post_max_size = 20M
   max_file_uploads = 10
   ```
   Restart Apache after changing `php.ini`.
7. Create the **first administrator** through a terminal in the project folder (PHP must be on your PATH):
   ```bash
   php tools/create_admin.php
   ```
   Enter the administrator's name, email and password when prompted. No default admin password is shipped.
8. Go to **http://localhost/printing-system-release1/public/** to sign in, or register a customer.
9. Sign in as administrator and go to **Print rates** to replace sample prices with your real prices.

**Alternative for development:** run `php -S localhost:8000 -t public` from the project folder and open http://localhost:8000/. MySQL still needs to be running. This command is for local development only.

## Pages

```text
public/
  index.php              # Route to dashboard / sign in
  login.php, register.php, logout.php
  customer/
    dashboard.php         # Counts, recent requests, new-order shortcut
    new-order.php         # PDF upload, simple options, quote
    orders.php            # Own order history, status filter
    order.php             # Detail, timeline, pending cancellation
  admin/
    dashboard.php         # Pending/approved/printing/ready counts, queue, revenue
    orders.php            # Search and filter all orders
    order.php             # Approve/reject/print/ready/complete
    prices.php            # Price matrix and audit history records
  download.php            # Authorization-checked PDF viewer
  receipt.php             # Printer-friendly receipt (paid orders only)
  assets/css/style.css
  assets/js/app.js
app/
  bootstrap.php           # Sessions, CSRF, queries, auth, safe output
  config.php              # Database configuration + optional overrides
  layout.php              # Navigation and reusable UI
  config.local.example.php
storage/print_files/     # Original PDFs, private, random names
  .htaccess               # Deny direct HTTP access for Apache
  .gitkeep
database/
  schema.sql              # Database & relations
  seed_rates.sql          # Example demo prices
  .htaccess
tools/
  create_admin.php        # Admin provisioning; CLI only
  .htaccess
```

## Example rates (for local demonstration only)

| Paper | B&W | Color |
|---|---:|---:|
| A4 | ₱3.00 | ₱10.00 |
| Letter | ₱3.00 | ₱10.00 |
| Legal | ₱4.00 | ₱12.00 |

**Estimated and verified total = pages per copy × copies × per-page rate.** Duplex changes sheets used, not the page-count billing basis. All amounts are stored as MySQL `DECIMAL(10,2)` and totals are calculated with integer cents on the server. When an order is approved, current pricing is captured in the order's snapshot. Subsequent price edits never reprice approved orders.

## Workflow

```text
Customer submits PDF → Pending
                Admin verifies pages and quote → Approved
                                       Admin starts → Printing
                                 Admin checks output → Ready for pickup
                   Admin confirms exact cash paid → Completed + receipt

Exceptions: Pending → Rejected (reason required) or Canceled (customer initiated).
```

Customers can view only their own orders and PDFs; admins can manage all orders. A paid receipt appears only after the transaction is committed. Every status action is logged with actor and timestamp. Print rate changes are also logged.

## Notes about the PDF page detector

The print request screen *attempts* to load `pdf.js 3.11.174` from Cloudflare CDN for client-side page counting. **Internet access is needed for auto-detection with this included version.** If blocked/offline or PDF requires a password, the interface requests the page count manually (the admin still verifies it). PDF data is read in the browser and **not** uploaded to the CDN; the selected document is uploaded only when the user submits the order to their own server. The server never trusts the claimed page count without administrator verification.

## Deployment and security

- **For production, serve only the `public/` directory as the Apache/Nginx document root.** Do not deploy the whole parent folder as a public document root. For local XAMPP use, `.htaccess` files block direct Apache access to private directories; verify that `.htaccess` is honored on your machine. Nginx ignores `.htaccess`.
- Enable HTTPS, secure file permissions and database accounts with minimal privileges. Do not expose production passwords in PHP files committed to Git.
- PDFs are validated for `.pdf` extension, `application/pdf` MIME and `%PDF-` magic bytes before storing with a random name outside `public/`. These checks do *not* constitute malware scanning; scan files as required for production.
- Customers cannot access other customers' orders, PDFs or receipts; mutations require login, role checks, CSRF token and POST.
- CSRF tokens, session regeneration, password hashing, prepared SQL, and transactional status/payment changes are included.
- Back up MySQL **and** the private uploads folder. Define/document a file retention and deletion procedure with your organization before production.
- No email/SMS alerts, online payment gateway, printer hardware automation, or multi-file checkout is provided in Release 1.

## Release 1 checks (manual QA)

- [ ] Register and log in as customer; test duplicate email and invalid passwords.
- [ ] Upload an A4 PDF and verify that copies/pages/price are correct.
- [ ] Try non-PDF and >15 MB files; confirm safe validation.
- [ ] Refresh after submission; confirm no duplicate order.
- [ ] Try viewing another customer's order and document; verify they are inaccessible.
- [ ] Cancel a pending request; verify it cannot later be approved.
- [ ] Approve another order with corrected page count; confirm price and history.
- [ ] Mark it Printing, then Ready for pickup; confirm customer status updates.
- [ ] Confirm exact cash amount and complete; print customer/admin receipt.
- [ ] Change a rate; confirm older approved quotes remain unchanged.
- [ ] Try a forbidden status action or a POST missing CSRF; verify it is rejected.
- [ ] Test layout at phone and desktop widths.

**Testing status:** PHP syntax has been checked. Full end-to-end database flows require an installed and running MySQL server, configured credentials, and manual execution of the checklist above.

## Next releases (not implemented)

- **Release 2:** email notifications, multiple PDF files per order, advanced previews and reporting.
- **Release 3:** GCash/Maya or other approved payment integrations and enhanced automated PDF verification.
- **Release 4:** direct printer integration, inventory, staff and multi-branch capabilities.
