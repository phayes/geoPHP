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
        $this->set($target, 'maxEle', $max);
        return $max;
      }
      if ($key === 'minEle') {
        $min = NULL;
        foreach ($target->components as $component) {
          if ($component->getMetadata($key) < $min || is_null($min)) {
            $min = $component->getMetadata($key);
          }
        }
        $this->set($target, 'minEle', $min);
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

        if ($count != 0) {
          $average = $ele_total / $count;
        }

        $this->set($target, 'averageEle', $average);
        return $average;
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
        $max = isset($max) ? $max : 0;
        $this->set($target, 'maxEle', $max);
        return $max;
      }
      if ($key === 'minEle') {
        $min = NULL;
        foreach ($target->components as $component) {
          if ($component->getMetadata('ele') < $min || is_null($min)) {
            $min = $component->getMetadata('ele');
          }
        }
        $min = isset($min) ? $min : 0;
        $this->set($target, 'minEle', $min);
        return $min;
      }
      if ($key === 'averageEle') {
        $ele_total = 0;
        $average = 0;
        $count = count($target->components);
        foreach ($target->components as $component) {
          $ele = $component->getMetadata('ele', $options);
          if ($ele == 0) {
            $count--;
          }
          $ele_total += $ele;
        }
        if ($count != 0) {
          $average = $ele_total / $count;
        }
        $this->set($target, 'averageEle', $average);
        return $average;
      }
    }

    if ($this->provides($key)) {
      return $target->metadata['metadatas'][__CLASS__][$key];
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
