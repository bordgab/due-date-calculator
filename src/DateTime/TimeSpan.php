<?php

namespace App\DateTime;

class TimeSpan extends \DateInterval
{
    const INTERVAL_ISO8601 = 'P%YY%mM%dDT%HH%iM%sS';

    public function getMonths(): int
    {
        return $this->m;
    }

    public function getDays(): int
    {
        return $this->d;
    }

    public function getHours(): int
    {
        return $this->h;
    }

    public function getMinutes(): int
    {
        return $this->i;
    }

    public function getSeconds(): int
    {
        return $this->s;
    }

    public function equals(TimeSpan $timeSpan): bool
    {
        return $this->format(self::INTERVAL_ISO8601) === $timeSpan->format(self::INTERVAL_ISO8601);
    }
}
