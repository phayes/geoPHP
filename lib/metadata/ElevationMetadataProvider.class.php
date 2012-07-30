<?php

class ElevationMetadataProvider implements MetadataProvider {

  public $capabilities = array('ele', 'maxEle', 'minEle', 'averageEle', 'elevations', 'gainTotalEle', 'gainTotalLoss');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
    if ($this->isAvailable($target, $key)) {
      return $target->metadata['metadatas'][__CLASS__][$key];
    }

    if (!$this->provides($key)) {return FALSE;}

    if ($key == 'elevations') {
      $points = $target->getPoints();
      foreach($points as $point) {
        $elevations[] = $point->getMetadata('ele');
      }
      $elevations = array_filter($elevations);
      $this->set($target, 'elevations', $elevations);
      return $elevations;
    }

    if ($key == 'averageEle') {
      $elevations = $target->getMetadata('elevations');
      $count = count($elevations);
      if ($count != 0) {
        $average = array_sum($elevations) / $count;
        $this->set($target, $key, $average);
        return $average;
      }
      return 0;
    }
    if ($key == 'maxEle') {
      $elevations = $target->getMetadata('elevations');
      rsort($elevations);
      $this->set($target, $key, current($elevations));
      return current($elevations);
    }
    if ($key == 'minEle') {
      $elevations = $target->getMetadata('elevations');
      sort($elevations);
      $this->set($target, $key, current($elevations));
      return current($elevations);
    }

    if ($key == 'gainTotalEle') {
      $elevations = $target->getMetadata('elevations');
      $start = $target->startPoint()->getMetadata('ele');
      $count = count($elevations)-1;
      $gain = 0;
      for ($i=0; $i<$count; $i++) {
        $current = $elevations[$i];
        $next = $elevations[$i+1];
        $deltas[] = array('eleA' => $current, 'eleB' => $next, 'delta' => $next-$current);
      }

      $p = $i = 0;
      foreach ($deltas as $n) {
        if ($n['delta'] >= 0) {
          if (!$p) {
            $p ^= 1;
            $i++;
          }
        } else {
          if ($p) {
            $p ^= 1;
            $i++;
          }
        }
        $result[$i][] = $n;
      }

      foreach($result as $id => $data) {
        if ($data[0]['delta'] < 0) {
          unset($result[$id]);
          continue;
        }

        if (count($data) <= 1) {
          unset($result[$id]);
          continue;
        }

        if (abs($data[count($data)-1]['eleB'] - $data[0]['eleA']) <= 0.5) {
          unset($result[$id]);
          continue;
        }
      }

      $gain = 0;
      foreach($result as $data) {
        foreach($data as $point) {
          $gain += $point['delta'];
        }
      }

      $this->set($target, 'gainTotalEle', $gain);
    }

    if ($key == 'gainTotalLoss') {
      $elevations = $target->getMetadata('elevations');
      $start = $target->startPoint()->getMetadata('ele');
      $count = count($elevations);
      $gain = 0;
      for ($i=0; $i<$count; $i++) {
        $current = $elevations[$i];
        $next = $elevations[$i+1];

        $delta = $current - $next;
        if (($delta > 0) && $delta > 2) {
          $gain += $delta;
        }
      }
      $this->set($target, 'gainTotalLoss', $gain);
    }

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
