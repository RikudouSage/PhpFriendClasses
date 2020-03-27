<?php

namespace Rikudou\FriendClasses\Composer;

/**
 * @internal
 */
class AutoloaderConfig
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

    public static function __set_state($state)
    {
        $instance = new self();
        foreach ($state as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }
}
