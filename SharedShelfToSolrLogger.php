<?php

class SharedShelfToSolrLogger
{
    private $log_file = '';
    private $task = 'unknown';
    private $task_start = false;
    private $item = 'unknown';
    private $item_start = false;
    private $messages = array();
    private $timezone = '';

    public function __construct($logfile_prefix)
    {
        $this->timezone = new DateTimeZone('America/New_York');
        $dt = new DateTime('NOW', $this->timezone);
        $this->log_file = __DIR__ . '/logs' . $logfile_prefix.$dt->format('-Y-m-d-H-i-s').'.log';
        if (!file_exists($this->log_file)) {
            if (false === touch($this->log_file)) {
                throw new Exception('Log file must exist: '.$this->log_file, 1);
            }
        }
    }

    public function log_file_name()
    {
        return $this->log_file;
    }

    public function task($taskname)
    {
        $this->end_item();
        $this->end_task();
        $this->write();
        $this->task = $taskname;
        $this->task_start = microtime(true);  // floating point version as of php 5
    }

    private function end_task()
    {
        if (false != $this->task_start) {
            $elapsed = microtime(true) - $this->task_start;
            $elapsed = $this->sec2hms($elapsed, true);
            $this->note("TaskTook: $elapsed");
            $this->task_start = false;
        }
    }

    public function item($itemname)
    {
        $this->end_item();
        $this->write();
        $this->item = $itemname;
        $this->item_start = microtime(true);  // floating point version as of php 5
    }

    private function end_item()
    {
        if (false != $this->item_start) {
            $elapsed = microtime(true) - $this->item_start;
            $elapsed = $this->sec2hms($elapsed, true);
            $this->note("ItemTook: $elapsed");
            $this->item_start = false;
        }
    }

    public function error($error_message)
    {
        $this->end_item();
        $this->end_task();
        $this->write();
        $this->note("ERROR: $error_message");
        $this->write();
    }

    public function note($text)
    {
        $this->messages[] = $text;
    }

    public function write()
    {
        $timestamp = new DateTime('NOW', $this->timezone);
        $texts = array();
        $texts[] = $timestamp->format('c');
        $texts[] = 'Task:'.$this->task;
        $texts[] = 'Item:'.$this->item;
        $texts = array_merge($texts, $this->messages);
        $this->log(implode(' | ', $texts));
        $this->messages = array();
    }

    private function log($text)
    {
        $bytes = file_put_contents($this->log_file, $text.PHP_EOL, FILE_APPEND);
        if (false === $bytes) {
            throw new Exception("Can't write to log file: ".$this->log_file, 1);
        }
    }

    public function sec2hms($sec, $padHours = false)
    {
        // http://www.laughing-buddha.net/php/sec2hms/
        // start with a blank string
        $hms = '';

        // do the hours first: there are 3600 seconds in an hour, so if we divide
        // the total number of seconds by 3600 and throw away the remainder, we're
        // left with the number of hours in those seconds
        $hours = intval(intval($sec) / 3600);

        // add hours to $hms (with a leading 0 if asked for)
        $hms .= ($padHours)
          ? str_pad($hours, 2, '0', STR_PAD_LEFT).':'
          : $hours.':';

        // dividing the total seconds by 60 will give us the number of minutes
        // in total, but we're interested in *minutes past the hour* and to get
        // this, we have to divide by 60 again and then use the remainder
        $minutes = intval(($sec / 60) % 60);

        // add minutes to $hms (with a leading 0 if needed)
        $hms .= str_pad($minutes, 2, '0', STR_PAD_LEFT).':';

        // seconds past the minute are found by dividing the total number of seconds
        // by 60 and using the remainder
        $seconds = intval($sec % 60);

        // add seconds to $hms (with a leading 0 if needed)
        $hms .= str_pad($seconds, 2, '0', STR_PAD_LEFT);

        // get two decimal digits from the seconds
        $dec = number_format((float) $sec, 2, '.', '');
        $dec = substr($dec, strpos($dec, '.'));
        $hms .= $dec;

        // done!
        return $hms;
    }
}
