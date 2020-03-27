<?php


namespace Rikudou\FriendClasses\Traits;


use Error;

/**
 * @internal
 */
trait FriendsTraitPropertiesDefinition
{
    public function __get($name)
    {
        $this->_friends_Parse();

        if (!property_exists($this, $name)) {
            trigger_error(
                sprintf('Undefined property: %s::$%s', $this->_friends_Config['currentClass'], $name),
                E_USER_NOTICE
            );
            return null;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (
            !isset($trace[1])
            || !isset($trace[1]['class'])
            || !in_array($trace[1]['class'], $this->_friends_Config['classes'], true)
        ) {
            throw new Error(
                sprintf('Cannot access private property %s::$%s', $this->_friends_Config['currentClass'], $name)
            );
        }

        return $this->$name;
    }
}
