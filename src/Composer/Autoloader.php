<?php

namespace Rikudou\FriendClasses\Composer;

use Composer\Autoload\ClassLoader;
use function Composer\Autoload\includeFile;

/**
 * @internal
 */
final class Autoloader extends ClassLoader
{
    /**
     * @var string
     */
    private string $vendorDir;

    /**
     * @var AutoloaderConfig
     */
    private AutoloaderConfig $config;

    public function __construct(ClassLoader $originalLoader, string $vendorDir, AutoloaderConfig $config)
    {
        parent::__construct($vendorDir);
        $this->add('', $originalLoader->getFallbackDirs());
        $this->addPsr4('', $originalLoader->getFallbackDirsPsr4());
        foreach ($originalLoader->getPrefixes() as $prefix => $path) {
            $this->add($prefix, $path);
        }
        foreach ($originalLoader->getPrefixesPsr4() as $prefix => $path) {
            $this->addPsr4($prefix, $path);
        }
        $this->setUseIncludePath($originalLoader->getUseIncludePath());

        $this->vendorDir = $vendorDir;
        $this->config = $config;
    }

    public function loadClass($class, bool $preloadedOnly = null): ?bool
    {
        if ($preloadedOnly === null) {
            $preloadedOnly = $this->config->preload;
        }
        if (!$this->config->devMode && file_exists($this->getHashedFileName($class))) {
            includeFile($this->getHashedFileName($class));

            return true;
        }

        if (!$preloadedOnly && $file = $this->findFile($class)) {
            $content = file_get_contents($file);
            assert(is_string($content));
            if (
                str_contains($content, '@FriendClass')
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

        $traitsLine = "\n\n";
        foreach ($this->config->traits as $trait) {
            $traitsLine .= "use \\${trait};\n";
        }

        $lines = explode(PHP_EOL, $content);
        $lines[$line] = $traitsLine . $lines[$line];

        file_put_contents($this->getHashedFileName($class), implode(PHP_EOL, $lines));

        return true;
    }
}
