<?php

namespace App\DateTime;

interface TimeInterval
{
    public function getStart(): \DateTimeInterface;
    public function getEnd(): \DateTimeInterface;
}
