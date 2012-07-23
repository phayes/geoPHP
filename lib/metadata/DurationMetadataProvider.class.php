<?php

class DurationMetadataProvider implements MetadataProvider {

  public $capabilities = array('duration', 'movingDuration', 'stopDuration', 'durations', 'stopDurations');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
    if (!$this->provides($key)) {return FALSE;}

    if ($key == 'durations') {
      if (!isset($target->metadata['metadatas'][__CLASS__]['durations'])) {
        $points = $target->getPoints();
        $count = count($points);
        for($i=0; $i<$count-1; $i++) {
          $current = $points[$i];
          $next = $points[$i+1];
          if (!($next instanceof Point)) {return 0;}
          $durations[] = abs(strtotime($current->getMetadata('time')) - strtotime($next->getMetadata('time')));
        }
        $this->set($target, 'durations', $durations);
        return $durations;
      } else {
        return $target->metadata['metadatas'][__CLASS__]['durations'];
      }
    }

    if ($key == 'stopDurations') {
      if (!isset($target->metadata['metadatas'][__CLASS__]['stopDurations'])) {
        $points = $target->getPoints();
        $count = count($points);
        for($i=0; $i<$count-1; $i++) {
          $current = $points[$i];
          $next = $points[$i+1];
          if (!($next instanceof Point)) {return 0;}
          $linestring = new LineString(array($current, $next));
          $duration = abs(strtotime($current->getMetadata('time')) - strtotime($next->getMetadata('time')));
          if ($linestring->greatCircleLength() < $options['threshold']) {
            $durations[] = $duration;
          }
        }
        $this->set($target, 'stopDurations', $durations);
        return $durations;
      } else {
        return $target->metadata['metadatas'][__CLASS__]['stopDurations'];
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

  public function id() {
    return __CLASS__;
  }

}
