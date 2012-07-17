<?php
/**
 * MultiLineString: A collection of LineStrings
 */
class MultiLineString extends Collection
{
  protected $geom_type = 'MultiLineString';

  // MultiLineString is closed if all it's components are closed
  public function isClosed() {
    foreach ($this->components as $line) {
      if (!$line->isClosed()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function getMetadata($key, $options = array()) {
    foreach ($this->components as $component) {
      foreach ($component->metadata['providers'] as $metadata_provider) {
        if ($metadata_provider->provides($key)) {
          return $metadata_provider->get($this, $key, $options);
        }
      }
      return NULL;
    }
  }
}

