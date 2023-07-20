<?php

namespace App;

use App\DateTime\TimeRange;
use App\DateTime\TimeSpan;
use App\Model\Issue;

class DueDateCalculator
{
    public function __construct(private readonly WorkdaysConfiguration $workdaysConfiguration)
    {
    }

    public function calculateDueDate(Issue $issue): \DateTimeInterface
    {
        $issueDate = $issue->getDate();
        $minutesLeft = $issue->getLeadTimeInHours()*60;

        $timeWindow = new TimeRange($issueDate, $this->workdaysConfiguration->getWorkingDay($issueDate)->getEnd());

        while (0 < $minutesLeft) {
            $duration = $timeWindow->getDuration();
            $minutesSubtract = min($minutesLeft, $duration->getHours()*60+$duration->getMinutes());
            $minutesLeft -= $minutesSubtract;
            $timeWindow = $timeWindow->shrinkStart(new TimeSpan('PT'.$minutesSubtract.'M'));

            if (0 >= $minutesLeft) {
                break;
            }

            $timeWindow = $this->workdaysConfiguration->getNextWorkingDay($timeWindow->getEnd());
        }

        return $timeWindow->getStart();
    }
}
