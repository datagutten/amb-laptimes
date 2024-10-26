<?php

namespace datagutten\amb\laps;

use datagutten\tools\SimpleArrayAccess;
use DateTime;

class Lap extends SimpleArrayAccess
{
    public float $lap_time;
    /**
     * @var int Transponder number
     */
    public int $transponder_num;
    /**
     * @var int Passing number for the first passing
     */
    public int $start_number;
    /**
     * @var int Passing number for the last passing
     */
    public int $end_number;
    /**
     * @var string CSS class
     */
    public string $class;
    public float $best_time;
    public DateTime $start_time;
    public DateTime $end_time;

    function __construct($info)
    {
        $this->lap_time = $info['lap_time'];
        $this->transponder_num = $info['transponder'];

        $this->start_time = DateTime::createFromFormat('U', $info['start_time']);
        $this->end_time = DateTime::createFromFormat('U', $info['end_time']);

        $this->start_number = $info['start_number'];
        $this->end_number = $info['end_number'];
        $this->class = $info['class'] ?? '';
        if (!empty($info['best-time']))
            $this->best_time = $info['best-time'];
    }
}