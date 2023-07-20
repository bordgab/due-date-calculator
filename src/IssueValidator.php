<?php

namespace App;

use App\DateTime\TimeRange;
use App\Model\Issue;

class IssueValidator
{
    public function __construct(private readonly WorkdaysConfiguration $workdaysConfiguration)
    {
    }

    public function dateIsValid(Issue $issue): bool
    {
        if (!$this->workdaysConfiguration->isWorkingDay($issue->getDate())) {
            return false;
        }

        $workingTime = $this->workdaysConfiguration->getWorkingDay($issue->getDate());

        if (!$workingTime->contains(TimeRange::createMoment($issue->getDate()))) {
            return false;
        }

        return true;
    }
}
