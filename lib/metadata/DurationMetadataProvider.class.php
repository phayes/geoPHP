<?php

class DurationMetadataProvider implements MetadataProvider {

  public $capabilities = array('duration', 'movingDuration', 'stopDuration');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {

    if ($target instanceof MultiLineString) {
      $duration = 0;
      foreach ($target->components as $component) {
        $duration += $component->getMetadata($key, $options);
      }
      $this->set($target, $key, $duration);
      return $duration;
    }

    if ($target instanceof LineString) {
      if ($key == 'duration') {
        $point_a = $target->startPoint();
        $point_b = $target->endPoint();
        if (!is_null($point_a->getMetadata('time')) && !is_null($point_b->getMetadata('time'))) {
          $time = abs(strtotime($point_b->getMetadata('time')) - strtotime($point_a->getMetadata('time')));
        } else {
          $time = 0;
        }
        $this->set($target, 'duration', $time);
        return $time;
      }

      if ($key == 'stopDuration') {
        $duration = 0;
        $points = $target->getPoints();
        foreach ($points as $point) {
          $point_a = $point;
          $point_b = current($points);

          $linestring = new LineString(array($point_a, $point_b));
          $linestring->registerMetadataProvider(new SpeedMetadataProvider());
          $linestring->registerMetadataProvider(new DurationMetadataProvider());

          if (!is_null($point_a->getMetadata('time')) && !is_null($point_b->getMetadata('time'))) {
            $time = abs(strtotime($point_b->getMetadata('time')) - strtotime($point_a->getMetadata('time')));
          } else {
            $time = 0;
          }

          $length = $linestring->greatCircleLength();

          if ($length >= 0 && $length <= $options['threshold']) {
            $duration += $time;
          }

        }

        $this->set($target, 'stopDuration', $duration);
        return $duration;
      }

      if ($key == 'movingDuration') {
        $tmp = $this->get($target, 'duration', $options) - $this->get($target, 'stopDuration', $options);
        $this->set($target, 'movingDuration', $tmp);
        return $tmp;
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
