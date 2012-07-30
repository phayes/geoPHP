<?php

class SpeedMetadataProvider implements MetadataProvider {

  public $capabilities = array('averageSpeed', 'maxSpeed', 'minSpeed', 'speeds');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
    if ($this->isAvailable($target, $key)) {
      return $target->metadata['metadatas'][__CLASS__][$key];
    }

    if (!$this->provides($key)) {return FALSE;}

    if ($key == 'speeds') {
      if ($target instanceof LineString) {
        $points = $target->getPoints();
        $count = count($points) - 1;
        $speeds = array();
        for($i=0; $i<$count; $i++) {
          $current = $points[$i];
          $next = $points[$i+1];

          if (!($next instanceof Point)) {return 0;}

          $linestring = new LineString(array($current, $next));
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          $duration = $linestring->getMetadata('duration', $options);
          $length = $linestring->greatCircleLength();
          if ($duration != 0 && $length != 0) {
            $speeds[] = $length/$duration;
          }
        }
        $this->set($target, 'speeds', $speeds);
      }
      if ($target instanceof MultiLineString) {
        $speeds = array();
        foreach ($target->components as $component) {
          $speeds = array_merge($speeds, $this->get($component, 'speeds', $options));
        }
        $this->set($target, 'speeds', $speeds);
      }
    }

    if ($key == 'averageSpeed') {
      $speeds = $target->getMetadata('speeds');
      $count = count($speeds);
      if ($count != 0) {
        $average = array_sum($speeds) / $count;
        $this->set($target, $key, $average);
        return $average;
      }
      return 0;
    }
    if ($key == 'maxSpeed') {
      $speeds = $target->getMetadata('speeds');
      rsort($speeds);
      $this->set($target, $key, current($speeds));
      return current($speeds);
    }
    if ($key == 'minSpeed') {
      $speeds = $target->getMetadata('speeds');
      sort($speeds);
      $this->set($target, $key, current($speeds));
      return current($speeds);
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
