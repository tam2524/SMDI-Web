# SMDI Web Portal - Handover Documentation

Welcome to the **SMDI-Web** project. This documentation serves as an exhaustive guide for developers inheriting the codebase, organized by functional portals and containing a detailed database guide.

---

## 📁 How to Access and Clone the Project

### 1. Repository Access (GitHub)
The project is hosted on GitHub. You can access and clone the repository using the link below:
*   **Repository URL:** [https://github.com/tam2524/SMDI-Web](https://github.com/tam2524/SMDI-Web)

### 2. Setup via Git Clone
To set up the project locally on your machine:
1.  Open your terminal/command prompt and navigate to your local web server's root directory (typically `C:\xampp\htdocs\`).
2.  Clone the repository using the following command:
    ```bash
    git clone https://github.com/tam2524/SMDI-Web.git
    ```
3.  Open the cloned folder in your preferred code editor (e.g., VS Code):
    *   **VS Code:** File > Open Folder... > Select `C:\xampp\htdocs\SMDI-Web`
4.  Install the composer libraries required by the app:
    ```bash
    composer install
    ```
5.  Create a `.env` file in the root folder (as it is ignored by Git) to set up your local database credentials.

### 3. Local Web Access
Once cloned, run Apache and MySQL in XAMPP and open your browser:
*   **URL:** `http://localhost/SMDI-Web/`

---

## 💾 Prerequisites & Required Installations

Before setting up the project locally, you must install the following software:

1.  **XAMPP (Local Web Server & Database):**
    *   This project requires an Apache server and MySQL/MariaDB database.
    *   **PHP Version:** Version 7.4 or higher (8.x compatible).
    *   [Download XAMPP for Windows](https://www.apachefriends.org/download.html)
2.  **Git:**
    *   Required to clone the codebase from GitHub and manage version control.
    *   [Download Git for Windows](https://git-scm.com/downloads)
3.  **Composer:**
    *   The PHP package dependency manager. It is required to install the libraries (TCPDF and PhpSpreadsheet) used for reports.
    *   [Download Composer for Windows](https://getcomposer.org/download/)
4.  **Text Editor / IDE:**
    *   **VS Code** is highly recommended for working on this project.
    *   [Download VS Code](https://code.visualstudio.com/)

---

## 🚀 How to Set Up the Project Locally

Once you have installed the prerequisites above:

1.  **Clone the Repo:**
    Open Git Bash or CMD, navigate to `C:\xampp\htdocs\`, and run:
    ```bash
    git clone https://github.com/tam2524/SMDI-Web.git
    ```
2.  **Install Dependencies:**
    Navigate inside the project folder (`C:\xampp\htdocs\SMDI-Web`) and run:
    ```bash
    composer install
    ```
    This generates the `vendor/` directory and sets up TCPDF/PhpSpreadsheet.
3.  **Configure Environment (`.env`):**
    *   Create a file named `.env` in the root folder (`C:\xampp\htdocs\SMDI-Web\.env`).
    *   Add the following variables (modify user/pass/port based on your local MySQL setup):
        ```env
        DB_HOST=127.0.0.1
        DB_USER=root
        DB_PASS=
        DB_NAME=smdi_website_db
        DB_PORT=3306
        ```
4.  **Import Database:**
    *   Start XAMPP Control Panel and start **Apache** and **MySQL**.
    *   Go to `http://localhost/phpmyadmin/` in your browser.
    *   Create a new database named `smdi_website_db`.
    *   Import your SQL database dump file.

---

## 🛠️ Technical Stack

*   **Backend:** Native PHP (v7.4 - v8.x compatible).
*   **Database:** MySQL / MariaDB (connected via `mysqli` extension).
*   **Frontend:** Bootstrap 5.3, jQuery, HTML5, Vanilla CSS, and JS.
*   **Libraries:** 
    *   **TCPDF:** Used for PDF report generation.
    *   **PhpSpreadsheet:** Used for Excel report exports.

---

## 🌐 1. Public Website

This portal is client-facing. It contains pages to display products, location branches, company info, and handles visitor inquiry submissions.

### File Navigation:
*   `index.html` - The public landing page.
*   `about.html` - Static "About Us" information page.
*   `branches.html` - Static map and listing page for physical branches.
*   `careers.html` - Careers and employment opportunity announcements page.
*   `parts.html` - Public parts inventory listing.
*   `404_page.html` - Standard 404 Page Not Found error page.
*   `under_repair.html` - Under maintenance advisory page.
*   **Product Pages:**
    *   `Big_bikes.html` - Catalog page for Big Bikes (large engine capacity motorcycles).
    *   `honda_motorcyles.html` - Catalog page for Honda motorcycles.
    *   `kawasaki_motorcyles.html` - Catalog page for Kawasaki motorcycles.
    *   `suzuki_motorcyles.html` - Catalog page for Suzuki motorcycles.
    *   `yamaha_motorcyles.html` - Catalog page for Yamaha motorcycles.
*   **Customer Inquiry Forms & APIs:**
    *   `inquiry_form.html` - Form for submitting general motorcycle purchase inquiries.
    *   `lto_plate_inquiry.html` - Plate status checker form.
    *   `lto_reg_inquiry.html` - Registration status checker form.
    *   `api/inquiry.php` - Processes customer inquiries and writes them to the `inquiries` table.
    *   `api/inquiry_details.php` - Fetches detailed client inquiry information.
    *   `api/lto_plate_inquiry.php` - Processes client LTO plate status searches.
    *   `api/track_visit.php` / `api/track_visitor.php` - Analytics trackers to log page hits.
    *   `api/visitor_logs.php` / `api/visitor_stats.php` - Feeds hits metrics to dashboards.

---

## 📋 2. Liaison Management System

This portal is used by Liaison Officers to process and track documents, registrations, and status records.

### File Navigation:
*   `liaison/liaison_dashboard.php` - Liaison dashboard portal.
*   `backups/liason_dashboard.html` - Legacy mock layout.
*   `api/lto_plate_inquiry.php` - Reads/updates plate records.

---

## 🏍️ 3. Motorcycle Inventory Management System

This system handles motorcycle stock levels, sales reporting, amortization installment plans, user accounts, and branch prints.

### File Navigation:
*   **Inventory Views (`inventory/`):**
    *   `inventory/admin_inventory.php` - Inventory interface for administrators.
    *   `inventory/branch_inventory.php` - Inventory interface for Branch Managers.
    *   `inventory/headoffice_inventory.php` - General view for Head Office managers.
*   **Admin Console (`admin/`):**
    *   `admin/admin_dashboard.php` - Core Admin Dashboard (handles user role updates and portal stats).
    *   `admin/admin_inventory.php` - Admin master inventory page (handles product logs).
*   **Sales Dashboard (`sales/`):**
    *   `sales/sales_dashboard.php` - Sales metrics and transactions log recording portal.
*   **Amortization Calculator (`amortization_dashboard/`):**
    *   `amortization_dashboard/index.html` - Amortization panel landing view.
    *   `amortization_dashboard/calculator.php` - Amortization calculator user interface.
    *   `amortization_dashboard/app.js` - Frontend calculations and AJAX handling.
    *   `amortization_dashboard/style.css` - Custom styling sheet.
    *   `amortization_dashboard/upload.php` - Handles file uploads for amortization data.
    *   `amortization_dashboard/uploads/pricing_data.xlsx` - Spreadsheet file template containing interest rates.
*   **Staff Print Controls (`staff/`):**
    *   `staff/staff_dashboard.html` - Generic landing dashboard.
    *   `staff/staff_print_form.html` - Form to select branches and print records.
*   **Motorcycle Backend APIs (`api/`):**
    *   `api/db_config.php` - Core database configuration file (reads credentials dynamically from `.env` using persistent `p:` connections).
    *   `api/auth.php` - Middleware verifying user login state and session tokens.
    *   `api/login.php` - Processes credentials and redirects users based on role.
    *   `api/logout.php` - Destroys active user sessions.
    *   `api/user_management.php` - Handles system users (add, edit, list, delete).
    *   `api/add_Record.php` - API to insert new inventory and sales records.
    *   `api/edit_Record.php` - API to update/edit general inventory records.
    *   `api/delete_Record.php` - API to delete records from the system.
    *   `api/fetch_Records.php` - Paginated and searchable records reader API.
    *   `api/get_Record.php` - Fetches single record details.
    *   `api/fetch_inquiries.php` - Unified API fetching customer inquiries for specific branches (uses `?branch=BranchName` or outputs all).
    *   `api/inventory_management.php` - Handles general motorcycle stock inventories.

---

## 🔧 4. Spareparts Management System

This is a comprehensive module managing spare parts warehouse operations, counter sales, pricelist adjustments, supplier aging, and check collections.

### File Navigation:
*   **Portals & Dashboards (`spareparts/`):**
    *   `spareparts/admin_dashboard.php` - Spare parts admin summary dashboard.
    *   `spareparts/admin_spareparts.php` - Spare parts admin main interface.
    *   `spareparts/warehouse_dashboard.php` - Warehouse operations summary page.
    *   `spareparts/warehouse_spareparts.php` - Warehouse stock coordinator page.
    *   `spareparts/branch_spareparts.php` - Local branch manager spare parts operations portal.
    *   `spareparts/headoffice_spareparts.php` - Head office spare parts dashboard.
    *   `spareparts/owner_dashboard.php` - Overview dashboard for owners.
    *   `spareparts/sales_dashboard.php` - Overview dashboard for parts sales.
*   **Inventory Operations:**
    *   `spareparts/beginning_inventory.php` - Initial stock configuration page.
    *   `spareparts/inventory_in.php` - Handles stock inventory receiving logs.
    *   `spareparts/transfer_stock.php` - Processes branch stock transfers.
    *   `spareparts/received_stock.php` - Logs of supplier deliveries.
    *   `spareparts/find_stocks.php` - Checks part stocks.
*   **Sales & Customers:**
    *   `spareparts/sales_spareparts.php` - Retail counter sales panel.
    *   `spareparts/customer_records.php` - Customer logs viewer.
    *   `spareparts/employee_records.php` - Employee logs viewer.
    *   `spareparts/beginning_customer_balance.php` - Configures starting credit balances.
    *   `spareparts/sales_pdc_payments.php` - Handles checks and payments.
*   **Reporting & Utilities:**
    *   `spareparts/master_reports.php` - Master PDF/Excel report generator panels.
    *   `spareparts/payable_report.php` - Account payables summary panel.
    *   `spareparts/pricing_management.php` - Interface for bulk price changes via Excel.
    *   `spareparts/barcode_generator.php` - Dynamically generates printable barcodes.
    *   `spareparts/api_user_management.php` - Local user authentication control.
    *   `spareparts/user_management.js` / `spareparts/user_management.php` - Localized user accounts managers.
*   **Spareparts Backend APIs (`api/`):**
    *   `api/spareparts_inventory.php` - Core operations API on spare parts table.
    *   `api/spareparts_dashboard_api.php` - Handles summary charts for parts dashboard.
    *   `api/bulk_price_preview.php` - API parsing excel sheets to preview bulk price updates.
    *   `api/bulk_price_update.php` - API applying bulk excel updates to database.
    *   `api/sales_features_api.php` - Handles advanced sales controls (returns, credit sales, validation).
    *   `api/payable_api.php` - Processes account payables (supplier aging metrics).
    *   `api/pdc_api.php` - Processes check collections and schedules.
    *   `api/migrate_spareparts.php` - Migration script to create spare parts tables.
    *   `api/reports_master.php` - Manages general reporting metrics.

---

## 🗄️ Database Guide

The database schema is divided into user accounts, visitor logs, motorcycle tracking, and a comprehensive spare parts subsystem. Many tables are set to **auto-create** on execution of their respective APIs if not already present.

### 🔑 1. User & Access Control
*   `users` - Core portal user records.
    *   `id` (INT, Primary Key, Auto Increment)
    *   `username` (VARCHAR)
    *   `fullName` (VARCHAR)
    *   `position` (VARCHAR) - Defines the portal redirection role (e.g. `Admin`, `Spareparts-Sales`, `Liaison`).
    *   `branch` (VARCHAR) - Denotes assigned location.
    *   `password` (VARCHAR) - Password hash created via PHP `password_hash()`.

### 🏍️ 2. Motorcycle Inventory & Inquiries
*   `inquiries` - Customer inquiries submitted through public forms.
    *   `firstname`, `middlename`, `lastname` (VARCHAR)
    *   `address`, `incomesource` (VARCHAR)
    *   `withvalidid` (VARCHAR)
    *   `mobilenumber` (VARCHAR)
    *   `mcbrand`, `mcmodel` (VARCHAR)
    *   `plandatepurchase` (DATE/VARCHAR)
    *   `nearestbranch` (VARCHAR) - Key used for branch-specific query sorting.
*   `records` - Master inventory log.
    *   `record_id` (INT, Primary Key)
    *   `family_name`, `first_name`, `middle_name` (VARCHAR)
    *   `plate_number`, `mv_file` (VARCHAR)
    *   `branch`, `batch` (VARCHAR)
    *   `remarks` (TEXT)

### 🔧 3. Spare Parts Subsystem Tables
*   `spareparts_inventory` - Core spare parts stock records.
    *   `id` (INT, Primary Key)
    *   `part_no` (VARCHAR) - Unique part identifier code.
    *   `description` (VARCHAR)
    *   `cost` (DECIMAL) - Purchase cost.
    *   `price` (DECIMAL) - Retail selling price.
    *   `quantity` (INT)
    *   `bin_location` (VARCHAR) - Shelf/bin coordinate.
    *   `current_branch` (VARCHAR) - Branch storing the part.
    *   `thumbnail_image` (VARCHAR)
*   `spareparts_price_history` - History of cost and price adjustments.
    *   `id` (INT, Primary Key)
    *   `part_no` (VARCHAR)
    *   `cost`, `price` (DECIMAL)
    *   `supplier`, `invoice_no` (VARCHAR)
    *   `change_reason` (VARCHAR)
    *   `changed_by` (VARCHAR)
    *   `transaction_date` (DATETIME)
*   `spareparts_compatibility` - Compatibility mapping between parts and motorcycle models.
    *   `id` (INT, Primary Key)
    *   `part_no` (VARCHAR)
    *   `model_name` (VARCHAR)
*   `spareparts_pdc_payments` - Tracks Post-Dated Check (PDC) collections.
    *   `id` (INT, Primary Key)
    *   `check_number`, `bank_name` (VARCHAR)
    *   `amount` (DECIMAL)
    *   `due_date` (DATE)
    *   `status` (VARCHAR) - e.g. `Pending`, `Cleared`, `Bounced`.
*   `spareparts_supplier_aging` - Tracks aging of accounts payables due to parts suppliers.
*   `spareparts_customers` - Registered customers with credit management details.
*   `spareparts_returns` - Logs of spare parts product returns.
*   `spareparts_settings` - System-wide configuration variables (e.g. enabling credit limits).
*   `spareparts_sales_force` - Employees registered under the parts sales team.
*   `spareparts_pricelists` - Holds references to pricing tiers.
*   `spareparts_transfer_items` / `spareparts_transfers` - Tracks transfers between warehouse locations.

### 🌐 4. Visitor Tracking
*   `visitor_logs` / `visitor_stats` - Logs visitor metadata, hit counts, and analytics.
