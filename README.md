<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

## ETIVACSILOG POS

### Install and start on Windows 10

Copy the project folder to the Windows 10 PC and double-click `setup-windows10.bat`. It installs PHP 8.3 and Node.js LTS with WinGet when missing, installs the PHP and JavaScript dependencies, builds the frontend, prepares SQLite, runs database migrations and seeders, then starts the POS. Windows 10 needs Microsoft's App Installer (WinGet) and an internet connection for the first setup.

For an existing installation, also transfer its `.env`, `database/database.sqlite`, and `public/uploads` so its settings, sales, and uploaded files are retained. The setup script does not replace an existing `.env` or database.

The launcher displays the PC's LAN address in the form `http://IPADDRESS:8001/posetivacsilogpos`; use that address on other devices on the same network. Allow PHP through Windows Defender Firewall on Private networks when prompted. Double-click `start-pos.bat` for later starts. It opens the POS in a browser and starts the Laravel scheduler in a separate console; keep both windows open while using the POS.

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
