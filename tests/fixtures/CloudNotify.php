<?php

#[\Attribute]
class Dto {}

#[Dto]
class CloudNotify {
    public function __construct(public string $id, public string $fcmToken)
    {
    }
}