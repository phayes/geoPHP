<?php

class DurationMetadataProvider implements MetadataProvider {

  public $capabilities = array('duration', 'movingDuration', 'stopDuration', 'durations', 'stopDurations');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
    if ($this->isAvailable($target, $key)) {
      return $target->metadata['metadatas'][__CLASS__][$key];
    }

    if (!$this->provides($key)) {return FALSE;}

    if ($key == 'durations') {
      if ($target instanceof LineString) {
        $points = $target->getPoints();
        $count = count($points);
        for($i=0; $i<$count-1; $i++) {
          $current = $points[$i];
          $next = $points[$i+1];
          if (!($next instanceof Point)) {return 0;}
          $durations[] = abs(strtotime($current->getMetadata('time')) - strtotime($next->getMetadata('time')));
        }
        $this->set($target, 'durations', $durations);
      }
      if ($target instanceof MultiLineString) {
        $durations = array();
        foreach ($target->components as $component) {
          $durations = array_merge($durations, $this->get($component, 'durations', $options));
        }
        $this->set($target, 'durations', $durations);
      }
    }

    if ($key == 'stopDurations') {
      if ($target instanceof LineString) {
        $points = $target->getPoints();
        $count = count($points);
        $durations = array();
        for($i=0; $i<$count-1; $i++) {
          $current = $points[$i];
          $next = $points[$i+1];
          if (!($next instanceof Point)) {return 0;}
          $linestring = new LineString(array($current, $next));
          $duration = abs(strtotime($current->getMetadata('time')) - strtotime($next->getMetadata('time')));
          $length = $linestring->greatCircleLength();
          if ($length < $options['threshold']) {
            $durations[] = $duration;
          }

        }
        $this->set($target, 'stopDurations', $durations);
      }
      if ($target instanceof MultiLineString) {
        $durations = array();
        foreach ($target->components as $component) {
          $durations = array_merge($durations, $this->get($component, 'stopDurations', $options));
        }
        $this->set($target, 'stopDurations', $durations);
      }
    }

    if ($key == 'duration') {
      $durations = $target->getMetadata('durations');
      return array_sum($durations);
    }

    if ($key == 'stopDuration') {
      $durations = $target->getMetadata('stopDurations', $options);
      return array_sum($durations);
    }

    if ($key == 'movingDuration') {
      return array_sum($target->getMetadata('durations')) -
        array_sum($target->getMetadata('stopDurations'));
    }

    return $target->metadata['metadatas'][__CLASS__][$key];
  }

  public function set($target, $key, $value) {
    if ($this->provides($key)) {
      $target->metadata['metadatas'][__CLASS__][$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  public function isAvailable($target, $keys) {
    if (!is_array($keys)) {
      $keys = (array) $keys;
    }
    foreach ($keys as $key) {
      if (!isset($target->metadata['metadatas'][__CLASS__][$key])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function id() {
    return __CLASS__;
  }

}
