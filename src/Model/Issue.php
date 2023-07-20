<?php

namespace App\Model;

class Issue
{
    public function __construct(
        private readonly \DateTimeImmutable $date,
        private readonly int $leadTimeInHours
    ) {
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getLeadTimeInHours(): int
    {
        return $this->leadTimeInHours;
    }
}
