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
        $count = count($target->components);
        foreach ($target->components as $component) {
          $ele = $component->getMetadata($key, $options);
          if ($ele != 0) {
            $ele_array[] = $ele;
          }
        }

        if ($count != 0) {
          $average = array_sum($ele_array) / $count;
        }

        $this->set($target, 'averageEle', $average);
        return $average;
      }
    }

    if ($target instanceof LineString) {
      if ($key === 'maxEle') {
        $max = NULL;
        $maxs = array();
        foreach ($target->components as $component) {
          $maxs[] = $component->getMetadata('ele');
        }
        $maxs = array_filter($maxs);
        rsort($maxs);
        $max = $maxs[0];
        $this->set($target, 'maxEle', $max);
        return $max;
      }
      if ($key === 'minEle') {
        $min = NULL;
        $mins = array();
        foreach ($target->components as $component) {
          $mins[] = $component->getMetadata('ele');
        }
        $mins = array_filter($mins);
        sort($mins);
        $min = $mins[0];
        $this->set($target, 'minEle', $min);
        return $min;
      }
      if ($key === 'averageEle') {
        foreach ($target->components as $component) {
          $ele_array[] = $component->getMetadata('ele', $options);
        }

        $ele_array = array_filter($ele_array);
        $count = count($ele_array);

        if ($count != 0) {
          $average = array_sum($ele_array) / $count;
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
