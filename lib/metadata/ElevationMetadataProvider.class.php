<?php

class ElevationMetadataProvider implements MetadataProvider {

  public $capabilities = array('ele');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
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
