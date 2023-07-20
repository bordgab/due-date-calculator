<?php

namespace App\DateTime;

final class TimeRange implements TimeInterval
{
    protected \DateTimeImmutable $start;
    protected \DateTimeImmutable $end;

    const MAX_VALUE = '2999/01/01 00:00:00';
    const MIN_VALUE = '1970/01/01 01:00:00';

    public function __construct(string|\DateTimeInterface $start = self::MIN_VALUE, string|\DateTimeInterface $end = self::MAX_VALUE)
    {
        $this->start = self::createDateTimeImmutable($start);
        $this->end = self::createDateTimeImmutable($end);

        if ($this->start > $this->end) {
            throw new \InvalidArgumentException('End must be greater than or equal than start.');
        }
    }

    /**
     * Convenience method to instantiate new TimeRange object.
     */
    public static function create($start = self::MIN_VALUE, $end = self::MAX_VALUE): self
    {
        return new self($start, $end);
    }

    public static function createMoment(\DateTimeInterface $time = null): self
    {
        if (null === $time) {
            $time = self::createDateTimeImmutable($time);
        }

        return new self($time, $time);
    }

    public function isMoment(): bool
    {
        return $this->start == $this->end;
    }

    private static function createDateTimeImmutable(string|\DateTimeInterface $value): \DateTimeImmutable
    {
        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }
        elseif ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        else {
            throw new \InvalidArgumentException('Value must be an instance of \DateTimeInterface or a convertable string literal, given '.\is_object($value)?\get_class($value):gettype($value));
        }
    }

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function getDuration(): TimeSpan
    {
        $timeInterval = $this->end->diff($this->start, true/*absolute*/);

        return new TimeSpan($timeInterval->format(TimeSpan::INTERVAL_ISO8601));
    }

    public function expandStart(TimeSpan $span): self
    {
        $start = \DateTime::createFromImmutable($this->start);
        $start->sub($span);

        return new self($start, $this->end);
    }

    public function expandEnd(TimeSpan $span): self
    {
        $end = \DateTime::createFromImmutable($this->end);
        $end->add($span);

        return new self($this->start, $end);
    }

    public function shrinkStart(TimeSpan $span): self
    {
        $start = \DateTime::createFromImmutable($this->start);
        $start->add($span);

        return new self($start, $this->end);
    }

    public function shrinkEnd(TimeSpan $span): self
    {
        $end = \DateTime::createFromImmutable($this->end);
        $end->sub($span);

        return new self($this->start, $end);
    }

    public function contains(TimeInterval $interval): bool
    {
        return $this->start <= $interval->getStart() && $interval->getEnd() <= $this->end;
    }

    public function __clone()
    {
        $this->start = clone $this->start;
        $this->end = clone $this->end;
    }
}
