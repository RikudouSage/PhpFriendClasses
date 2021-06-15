<?php

namespace Rikudou\FriendClasses\Attribute;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
final class FriendClass
{
    public function __construct(
        private string $className
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
