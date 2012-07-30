<?php

class TimeMetadataProvider implements MetadataProvider {

  public $capabilities = array('time');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
    if ($this->isAvailable($target, $key)) {
      return $target->metadata['metadatas'][__CLASS__][$key];
    }

    if (!$this->provides($key)) {return FALSE;}

    return $target->metadata['metadatas'][__CLASS__][$key];
  }

  public function set($target, $key, $value) {
    if ($this->provides($key)) {
      $target->metadata['metadatas'][__CLASS__][$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  public function isAvailable($target, $keys) {
    if (!is_array($keys)) {
      $keys = (array) $keys;
    }
    foreach ($keys as $key) {
      if (!isset($target->metadata['metadatas'][__CLASS__][$key])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function id() {
    return __CLASS__;
  }

}
