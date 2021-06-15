<?php

namespace Rikudou\FriendClasses\Traits;

use ReflectionObject;
use Rikudou\FriendClasses\Attribute\FriendClass;

/**
 * @internal
 */
trait FriendsTraitCommonDefinitions
{
    /**
     * @var array<string,mixed>
     */
    private array $_friends_Config = [
        'parsed' => false,
        'classes' => [],
        'currentClass' => '',
        'properties' => [],
        'methods' => [],
    ];

    private function _friends_Parse(): void
    {
        if ($this->_friends_Config['parsed']) {
            return;
        }

        $this->_friends_Config['currentClass'] = get_class($this);

        $reflection = new ReflectionObject($this);
        $attributes = $reflection->getAttributes(FriendClass::class);
        foreach ($attributes as $attribute) {
            $attribute = $attribute->newInstance();
            assert($attribute instanceof FriendClass);
            $this->_friends_Config['classes'][] = $attribute->getClassName();
        }
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(FriendClass::class);
            foreach ($attributes as $attribute) {
                $attribute = $attribute->newInstance();
                assert($attribute instanceof FriendClass);
                $this->_friends_Config['properties'][$property->getName()][] = $attribute->getClassName();
            }
        }
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(FriendClass::class);
            foreach ($attributes as $attribute) {
                $attribute = $attribute->newInstance();
                assert($attribute instanceof FriendClass);
                $this->_friends_Config['methods'][$method->getName()][] = $attribute->getClassName();
            }
        }

        $this->_friends_Config['parsed'] = true;
    }
}
