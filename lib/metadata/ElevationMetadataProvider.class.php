<?php

class ElevationMetadataProvider implements MetadataProvider {

  public $capabilities = array('ele');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function has($target, $key) {
    return isset($target->metadata['metadatas'][__CLASS__]) && isset($target->metadata['metadatas'][__CLASS__][$key]) && !is_null($target->metadata['metadatas'][__CLASS__][$key]) && ($key === 'ele') && ($target instanceof Point);
  }

  public function get($target, $key, $options) {
    if ($this->has($target, $key)) {
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
