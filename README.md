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

A Composer plugin that copies a single file from any installed package into a target directory resolved from the root package's `installer-paths` configuration.

## Why

Some packages need to place a single file at a specific location in the project — for example, a WordPress MU-plugin bootstrapper. The standard `composer/installers` approach installs an entire package directory. This plugin does one thing: copies a single declared file to the right place.

## Installation

```bash
composer require folivoro/composer-dropin-installer
```

## Usage

In any package that wants to install a dropin file, add to `composer.json`:

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

The root project must have `installer-paths` configured for the target type:

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

```
public/extensions/components/my-plugin/my-plugin.php
```

## Configuration

| Key           | Required | Description                                                                 |
|---------------|----------|-----------------------------------------------------------------------------|
| `file`        | ✅        | Filename to copy from the package root                                      |
| `target-type` | ✅        | The `installer-paths` type to resolve the target directory                  |
| `target-dir`  | ❌        | Subdirectory name within the resolved path. Defaults to the package name.   |

## How it works

On `pre-autoload-dump`, the plugin iterates all installed packages and checks for `extra.dropin` configuration. For each match, it resolves the target directory from the root package's `installer-paths` and copies the file. On `uninstall`, the copied file is removed.

## License

MIT
