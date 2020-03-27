<?php


namespace Rikudou\FriendClasses\Traits;


use ReflectionObject;

/**
 * @internal
 */
trait FriendsTraitCommonDefinitions
{
    private $_friends_Config = [
        'parsed' => false,
        'classes' => [],
        'currentClass' => ''
    ];

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
