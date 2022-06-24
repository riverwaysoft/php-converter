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

class Activity
{
    public string $id;
    public string $createdAt;
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
    /** @phpstan-ignore-next-line */
    public array $achievements;
    /** @var string[] */
    public array $tags;
    /** @var Activity[] */
    public array $activities;
    public mixed $mixed;
    public bool|null $isApproved;
}
