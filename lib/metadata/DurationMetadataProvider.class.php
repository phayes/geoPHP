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
      return $duration;
    }

    if ($target instanceof LineString) {
      if ($key == 'duration') {
        $point_a = $target->startPoint();
        $point_b = $target->endPoint();

        if (!is_null($point_a->getMetadata('time')) && !is_null($point_b->getMetadata('time'))) {
          $time = strtotime($point_b->getMetadata('time')) - strtotime($point_a->getMetadata('time'));
        }
        return $time;
      }

      if ($key == 'stopDuration') {
        $duration = 0;
        foreach ($target->explode() as $LineString) {
          $point_a = $LineString->startPoint();
          $point_b = $LineString->endPoint();
          if (!is_null($point_a->getMetadata('time')) && !is_null($point_b->getMetadata('time'))) {
            $time = strtotime($point_b->getMetadata('time')) - strtotime($point_a->getMetadata('time'));
          } else {
            $time = 0;
          }

          $length = $LineString->greatCircleLength();

          if ($length >= 0 && $length <= $options['threshold']) {
            $duration += $time;
          }

        }

        return $duration;
      }

      if ($key == 'movingDuration') {
        return $this->get($target, 'duration', $options) - $this->get($target, 'stopDuration', $options);
      }
    }

  }

  public function set($target, $key, $value) {
    if ($key === 'duration') {
      $target->metadata['metadatas'][__CLASS__][$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  public function id() {
    return __CLASS__;
  }

}
