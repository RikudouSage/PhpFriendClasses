<?php


namespace Rikudou\FriendClasses;


use Error;
use ReflectionObject;

/**
 * @internal
 */
trait FriendsTrait
{
    private $_friends_Config = [
        'parsed' => false,
        'classes' => [],
        'currentClass' => ''
    ];

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

    private function _friends_Parse()
    {
        if ($this->_friends_Config['parsed']) {
            return;
        }

        $this->_friends_Config['currentClass'] = get_class($this);

        $reflection = new ReflectionObject($this);
        $docblock = $reflection->getDocComment();
        if (is_string($docblock)) {
            $this->_friends_Config['classes'] = array_map(function ($line) {
                $line = trim($line, " \t\n\r\0\x0B*");
                $class = str_replace([
                    '@FriendClass(',
                    ')'
                ], '', $line);
                if (strpos($class, '\\') === 0) {
                    $class = substr($class, 1);
                }

                return $class;
            }, array_filter(
                explode(PHP_EOL, $docblock),
                function ($line) {
                    return strpos($line, '@FriendClass') !== false;
                }
            ));
        }

        $this->_friends_Config['parsed'] = true;
    }
}
