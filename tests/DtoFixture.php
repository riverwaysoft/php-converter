<?php

#[\Attribute]
class Dto {}

#[Dto]
class DtoFixture {
    public int $age;
    public string $name;
    public float $latitude;
    public float $longitude;
    public array $achievements;
    public mixed $mixed;
    public bool|null $isApproved;
}