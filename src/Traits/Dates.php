<?php
declare(strict_types=1);

namespace Simplex\Traits;

trait Dates {
    
    /**
     * formats a date from locale format (as specified into language definition) to English format YYYY-MM-DD
     * useful to format user input for saving to database
     * @param string $dateLocale
     */
    protected function formatDateLocaleToEn($dateLocale)
    {
        return \DateTime::createFromFormat($this->language->dateFormat->PHP, $dateLocale)->format('Y-m-d');
    }
    
    /**
     * Turns a seconds value into a time duration
     * @param int $seconds
     */
    protected function secondsToTime(int $seconds, bool $displaySeconds = false): string
    {
      $zero    = new \DateTime("@0");
      $offset  = new \DateTime("@$seconds");
      $diff    = $zero->diff($offset);
      $format = '%02d:%02d';
      $timeSeconds = null;
      if($displaySeconds) {
        $format .= ':%02d';
        $timeSeconds = $diff->s;
      }
      return sprintf($format, $diff->days * 24 + $diff->h, $diff->i, $timeSeconds);
    }
    
    /**
     * Checks whether two date intervals overlaps and returns the number of overlapping days if any
     * @param string $interval1StartDate YYYY-MM-DD
     * @param string $interval1StopDate YYYY-MM-DD
     * @param string $interval2StartDate YYYY-MM-DD
     * @param string $interval2StopDate YYYY-MM-DD
     * @return int number of overlapping days
     */
    protected function intervalsOverlap(
        string $interval1StartDate,
        string $interval1StopDate,
        string $interval2StartDate,
        string $interval2StopDate
    )
    {
        $interval1StartDate = new \DateTime($interval1StartDate);
        $interval1StopDate = new \DateTime($interval1StopDate);
        $interval2StartDate = new \DateTime($interval2StartDate);
        $interval2StopDate = new \DateTime($interval2StopDate);
        if($interval1StartDate <= $interval2StopDate && $interval1StopDate >= $interval2StartDate) {
            return min($interval1StopDate, $interval2StopDate)->diff(max($interval2StartDate, $interval1StartDate))->days + 1;
        }
        return 0;
    }
}
