<?php

class FullName
{
    public string $firstName;
    public string $lastName;
}

class Profile
{
    public ?FullName $name;
    public int $age;
}

class UserCreate
{
    public string $id;
    public Profile|null $profile;
}
