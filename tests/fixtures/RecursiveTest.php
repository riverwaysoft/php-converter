<?php

class User
{
    public string $id;
    public ?User $bestFriend;
    /** @var User[] */
    public array $friends;
}
