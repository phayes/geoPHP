<?php

class ElevationMetadataProvider implements MetadataProvider {

  public $capabilities = array('ele', 'maxEle', 'minEle', 'averageEle', 'elevations');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {

    if (!$this->provides($key)) {return FALSE;}

    if ($key == 'elevations') {
      if (!isset($target->metadata['metadatas'][__CLASS__]['elevations'])) {
        $points = $target->getPoints();
        foreach($points as $point) {
          $elevations[] = $point->getMetadata('ele');
        }
        $elevations = array_filter($elevations);
        $this->set($target, 'elevations', $elevations);
        return $elevations;
      } else {
        return $target->metadata['metadatas'][__CLASS__]['elevations'];
      }
    }

    if ($key == 'averageEle') {
      $elevations = $target->getMetadata('elevations');
      $count = count($elevations);
      if ($count != 0) {
        $average = array_sum($elevations) / $count;
        $this->set($target, $key, $average);
        return $average;
      }
      return 0;
    }
    if ($key == 'maxEle') {
      $elevations = $target->getMetadata('elevations');
      rsort($elevations);
      $this->set($target, $key, current($elevations));
      return current($elevations);
    }
    if ($key == 'minEle') {
      $elevations = $target->getMetadata('elevations');
      sort($elevations);
      $this->set($target, $key, current($elevations));
      return current($elevations);
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
