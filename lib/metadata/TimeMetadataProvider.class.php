<?php

class TimeMetadataProvider implements MetadataProvider {

  public $capabilities = array('time');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
    if ($target instanceof Point) {
      if ($this->provides($key)) {
        return $target->metadata['metadatas'][__CLASS__][$key];
      }
      return 0;
    }
  }

  public function set($target, $key, $value) {
    if ($key === 'time') {
      $target->metadata['metadatas'][__CLASS__][$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  public function id() {
    return __CLASS__;
  }

}
