<?php

class TimeMetadataProvider implements MetadataProvider {

  public function has($target, $key) {
    return !empty($target->metadata[__CLASS__][$key]) || ($key === 'time' && ($target instanceof Collection));
  }

  public function get($target, $key) {
    if ($this->has($target, $key)) {
      return $target->metadata[__CLASS__][$key];
    }
  }

  public function set($target, $key, $value) {
    if ($key === 'time') {
      $target->metadata[__CLASS__][$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  public function id() {
    return __CLASS__;
  }

}
