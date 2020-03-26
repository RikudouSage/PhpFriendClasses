<?php

namespace Rikudou\FriendClasses\Composer;

use Composer\Autoload\ClassLoader;
use function Composer\Autoload\includeFile;

/**
 * @internal
 */
class Autoloader extends ClassLoader
{
    /**
     * @var string
     */
    private $vendorDir;

    /**
     * @var bool
     */
    private $devMode;

    public function __construct(ClassLoader $originalLoader, string $vendorDir, bool $devMode)
    {
        $this->add(null, $originalLoader->getFallbackDirs());
        $this->addPsr4(null, $originalLoader->getFallbackDirsPsr4());
        foreach ($originalLoader->getPrefixes() as $prefix => $path) {
            $this->add($prefix, $path);
        }
        foreach ($originalLoader->getPrefixesPsr4() as $prefix => $path) {
            $this->addPsr4($prefix, $path);
        }
        $this->setUseIncludePath($originalLoader->getUseIncludePath());

        $this->vendorDir = $vendorDir;
        $this->devMode = $devMode;
    }

    public function loadClass($class)
    {
        if (!$this->devMode && file_exists($this->getHashedFileName($class))) {
            includeFile($this->getHashedFileName($class));
            return true;
        }

        if ($file = $this->findFile($class)) {
            $content = file_get_contents($file);
            if (
                strpos($content, '@FriendClass') !== false
                && $this->createClass($class, $content)
            ) {
                includeFile($this->getHashedFileName($class));

                return true;
            }
        }

        return parent::loadClass($class);
    }

    private function getHashedFileName(string $class): string
    {
        $hash = md5($class);
        return "{$this->vendorDir}/composer/friend-classes/${hash}.php";
    }

    private function createClass(string $class, string $content): bool
    {
        $tokens = token_get_all($content);

        $line = null;
        $foundClass = false;
        foreach ($tokens as $index => $token) {
            if (!$foundClass && !is_array($token)) {
                continue;
            }

            if (!$foundClass && $token[0] !== T_CLASS) {
                continue;
            }

            $foundClass = true;

            if ($token === '{') {
                $i = $index;
                while (!is_array($tokens[$i])) {
                    ++$i;
                }

                $line = $tokens[$i][2];
                break;
            }
        }

        if ($line === null) {
            return false;
        }

        $lines = explode(PHP_EOL, $content);
        $lines[$line] = "\n\nuse \Rikudou\FriendClasses\FriendsTrait;\n" . $lines[$line];

        file_put_contents($this->getHashedFileName($class), implode(PHP_EOL, $lines));

        return true;
    }
}
