<?php


namespace datagutten\amb\laps;


use datagutten\tools\SimpleArrayAccess;
use DateTimeImmutable;

class Passing extends SimpleArrayAccess
{
    /**
     * @var int Transponder number
     */
    public int $transponder_num;
    /**
     * Passing time
     */
    public DateTimeImmutable $time;
    /**
     * @var int Passing number
     */
    public int $number;
    /**
     * @var int Passing RTC time in microseconds
     */
    public int $timestamp;
    /**
     * @var int Signal strength
     */
    public int $strength;
    public int $hits;
    public int $flags;
    /**
     * @var string Decoder id
     */
    public string $decoder;

    function __construct(array $passing)
    {
        $this->time = self::parse_time($passing['rtc_time']);
        $this->timestamp = $passing['rtc_time'];
        $this->number = $passing['passing_number'];
        $this->transponder_num = $passing['transponder'];
        $this->strength = $passing['strength'];
        $this->hits = $passing['hits'];
        $this->flags = $passing['flags'];
        $this->decoder = $passing['decoder_id'];
    }

    public static function parse_time($timestamp): DateTimeImmutable
    {
        $timestamp = lap_timing::convert_time($timestamp);
        $time = new DateTimeImmutable();
        return $time->setTimestamp($timestamp);
    }

    public function format_time(string $format = 'Y-m-d H:i'): string
    {
        return $this->time->format($format);
    }
}