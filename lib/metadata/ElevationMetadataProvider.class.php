<?php

class ElevationMetadataProvider implements MetadataProvider {

  public $capabilities = array('ele', 'maxEle', 'minEle', 'averageEle');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {

    if ($target instanceof MultiLineString) {
      if ($key === 'maxEle') {
        $max = NULL;
        foreach ($target->components as $component) {
          if ($component->getMetadata($key) > $max || is_null($max)) {
            $max = $component->getMetadata($key);
          }
        }
        return $max;
      }
      if ($key === 'minEle') {
        $min = NULL;
        foreach ($target->components as $component) {
          if ($component->getMetadata($key) < $min || is_null($min)) {
            $min = $component->getMetadata($key);
          }
        }
        return $min;
      }
      if ($key === 'averageEle') {
        $ele_total = 0;
        $count = count($target->components);
        foreach ($target->components as $component) {
          $ele = $component->getMetadata($key, $options);
          if ($ele == 0) {
            $count--;
          }
          $ele_total += $ele;
        }
        return $ele_total / $count;
      }
    }

    if ($target instanceof LineString) {
      if ($key === 'maxEle') {
        $max = NULL;
        foreach ($target->components as $component) {
          if ($component->getMetadata('ele') > $max || is_null($max)) {
            $max = $component->getMetadata('ele');
          }
        }
        return $max;
      }
      if ($key === 'minEle') {
        $min = NULL;
        foreach ($target->components as $component) {
          if ($component->getMetadata('ele') < $min || is_null($min)) {
            $min = $component->getMetadata('ele');
          }
        }
        return $min;
      }
      if ($key === 'averageEle') {
        $ele_total = 0;
        $count = count($target->components);
        foreach ($target->components as $component) {
          $ele = $component->getMetadata('ele', $options);
          if ($ele == 0) {
            $count--;
          }
          $ele_total += $ele;
        }
        return $ele_total / $count;
      }
    }

    if ($this->provides($key)) {
      return $target->metadata['metadatas'][__CLASS__][$key];
    }

  }

  public function set($target, $key, $value) {
    if ($key === 'ele') {
      $target->metadata['metadatas'][__CLASS__][$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  public function id() {
    return __CLASS__;
  }

}
