<?php

class SpeedMetadataProvider implements MetadataProvider {

  public $capabilities = array('averageSpeed', 'maxSpeed', 'minSpeed');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {

    if ($target instanceof MultiLineString) {
      if ($key === 'maxSpeed') {
        $speeds = array();
        foreach ($target->components as $component) {
          $speeds[] = $component->getMetadata($key, $options);
        }
        rsort($speeds);
        foreach($speeds as $speed) {
          if ($speed != 0) {
            $this->set($target, 'maxSpeed', $speed);
            return $speed;
          }
        }
        $this->set($target, 'maxSpeed', 0);
        return 0;
      }
      if ($key === 'minSpeed') {
        foreach ($target->components as $component) {
          $speeds = array();
          foreach ($target->components as $component) {
            $speeds[] = $component->getMetadata($key, $options);
          }
          sort($speeds);
          foreach($speeds as $speed) {
            if ($speed != 0) {
              $this->set($target, 'minSpeed', $speed);
              return $speed;
            }
          }
        }
        $this->set($target, 'minSpeed', 0);
        return 0;
      }
      if ($key === 'averageSpeed') {
        $speeds = 0;
        $count = count($target->components);
        foreach ($target->components as $component) {
          $speed = $component->getMetadata($key, $options);
          if ($speed == 0) {
            $count--;
          }
          $speeds += $speed;
        }
        $speed = $speeds / $count;
      }
      $this->set($target, 'averageSpeed', $speed);
      return $speed;
    }

    if ($target instanceof LineString) {
      if ($key === 'maxSpeed') {
        $speeds = array();
        $points = $target->getPoints();
        for($i=0; $i<$target->numPoints()-1; $i++) {
          $point = $points[$i];
          $next_point = $points[$i+1];
          if (!is_object($next_point)) {continue;}
          $linestring = new LineString(array($point, $next_point));
          $linestring->registerMetadataProvider(new SpeedMetadataProvider());
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          $duration = $linestring->getMetadata('duration', array('threshold' => 0.5));
          $length = $linestring->greatCircleLength();
          if ($duration != 0) {
            //dpm("$length meters in $duration");
            $speeds[] = $length/$duration;
          }
        }
        rsort($speeds);
        foreach($speeds as $speed) {
          if ($speed != 0) {
            $this->set($target, 'maxSpeed', $speed);
            return $speed;
          }
        }
        $this->set($target, 'maxSpeed', 0);
        return 0;
      }

      if ($key === 'minSpeed') {
        $speeds = array();
        $points = $target->getPoints();
        for($i=0; $i<$target->numPoints()-1; $i++) {
          $point = $points[$i];
          $next_point = $points[$i+1];
          if (!is_object($next_point)) {continue;}
          if (!is_object($next_point)) {continue;}
          $linestring = new LineString(array($point, $next_point));
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          $duration = $linestring->getMetadata('duration', array('threshold' => 0.5));
          $length = $linestring->greatCircleLength();
          if ($duration != 0) {
            $speeds[] = $length/$duration;
          }
        }
        sort($speeds);
        foreach($speeds as $speed) {
          if ($speed != 0) {
            $this->set($target, 'minSpeed', $speed);
            return $speed;
          }
        }
        $this->set($target, 'minSpeed', 0);
        return 0;
      }

      if ($key == 'averageSpeed') {
        $time = $target->getMetadata('duration', array('threshold' => 0.5));
        $length = $target->greatCircleLength();
        if ($time != 0) {
          $this->set($target, 'averageSpeed', $length/$time);
          return $length/$time; // Meter/Sec
        }
        return 0;
      }
    }
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
