<?php

namespace App;

use App\DateTime\TimeRange;

class WorkdaysConfiguration
{
    public function __construct(private readonly array $workingHours)
    {
    }

    public function isWorkingDay(\DateTimeInterface $date): bool
    {
        $day = $date->format('N')-1;

        $boundaries = $this->workingHours[$day]??null;

        if (null === $boundaries || null === $boundaries[0]??null) {
            return false;
        }

        return true;
    }

    /**
     * @throws \RuntimeException  If given date is not a working day
     */
    public function getWorkingDay(\DateTimeInterface $date): TimeRange
    {
        $boundaries = $this->getWorkingHoursBoundaries($date->format('N')-1);

        if (null === $boundaries) {
            throw new \RuntimeException(\sprintf('Date %s is not a working day!', $date->format('Y-m-d')));
        }

        return new TimeRange(
            \DateTimeImmutable::createFromFormat('Y-m-d H:i', \sprintf('%s %s', $date->format('Y-m-d'), $boundaries[0])),
            \DateTimeImmutable::createFromFormat('Y-m-d H:i', \sprintf('%s %s', $date->format('Y-m-d'), $boundaries[1]))
        );
    }

    /**
     * @return int Zero based index, week begins with Monday (eg. 0=Monday, 1=Thuesday, and so on...)
     */
    public function getNextWorkingDay(\DateTimeInterface $date): TimeRange
    {
        $day = $nextDay = $date->format('N')-1;

        $date = \DateTime::createFromInterface($date);

        do {
            $nextDay = ($nextDay+1) % 7;

            $boundaries = $this->workingHours[$nextDay]??null;

            $nextDayIsWorkingDay = null !== $boundaries && null !== $boundaries[0]??null;

            $date->add(new \DateInterval('P1D'));
        } while (!$nextDayIsWorkingDay && $day != $nextDay);

        return $this->getWorkingDay($date);
    }

    /**
     * @param int $day  Zero based index, weeks begins with Monday (eg. 0=Monday, 1=Thuesday, and so on...)
     * @return null|array<string> Return null if given day is not a working day
     */
    private function getWorkingHoursBoundaries(int $day): null|array
    {
        $boundaries = $this->workingHours[$day]??null;

        if (null === $boundaries || null === $boundaries[0]??null) {
            return null;
        }

        if (!preg_match('/[0-9]{1,2}\:[0-9]{1,2}/', $boundaries[0])
                || !preg_match('/[0-9]{1,2}\:[0-9]{1,2}/', $boundaries[1])) {
            throw new \RuntimeException('Invalid working time configuration!');
        }

        return $boundaries;
    }
}
