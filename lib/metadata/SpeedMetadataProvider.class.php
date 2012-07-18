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
            return $speed;
          }
        }
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
              return $speed;
            }
          }
        }
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
      return $speed;
    }

    if ($target instanceof LineString) {
      if ($key === 'maxSpeed') {
        $numPoints = $target->numPoints();
        $speeds = array();
        for($i=1; $i<$numPoints; $i++) {
          $linestring = new LineString(array($target->pointN($i), $target->pointN($i+1)));
          $linestring->registerMetadataProvider(new SpeedMetadataProvider());
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          $duration = $linestring->getMetadata('duration', array('threshold' => 0.5));
          $length = $linestring->greatCircleLength();

          $speeds[] = $length/$duration;
        }
        rsort($speeds);
        foreach($speeds as $speed) {
          if ($speed != 0) {
            return $speed;
          }
        }
        return 0;
      }

      if ($key === 'minSpeed') {
        $numPoints = $target->numPoints();
        $speeds = array();
        for($i=1; $i<$numPoints; $i++) {
          $linestring = new LineString(array($target->pointN($i), $target->pointN($i+1)));
          $linestring->registerMetadataProvider(new SpeedMetadataProvider());
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          $duration = $linestring->getMetadata('duration', array('threshold' => 0.5));
          $length = $linestring->greatCircleLength();

          $speeds[] = $length/$duration;
        }
        sort($speeds);
        foreach($speeds as $speed) {
          if ($speed != 0) {
            return $speed;
          }
        }
        return 0;
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
