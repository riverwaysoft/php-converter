<?php

declare(strict_types=1);

class User
{
    public string $id;
    public ?User $bestFriend;
    /** @var User[] */
    public array $friends;

    public self $selfProperty;

    public function __construct(
        public self $selfConstructor,
    ) {
    }
}
