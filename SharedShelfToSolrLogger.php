<?php
class SharedShelfToSolrLogger {

  private $log_file = '';
  private $task = 'unknown';
  private $tast_start = FALSE;
  private $item = 'unknown';
  private $item_start = FALSE;
  private $messages = array();
  private $timezone = '';

  function __construct($logfile_prefix) {
    $this->timezone = new DateTimeZone('America/New_York');
    $dt = new DateTime('NOW',  $this->timezone);
    $this->log_file = $logfile_prefix . $dt->format('-Y-m-d') . '.log';
    if (!file_exists($this->log_file)) {
      if (touch($this->log_file) === FALSE) {
        throw new Exception("Log file must exist: " . $this->log_file, 1);
      }
    }
  }

  function task($taskname) {
    $this->end_item();
    $this->end_task();
    $this->write();
    $this->task = $taskname;
    $this->task_start = array_sum( explode( ' ' , microtime() ) );
  }

  private function end_task() {
    if ($this->task_start != FALSE) {
      $elapsed = array_sum( explode( ' ' , microtime() ) ) - $this->task_start;
      $this->note("TaskTook:$elapsed");
      $this->task_start = FALSE;
    }
  }

  function item($itemname) {
    $this->end_item();
    $this->write();
    $this->item = $itemname;
    $this->item_start = array_sum( explode( ' ' , microtime() ) );
  }

  private function end_item() {
    if ($this->item_start != FALSE) {
      $elapsed = array_sum( explode( ' ' , microtime() ) ) - $this->item_start;
      $this->note("ItemTook:$elapsed");
      $this->item_start = FALSE;
    }
  }

  function error($error_message) {
    $this->end_item();
    $this->end_task();
    $this->write();
    $this->note("ERROR: $error_message");
    $this->write();
  }

  function note($text) {
    $this->messages[] = $text;
  }

  function write() {
    $timestamp = new DateTime('NOW', $this->timezone);
    $texts = array();
    $texts[] = $timestamp->format('c');
    $texts[] = 'Task:' . $this->task;
    $texts[] = 'Item:' . $this->item;
    $texts = array_merge($texts, $this->messages);
    $this->log(implode(' | ', $texts));
    $this->messages = array();
  }

  private function log($text) {
    $bytes = file_put_contents($this->log_file, $text . PHP_EOL, FILE_APPEND);
    if ($bytes === FALSE) {
      throw new Exception("Can't write to log file: " . $this->log_file, 1);
    }
  }

  function sec2hms ($sec, $padHours = false) {
    // http://www.laughing-buddha.net/php/sec2hms/
    // start with a blank string
    $hms = "";

    // do the hours first: there are 3600 seconds in an hour, so if we divide
    // the total number of seconds by 3600 and throw away the remainder, we're
    // left with the number of hours in those seconds
    $hours = intval(intval($sec) / 3600);

    // add hours to $hms (with a leading 0 if asked for)
    $hms .= ($padHours)
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":"
          : $hours. ":";

    // dividing the total seconds by 60 will give us the number of minutes
    // in total, but we're interested in *minutes past the hour* and to get
    // this, we have to divide by 60 again and then use the remainder
    $minutes = intval(($sec / 60) % 60);

    // add minutes to $hms (with a leading 0 if needed)
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";

    // seconds past the minute are found by dividing the total number of seconds
    // by 60 and using the remainder
    $seconds = intval($sec % 60);

    // add seconds to $hms (with a leading 0 if needed)
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

    // done!
    return $hms;

  }

}
