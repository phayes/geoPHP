<?php

class SimplifyMetadataProvider implements MetadataProvider {

  public $capabilities = array('dp', 'radial');

  public function provides($key) {
    if (in_array($key, $this->capabilities)) {return TRUE;};
    return FALSE;
  }

  public function get($target, $key, $options) {
    if ($this->isAvailable($target, $key)) {
      return $target->metadata['metadatas'][__CLASS__][$key];
    }

    if (!$this->provides($key)) {return FALSE;}

    if ($key == 'radial') {
      if ($target instanceof MultiLineString) {
        foreach ($target->components as $line) {
          $newline = $this->get($line, 'radial', $options);
          // TODO: remove this test and include it in Douglas Peucker algo.
          if ($newline->numPoints() >= 2) {
            $result[] = $newline;
          }
        }
        return new MultiLineString($result);
      }
      if ($target instanceof LineString) {
        $tolerance_squared = pow($options['tolerance'], 2);
        $new = $this->radial($target, array('tolerance'=>$tolerance_squared));
        $new->metadata['providers'] = $target->metadata['providers'];

        return $new;
      }
    }

    if ($key == 'dp') {
      if ($target instanceof MultiLineString) {
        foreach ($target->components as $line) {
          $newline = $this->get($line, 'dp', $options);
          // TODO: remove this test and include it in Douglas Peucker algo.
          if ($newline->numPoints() >= 2) {
            $result[] = $newline;
          }
        }
        return new MultiLineString($result);
      }
      if ($target instanceof LineString) {
        $tolerance_squared = pow($options['tolerance'], 2);
        $this->douglasPeucker($target, 0,count($target->components)-1, $tolerance_squared);
        $out = array();
        foreach ($target->components as $id => $point) {
          if (isset($point->include)) {
            $out[] = $target->components[$id];
          }
        }
        $linestring = new LineString($out);
        $linestring->metadata['providers'] = $target->metadata['providers'];

        return $linestring;
      }
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

  public function radial($line, $options) {
    $count = $line->numPoints()-1;
    $out = array();
    $out[] = $line->components[0];
    for($i=0; $i<$count; $i++) {
      $current = $line->components[$i];
      $next = $line->components[$i+1];

      if ($next instanceof Point && $current instanceof Point) {
        $newline = new LineString(array($current, $next));
        if (pow($newline->length(), 2) >= $options['tolerance']) {
          $out[] = $next;
        }
      }
    }

    $out[] = end($line->components);
    return new LineString($out);
  }

  /**
   * Douglas-Peuker polyline simplification algorithm. First draws single line
   * from start to end. Then finds largest deviation from this straight line, and if
   * greater than tolerance, includes that point, splitting the original line into
   * two new lines. Repeats recursively for each new line created.
   *
   * @param int $start_vertex_index
   * @param int $end_vertex_index
   */
  private function douglasPeucker($line, $start_vertex_index, $end_vertex_index, $tolerance_squared) {
    if ($end_vertex_index <= $start_vertex_index + 1) // there is nothing to simplify
      return;

    // Make line from start to end
    $baseline = new LineString(array($line->components[$start_vertex_index],$line->components[$end_vertex_index]));

    // Find largest distance from intermediate points to this line
    $max_dist_to_line_squared = 0;
    for ($index = $start_vertex_index+1; $index < $end_vertex_index; $index++) {
      $dist_to_line_squared = $this->distanceToPointSquared($baseline, $line->components[$index]);
      if ($dist_to_line_squared>$max_dist_to_line_squared) {
        $max_dist_to_line_squared = $dist_to_line_squared;
        $max_dist_index = $index;
      }
    }

    // Check max distance with tolerance
    // error is worse than the tolerance
    if ($max_dist_to_line_squared > $tolerance_squared) {
      //dpm($line->components[$max_dist_index], 'Point '.$max_dist_index);
      // split the polyline at the farthest vertex from S
      $line->components[$max_dist_index]->include = true;
      // recursively simplify the two subpolylines
      $this->douglasPeucker($line, $start_vertex_index,$max_dist_index, $tolerance_squared);
      $this->douglasPeucker($line, $max_dist_index,$end_vertex_index, $tolerance_squared);
    }
    // else the approximation is OK, so ignore intermediate vertices
  }

  public function distanceToPointSquared(LineString $line, Point $point) {
    $startPoint = $line->startPoint(); // p1
    $endPoint = $line->endPoint(); // p2

    $v = new Point($point->x() - $startPoint->x(), $point->y() - $startPoint->y());
    $l = new Point($endPoint->x() - $startPoint->x(), $endPoint->y() - $startPoint->y());
    $dot = $v->dotProduct($l->unitVector());

    if ($dot<=0) {
      $dl = new LineString(array($startPoint,$point));
      return pow($dl->length(), 2);
    }
    if ( ($dot*$dot) >= pow($line->length() ,2) ) {
      $dl = new LineString(array($endPoint,$point));
      return pow($dl->length(), 2);
    }
    else // Point within line
    {
      $v2 = new LineString(array($startPoint,$point));
      return pow($v2->length(), 2) - pow($dot, 2);
    }
  }
}
