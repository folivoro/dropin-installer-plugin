<?php

declare(strict_types=1);

namespace Folivoro\ComposerDropinInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

/**
 * Dropin Installer Composer Plugin.
 *
 * Copies a single file from any installed package into a target directory
 * resolved from the root package's installer-paths configuration.
 *
 * ## Configuration
 *
 * In the package that wants to install a dropin, add to composer.json:
 *
 * ```json
 * {
 *     "extra": {
 *         "dropin": {
 *             "file": "cecropia.php",
 *             "target-type": "wordpress-muplugin",
 *             "target-dir": "cecropia"
 *         }
 *     }
 * }
 * ```
 *
 * - `file`        — filename to copy from the package root (required)
 * - `target-type` — installer-paths type to resolve the target directory (required)
 * - `target-dir`  — subdirectory within the target path (optional, defaults to package name)
 *
 * ## How it works
 *
 * On `pre-autoload-dump`, iterates all installed packages and checks if they
 * have a `extra.dropin` configuration. For each, resolves the target directory
 * from the root package's `installer-paths` and copies the file there.
 *
 * On uninstall, removes the copied file.
 *
 * @since 1.0.0
 */
class DropinInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'pre-autoload-dump' => 'installDropins',
        ];
    }

    /**
     * Install all dropin files from packages that declare extra.dropin.
     */
    public function installDropins(Event $event): void
    {
        $packages = $this->composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages();

        foreach ($packages as $package) {
            $dropin = $package->getExtra()['dropin'] ?? null;

            if ($dropin === null) {
                continue;
            }

            $this->installDropin($package, $dropin);
        }
    }

    /**
     * Install a single dropin file from a package.
     *
     * @param PackageInterface     $package The package declaring the dropin.
     * @param array<string, mixed> $dropin  The dropin configuration from extra.dropin.
     */
    private function installDropin(PackageInterface $package, array $dropin): void
    {
        $file       = $dropin['file'] ?? null;
        $targetType = $dropin['target-type'] ?? null;
        $targetDir  = $dropin['target-dir'] ?? null;

        if ($file === null || $targetType === null) {
            $this->io->writeError(
                "<warning>Dropin: {$package->getName()} has invalid dropin config — 'file' and 'target-type' are required.</warning>"
            );

            return;
        }

        $targetPath = $this->resolveTargetPath($targetType, $file, $targetDir);

        if ($targetPath === null) {
            $this->io->writeError(
                "<warning>Dropin: Could not resolve installer-path for type '{$targetType}'.</warning>"
            );

            return;
        }

        $sourcePath = $this->resolvePackagePath($package) . DIRECTORY_SEPARATOR . $file;

        if (!file_exists($sourcePath)) {
            $this->io->writeError(
                "<warning>Dropin: Source file '{$sourcePath}' not found in {$package->getName()}.</warning>"
            );

            return;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        copy($sourcePath, $targetPath);

        $this->io->write(
            "<info>Dropin: {$package->getName()}/{$file} → {$targetPath}</info>"
        );
    }

    /**
     * Resolve the absolute target path from installer-paths.
     *
     * @param string      $targetType The installer-paths type (e.g. 'wordpress-muplugin').
     * @param string      $file       The filename to copy.
     * @param string|null $targetDir  Optional subdirectory within the resolved path.
     */
    private function resolveTargetPath(string $targetType, string $file, ?string $targetDir): ?string
    {
        $extra          = $this->composer->getPackage()->getExtra();
        $installerPaths = $extra['installer-paths'] ?? [];

        foreach ($installerPaths as $path => $conditions) {
            if (!in_array("type:{$targetType}", $conditions, true)) {
                continue;
            }

            // Strip {$name} placeholder and trailing slashes
            $dir = rtrim(str_replace('{$name}', $targetDir ?? '', $path), '/\\');
            $dir = rtrim($dir, '/\\');

            return $this->resolveProjectRoot()
                . DIRECTORY_SEPARATOR
                . $dir
                . DIRECTORY_SEPARATOR
                . $file;
        }

        return null;
    }

    /**
     * Resolve the absolute path where a package is installed.
     *
     * @param PackageInterface $package The installed package.
     */
    private function resolvePackagePath(PackageInterface $package): string
    {
        return $this->composer
            ->getInstallationManager()
            ->getInstallPath($package);
    }

    /**
     * Resolve the project root from the vendor-dir config.
     */
    private function resolveProjectRoot(): string
    {
        return dirname($this->composer->getConfig()->get('vendor-dir'));
    }
}
