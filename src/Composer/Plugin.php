<?php

namespace Rikudou\FriendClasses\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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
        ];
    }

    public function registerAutoloader(Event $event)
    {
        $devMode = !($event->getFlags()['optimize'] ?? false) ? 'true' : 'false';

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

        $content = <<<AUTOLOADER
        <?php
        
        \$autoloader = require '${dir}/composer_autoload.php';
        \$newAutoloader = new \Rikudou\FriendClasses\Composer\Autoloader(\$autoloader, '${dir}', $devMode);
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
    }
}
