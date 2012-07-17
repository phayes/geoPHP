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
        $max = NULL;
        foreach ($target->components as $component) {
          $speed = $component->getMetadata($key, $options);
          if (is_null($max) || $speed > $max) {
            $max = $speed;
          }
        }
        return $max;
      }
      if ($key === 'minSpeed') {
        $min = NULL;
        foreach ($target->components as $component) {
          $speed = $component->getMetadata($key, $options);
          if (is_null($min) || $speed < $min || $speed != 0) {
            $min = $speed;
          }
        }
        return $min;
      }
      if ($key === 'averageSpeed') {
        $speeds = 0;
        $count = count($target->components);
        foreach ($target->components as $component) {
          $speed = $component->getMetadata($key, $options);
          if ($speed == 0) {
            $count--;
          } else {
            $speeds += $component->getMetadata($key, $options);
          }
        }
        $speed = $speeds / $count;
      }
      return $speed;
    }

    if ($target instanceof LineString) {
      if ($key === 'maxSpeed') {
        $numPoints = $target->numPoints();
        $max = NULL;
        for($i=1; $i<$numPoints; $i++) {
          $point = $target->pointN($i);
          $next_point = $target->pointN($i+1);

          $linestring = new LineString(array($point, $next_point));
          $linestring->registerMetadataProvider(new SpeedMetadataProvider());
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          $duration = $linestring->getMetadata('duration', array('threshold' => 0.5));
          $length = $linestring->greatCircleLength();
          $speed = $length/$duration;
          if (is_null($max) || $speed > $max) {
            $max = $speed;
          }
        }
        return $max;
      }

      if ($key === 'minSpeed') {
        $numPoints = $target->numPoints();
        $min = NULL;
        for($i=1; $i<$numPoints; $i++) {
          $point = $target->pointN($i);
          $next_point = $target->pointN($i+1);

          $linestring = new LineString(array($point, $next_point));
          $linestring->registerMetadataProvider(new SpeedMetadataProvider());
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          $duration = $linestring->getMetadata('duration', array('threshold' => 0.5));
          $length = $linestring->greatCircleLength();
          $speed = $length/$duration;
          if (is_null($min) || $speed < $min || $speed != 0) {
            $min = $speed;
          }
        }
        return $min;
      }

      if ($key == 'averageSpeed') {
        $time = $target->getMetadata('duration', array('threshold' => 0.5));
        $length = $target->greatCircleLength();
        return $length/$time; // Meter/Sec
      }
    }
 }

  public function set($target, $key, $value) {
    if ($key === 'averageSpeed') {
      $target->metadata['metadatas'][__CLASS__][$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  public function id() {
    return __CLASS__;
  }

}
