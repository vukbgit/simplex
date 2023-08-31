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
    
    /**
     * Gets a locale month name 
     * @param int $monthIndex
     */
    protected function getLocaleMonthName(int $monthIndex): string
    {
      $fmt = new \IntlDateFormatter(
        null,
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::FULL,
        null,
        null,
        'MMMM'
      );
      return $fmt->format(['tm_mon' => $monthIndex]);
    }
    
    /**
     * Gets locale months names indexed by month index into an array
     */
    protected function getLocaleMonthNames(): array
    {
      $months = [];
      for ($i=1; $i <=12 ; $i++) {
        $months[$i] = $this->getLocaleMonthName($i);
      }
      return $months;
    }
    
    /**
     * Gets a locale week day name 
     * @param int $weekDayIndex from 0 = sunday to 6 = saturday
     * @param string $format:
     *                  E = 3 letters short (i.e. Sun)
     *                  EEEE = full (i.e. Sunday)
     *                  EEEEE = 1 letters short (i.e. S)
     *                  EEEEEE = 2 letters short (i.e. Su)
     */
    protected function getLocaleWeekDayName(int $weekDayIndex, $format = 'EEEE'): string
    {
      $weekDaysIndexes = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
      ];
      $date = new \DateTime('next ' . $weekDaysIndexes[$weekDayIndex]);
      $fmt = new \IntlDateFormatter(
        null,
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::FULL,
        null,
        null,
        $format
      );
      return $fmt->format($date);
    }
    
    /**
     * Gets locale week days names indexed by week day index into an array
     * @param int $sundayIndex
     * @param int $firstIndex index of first day, usually 0 or 1
     * @param string $format:
     *                  E|EE|EEE = 3 letters short (i.e. Sun)
     *                  EEEE = full (i.e. Sunday)
     *                  EEEEE = 1 letters short (i.e. S)
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
     */
    protected function getLocaleWeekDaysNames($sundayIndex = 0, $firstIndex = 0, $format = 'EEEE'): array
    {
      $weekDays = [];
      for ($innerIndex = 0; $innerIndex <= 6 ; $innerIndex++) {
        $returnIndex = $innerIndex + $sundayIndex;
        if($returnIndex > (6 + $firstIndex)) {
          $returnIndex = $returnIndex - (6 + $firstIndex);
        }
        $weekDays[$returnIndex] = $this->getLocaleWeekDayName($innerIndex, $format);
      }
      ksort($weekDays);
      return $weekDays;
    }
}
