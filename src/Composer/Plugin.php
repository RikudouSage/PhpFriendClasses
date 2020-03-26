<?php

namespace Rikudou\FriendClasses\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @internal
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var bool
     */
    private $isUninstalling = false;

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'registerAutoloader',
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'handleUninstall',
        ];
    }

    public function registerAutoloader(Event $event)
    {

        $config = new AutoloaderConfig();
        $config->devMode = !($event->getFlags()['optimize'] ?? false);
        $config->preload = !$config->devMode
            && ($this->composer->getPackage()->getExtra()['friendClasses']['preload'] ?? true);

        $dir = $this->composer->getConfig()->get('vendor-dir');
        if (!file_exists("${dir}/composer/friend-classes")) {
            mkdir("${dir}/composer/friend-classes", 0777, true);
        } else {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    "${dir}/composer/friend-classes"
                )
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    unlink($file->getRealPath());
                }
            }
        }

        if ($this->isUninstalling) {
            rmdir("${dir}/composer/friend-classes");
            if (file_exists("${dir}/composer_autoload.php")) {
                unlink("${dir}/composer_autoload.php");
            }
            return;
        }

        $exportedConfig = var_export($config, true);
        $content = <<<AUTOLOADER
        <?php
        
        \$autoloader = require '${dir}/composer_autoload.php';
        
        \$config = $exportedConfig;
        
        \$newAutoloader = new \Rikudou\FriendClasses\Composer\Autoloader(\$autoloader, '${dir}', \$config);
        \$autoloader->unregister();
        \$newAutoloader->register(true);
        
        foreach (get_declared_classes() as \$class) {
            if (strpos(\$class, "ComposerAutoloaderInit") === 0) {
                \$reflection = new ReflectionProperty(\$class, "loader");
                \$reflection->setAccessible(true);
                \$reflection->setValue(\$newAutoloader);
            }
        }
        
        return \$newAutoloader;
        AUTOLOADER;

        rename("${dir}/autoload.php", "${dir}/composer_autoload.php");
        file_put_contents("${dir}/autoload.php", $content);

        if ($config->preload) {
            $this->preload($dir);
        }
    }

    public function handleUninstall(PackageEvent $event)
    {
        $operation = $event->getOperation();
        if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
            if ($package->getName() === 'rikudou/friend-classes') {
                $this->isUninstalling = true;
            }
        }
    }

    private function preload(string $vendorDir)
    {
        $classMapFile = "${vendorDir}/composer/autoload_classmap.php";
        if (file_exists($classMapFile)) {
            /** @var Autoloader $autoloader */
            $autoloader = require "${vendorDir}/autoload.php";
            $classMap = require $classMapFile;
            foreach ($classMap as $class => $file) {
                if (
                    class_exists($class, false)
                    || interface_exists($class, false)
                    || trait_exists($class, false)
                ) {
                    continue;
                }
                $autoloader->loadClass($class, false);
            }
        }
    }
}
