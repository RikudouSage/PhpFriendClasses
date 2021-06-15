<?php

namespace Rikudou\FriendClasses\Composer;

use JetBrains\PhpStorm\Pure;

/**
 * @internal
 */
final class AutoloaderConfig
{
    /**
     * @var bool
     */
    public bool $devMode = false;

    /**
     * @var bool
     */
    public bool $preload = false;

    /**
     * @var string[]
     */
    public array $traits = [];

    /**
     * @param array<string,mixed> $state
     */
    #[Pure]
    public static function __set_state(array $state): self
    {
        $instance = new self();
        foreach ($state as $key => $value) {
            $instance->{$key} = $value;
        }

        return $instance;
    }
}
