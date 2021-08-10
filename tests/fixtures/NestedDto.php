<?php

#[\Attribute]
class Dto
{
}

#[Dto]
class FullName
{
    public string $firstName;
    public string $lastName;
}

#[Dto]
class Profile
{
    public ?FullName $name;
    public int $age;
}

#[Dto]
class UserCreate
{
    public string $id;
    public Profile|null $profile;
}
