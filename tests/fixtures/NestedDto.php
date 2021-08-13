<?php

use MyCLabs\Enum\Enum;

final class PermissionsEnum extends Enum
{
    private const VIEW = 'view';
    private const EDIT = 'edit';
}

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
    public PermissionsEnum $permissions;
    public Profile|null $profile;
    public int $age;
    public ?string $name;
    public float $latitude;
    public float $longitude;
    public array $achievements;
    public mixed $mixed;
    public bool|null $isApproved;
}
