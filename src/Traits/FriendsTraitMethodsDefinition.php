<?php

namespace Rikudou\FriendClasses\Traits;

use Error;

/**
 * @internal
 */
trait FriendsTraitMethodsDefinition
{
    public function __call($name, $arguments)
    {
        $this->_friends_Parse();

        if (!method_exists($this, $name)) {
            throw new Error(
                sprintf('Call to undefined method %s::%s()', $this->_friends_Config['currentClass'], $name)
            );
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (
            !isset($trace[1])
            || !isset($trace[1]['class'])
            || !in_array($trace[1]['class'], $this->_friends_Config['classes'], true)
        ) {
            throw new Error(
                sprintf(
                    "Call to private method %s::%s() from context '%s'",
                    $this->_friends_Config['currentClass'],
                    $name,
                    $trace[1]['class'] ?? ''
                )
            );
        }

        return call_user_func([$this, $name], ...$arguments);
    }
}
