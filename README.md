<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

## ETIVACSILOG POS

### Start the application

Double-click `start-pos.bat`. It starts the Laravel web server and the Laravel scheduler in separate console windows. Keep both open while using the POS. Open `http://127.0.0.1:8001` in your browser.

### Run the POS from an Android tablet (Termux)

The cashier, cart, checkout, inventory, menu, and sales pages adapt to phone and tablet screen widths. The following setup runs the Laravel POS on the tablet and makes it available to other devices on the same trusted Wi-Fi network.

#### 1. Install Termux and copy the application

Install Termux from F-Droid or the official Termux GitHub releases. Avoid the outdated Play Store build. Open Termux once, then grant shared-storage access when prompted:

```sh
termux-setup-storage
```

Stop the POS on the Windows computer before copying its database. Copy the current project folder to the tablet using USB or another trusted transfer method. Include the current `.env`, `database/database.sqlite`, and `public/uploads/receipts` along with the application files. Do not upload `.env` or the database to a public repository or file-sharing service.

In Termux, extract or copy the project into Termux's private home directory, not directly under shared `Downloads` storage. For example, if a ZIP named `ETIVACSILOG.zip` is in Downloads:

```sh
mkdir -p "$HOME/etivacsilog"
unzip "$HOME/storage/downloads/ETIVACSILOG.zip" -d "$HOME/etivacsilog"
```

Change into the extracted folder that contains `artisan`, `composer.json`, and `setup-android-termux.sh`. Keeping the SQLite database in Termux's private home avoids SQLite locking and Android shared-storage permission problems.

#### 2. Install dependencies and migrate the database

Run the setup script from the project folder:

```sh
bash setup-android-termux.sh
```

The script installs PHP, Composer, Node.js, and SQLite; reconciles and installs the npm lockfile; builds the frontend; configures Laravel to use the copied SQLite file; and runs pending migrations without deleting existing orders, menu items, accounts, or inventory. Reconciliation allows setup to recover if a transferred `package-lock.json` is older than `package.json`. It preserves an existing `APP_KEY` and makes a timestamped copy of a non-empty SQLite database before migrating. If this is a brand-new database, it does not seed default accounts; use an existing database with cashier/superadmin accounts, or securely create accounts before opening the POS.

This setup expects the current POS SQLite database. If the live system is using MySQL, do not point this setup at an empty SQLite file expecting MySQL data to appear; export/import that database separately before using the tablet.

#### 3. Start the server

```sh
bash start-android-termux.sh
```

Keep Termux open while the POS is in use. The script prints the tablet's Wi-Fi address. Open `http://127.0.0.1:8001` on the tablet itself, or open `http://TABLET-IP:8001` on a cashier device connected to the same Wi-Fi. Press Ctrl+C in Termux to stop the server. Android may stop background apps to save battery, so set Termux battery use to **Unrestricted** and keep the tablet powered during service.

Use a trusted, private Wi-Fi network only. The server uses HTTP and is reachable by devices on that network; do not expose port 8001 to the public internet or configure router port forwarding. Change any development passwords before using the tablet for real sales.

#### 4. Update the tablet later and back up data

Stop the server with Ctrl+C before updating or copying the SQLite database. Copy the newer application files onto the tablet without replacing its `.env` or `database/database.sqlite`, then run:

```sh
bash setup-android-termux.sh
```

The setup script runs pending migrations and backs up the tablet database first. To move the live system back to another computer, stop the server and copy `database/database.sqlite`, `.env` (especially its `APP_KEY`), and `public/uploads/receipts`. Keep database backups private and store them outside the application folder too.

### Initial accounts

Run `php artisan migrate --seed` to create the initial accounts. The development defaults are:

- Superadmin: `niborobin` / `Etivacsilog2023`
- Cashier: `cashier` / `password`

Change the development superadmin password before deployment. Set `SUPERADMIN_NAME`, `SUPERADMIN_USERNAME`, `SUPERADMIN_EMAIL`, and `SUPERADMIN_PASSWORD` in `.env` before seeding on a deployed system.

The superadmin area is at `/superadmin`. It contains account management, menu management, and inventory/recipe management. Enter opening inventory quantities before processing sales; checkout will reject orders when a configured ingredient is out of stock.

The superadmin **Cash Flow** module is available from `/superadmin/cash-flow`. It groups paid sales and recorded expenses by payment method and calculates net cash flow. Record expenses there with their category and payment method. Cashier POS warns when stock is at or below the threshold configured in the inventory module.

Account management uses usernames and passwords; it does not ask for email addresses. The **Receipt editor** is available from the superadmin dashboard and controls the printed/PNG receipt business name, contact details, footer, survey URL placeholder, and 58 mm or 80 mm paper width.

Before a fresh live launch, use **Superadmin → Reset business data** to remove test orders, expenses, and inventory movements and set stock quantities to zero. Accounts, menu items, recipes, stock thresholds, and receipt settings are preserved. The reset requires typing `RESET BUSINESS DATA` and cannot be undone.

Meal recipes deduct one egg plus the mapped meal ingredient per meal. The Egg extra deducts one egg, and Siomai products deduct siomai stock. Recipe quantities can be changed in the superadmin inventory page.

Served kitchen orders are archived after midnight by the scheduler. Pending and cooking orders remain active. The kitchen page also catches up older served tickets if the scheduler was stopped overnight.

Orders can be voided from the kitchen ticket by entering a reason and confirming. Voided orders remain in the superadmin **Voided orders** audit list, are excluded from sales and cash-flow income, and restore inventory deducted by that order. Voids cannot be applied twice.

### Database changes

Run `php artisan migrate` after updating the application. The menu, inventory, recipe mappings, order archive timestamps, and user roles are stored in the configured database.

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
