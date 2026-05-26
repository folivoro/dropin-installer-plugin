<p align="center">
<a href="https://folivoro.com" target="_blank">
<img src="https://raw.githubusercontent.com/folivoro/art/refs/heads/main/sloth-logo.svg" alt="Sloth Logo" width="200" height="200" />
</a>
</p>
<p align="center">
<a href="https://packagist.org/packages/folivoro/sloth"><img src="https://img.shields.io/packagist/dt/folivoro/sloth" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/folivoro/sloth"><img src="https://img.shields.io/packagist/v/folivoro/sloth" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/folivoro/sloth"><img src="https://img.shields.io/packagist/l/folivoro/sloth" alt="License"></a>
<a href="https://github.com/folivoro/sloth/actions/workflows/ci.yml"><img src="https://github.com/folivoro/sloth/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
</p>

# Folivoro Composer-Dropin-Installer

A Composer plugin that copies files from any installed package into a target directory — either resolved from the root package's `installer-paths` configuration, or a direct path relative to the project root.

## Why

Some packages need to place a file at a specific location in the project — for example, a WordPress MU-plugin bootstrapper or a shared config file. The standard `composer/installers` approach installs an entire package directory. This plugin does one thing: copies declared files to the right place.

## Installation

```bash
composer require folivoro/composer-dropin-installer
```

## Usage

In any package that wants to install dropin files, add to `composer.json`:

### Single file (via `installer-paths`)

```json
{
    "extra": {
        "dropin": {
            "file": "my-plugin.php",
            "target-type": "wordpress-muplugin",
            "target-dir": "my-plugin"
        }
    }
}
```

### Single file (via direct path)

```json
{
    "extra": {
        "dropin": {
            "file": "pint.json",
            "target-path": "."
        }
    }
}
```

### Multiple files

```json
{
    "extra": {
        "dropins": [
            { "file": "pint.json", "target-path": "." },
            { "file": "phpstan.neon", "target-path": "." },
            { "file": "rector.php", "target-path": "." }
        ]
    }
}
```

The root project must have `installer-paths` configured when using `target-type`:

```json
{
    "extra": {
        "installer-paths": {
            "public/extensions/components/{$name}/": [
                "type:wordpress-muplugin"
            ]
        }
    }
}
```

Given the above, `my-plugin.php` will be copied to:
