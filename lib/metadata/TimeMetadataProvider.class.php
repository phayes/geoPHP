<?php

class TimeMetadataProvider implements MetadataProvider {

  public function has($target, $key) {
    return isset($target->metadata['metadatas'][__CLASS__]) && isset($target->metadata['metadatas'][__CLASS__][$key]) && !is_null($target->metadata['metadatas'][__CLASS__][$key]) && ($key === 'time') && ($target instanceof Point);
  }

  public function get($target, $key, $options) {

    if ($target instanceof Point) {
      if ($this->has($target, $key)) {
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
