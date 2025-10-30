# GEMINI.md

This file provides guidance to Gemini when working with code in this repository.

## Project Overview

This project, "PPM-CC-Laravel" (Prestashop Product Manager), is an enterprise-grade application for managing products across multiple Prestashop stores. It serves as a central product hub for MPP TRADE.

### Key Technologies

*   **Backend:** PHP 8.3, Laravel 12.x
*   **UI:** Blade, Livewire 3.x, Alpine.js
*   **Database:** MySQL
*   **Build Tool:** Vite 5.x
*   **Dependencies:**
    *   `laravel/framework`: ^11.0
    *   `livewire/livewire`: ^3.0
    *   `maatwebsite/excel`: ^3.1 (for XLSX import)
    *   `spatie/laravel-permission`: ^6.0
    *   `laravel/socialite`: ^5.15 (for OAuth)
    *   `tailwindcss`: ^3.4.17

### Architecture

The application is built on a standard Laravel framework with a Livewire frontend. A critical architectural rule is that **SKU (Stock Keeping Unit) is the universal identifier for products**.

## Building and Running

### Local Development

1.  **Start the development server:**
    ```bash
    php artisan serve
    ```
2.  **Run database migrations:**
    ```bash
    php artisan migrate
    ```
3.  **Seed the database (if necessary):**
    ```bash
    php artisan db:seed
    ```
4.  **Install NPM dependencies:**
    ```bash
    npm install
    ```
5.  **Build frontend assets for development:**
    ```bash
    npm run dev
    ```
6.  **Build frontend assets for production:**
    ```bash
    npm run build
    ```

### Testing

*   **Run all tests:**
    ```bash
    php artisan test
    ```
*   **Run PHPUnit tests:**
    ```bash
    ./vendor/bin/phpunit
    ```
*   **Run PHPStan analysis:**
    ```bash
    vendor/bin/phpstan analyse
    ```
*   **Fix coding standards:**
    ```bash
    vendor/bin/php-cs-fixer fix
    ```

## Development Conventions

*   **SKU First:** Always use SKU as the primary key for products.
*   **No Inline Styles:** Do not use inline `style` attributes or Tailwind arbitrary values for styling. Use dedicated CSS classes in existing stylesheets.
*   **Vite Manifest:** When adding new CSS files, add them to existing CSS files to avoid issues with the Vite manifest on the production server.
*   **Logging:** Use extensive logging during development (`Log::debug`) and minimal logging in production (`Log::info`, `Log::warning`, `Log::error`).
*   **Agent System:** This project utilizes a system of AI agents to automate tasks. Refer to `AGENTS.md` and `CLAUDE.md` for more details on the agent system and how to interact with it.
*   **Reporting:** After completing a task, create a report in the `_AGENT_REPORTS` directory.

## Deployment

Deployment to the production server on Hostido is handled via PowerShell scripts located in the `_TOOLS` directory.

*   **Full Deploy:**
    ```powershell
    _TOOLS/hostido_deploy.ps1 -SourcePath "." -TargetPath "/domains/ppm.mpptrade.pl/public_html/"
    _TOOLS/hostido_deploy.ps1 -Command "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev && php artisan migrate --force && php artisan view:clear && php artisan config:clear && php artisan cache:clear"
    ```
*   **Quick Push (for single file changes):**
    ```powershell
    _TOOLS/hostido_quick_push.ps1 -Files @('path/to/your/file.php') -PostCommand "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"
    ```

**CRITICAL:** The production server does **not** have Node.js, npm, or Vite installed. All frontend assets must be built locally and then uploaded.

## Agent System

This project uses a system of AI agents to assist with development. For detailed information on the agents and their roles, please refer to the `AGENTS.md` and `CLAUDE.md` files.
