# Statamic Component Transfer GUI

A local web tool for quickly transferring components between Statamic projects. Built for teams who reuse Antlers templates, Vue components, and other assets across multiple client sites.

## The Problem

Agencies working with Statamic often reuse components between projects—heroes, accordions, carousels, etc. The current workflow involves manually copying files between Finder/Explorer windows, which is error-prone and tedious when transferring multiple related files.

## The Solution

A simple GUI that lets you:
- Select source and destination projects from a dropdown (auto-detects projects in your Herd directory)
- Browse all transferable files in the source project
- Queue up multiple files for transfer
- Rename files and customize destination paths
- Execute all transfers with one click

## Supported File Types

- Antlers templates (`.antlers.html`)
- Vue components (`.vue`)
- JavaScript files (`.js`)
- YAML configs (`.yaml`, `.yml`)
- PHP files (`.php`)

## Requirements

- PHP 8.1+
- [Laravel Herd](https://herd.laravel.com/) (or any local Laravel development environment)
- Composer

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/cpadierna/statamic-transfer.git
   cd statamic-transfer
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate the app key:
   ```bash
   php artisan key:generate
   ```

5. Update `.env` to use file sessions (no database required):
   ```
   SESSION_DRIVER=file
   ```

6. Point Herd at the project (or add to your Herd paths), then visit `http://statamic-transfer.test`

## Configuration

By default, the tool scans for projects in:
- `~/Herd`
- `~/Documents/GitHub`

To add additional paths, edit the `$herdPaths` array in `app/Http/Controllers/TransferController.php`.

## Usage

1. **Select Original Project** - Choose the project you want to copy components from
2. **Select New Project** - Choose the destination project
3. **Click files** to add them to the transfer queue
4. **Customize** labels, destination paths, and filenames as needed
5. **Execute Transfer** to copy all queued files

## Limitations

- Currently only supports Statamic-to-Statamic transfers
- Does not detect or transfer file dependencies (partials, imports)—planned for v1.1
- Browser-based, so no native file picker (uses project dropdowns instead)

## Roadmap

- [ ] Dependency scanning (detect partials and imports)
- [ ] File preview before transfer

## License

MIT
