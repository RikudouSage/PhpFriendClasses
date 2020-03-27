<?php

namespace Rikudou\FriendClasses\Composer;

/**
 * @internal
 */
final class AutoloaderConfig
{
    /**
     * @var bool
     */
    public $devMode;

    /**
     * @var bool
     */
    public $preload;

    /**
     * @var string[]
     */
    public $traits = [];

    /**
     * @param array<mixed> $state
     * @return AutoloaderConfig
     */
    public static function __set_state(array $state)
    {
        $instance = new self();
        foreach ($state as $key => $value) {
            $instance->{$key} = $value;
        }

        return $instance;
    }
}
