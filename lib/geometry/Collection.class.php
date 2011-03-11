<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Collection : abstract class which represents a collection of components.
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 * @version
 */
abstract class Collection extends Geometry implements Iterator
{
  protected $components = array();

  /**
   * Constructor
   *
   * @param array $components The components array
   */
  public function __construct(array $components)
  {
    foreach ($components as $component)
    {
      $this->add($component);
    }
  }

  private function add($component)
  {
    $this->components[] = $component;
  }

  /**
   * An accessor method which recursively calls itself to build the coordinates array
   *
   * @return array The coordinates array
   */
  public function getCoordinates()
  {
    $coordinates = array();
    foreach ($this->components as $component)
    {
      $coordinates[] = $component->getCoordinates();
    }
    return $coordinates;
  }

  /**
   * Returns Colection components
   *
   * @return array
   */
  public function getComponents()
  {
    return $this->components;
  }

  # Iterator Interface functions

  public function rewind()
  {
    reset($this->components);
  }

  public function current()
  {
    return current($this->components);
  }

  public function key()
  {
    return key($this->components);
  }

  public function next()
  {
    return next($this->components);
  }

  public function valid()
  {
    return $this->current() !== false;
  }

  // For collections, centroids and bbox are all the same
  public function getCentroid()
  {
    // By default, the centroid of a collection is the average of x and y of all the component centroids
    $i = 0;
    
    foreach ($this->components as $component) {
      $component_centroid = $component->getCentroid();
      // On the first run through, set sum manually

      if ($i == 0) {
        $x_sum = $component_centroid->getX();
        $y_sum = $component_centroid->getY();
      }
      else {
        $x_sum += $component_centroid->getX();
        $y_sum += $component_centroid->getY();
      }
      $i++;
    }
    
    $x = $x_sum / $i;
    $y = $y_sum / $i;
    
    $centroid = new Point($x, $y);
    
    return $centroid;
  }
  
  public function getBBox() {
    // Go through each component and get the max and min x and y
    $i = 0;
    foreach ($this->components as $component) {
      $component_bbox = $component->getBBox();
      
      // On the first run through, set the bbox to the component bbox
      if ($i == 0) {
        $maxx = $component_bbox['maxx'];
        $maxy = $component_bbox['maxy'];
        $minx = $component_bbox['minx'];
        $miny = $component_bbox['miny'];
      }
      
      // Do a check and replace on each boundary, slowly growing the bbox
      $maxx = $component_bbox['maxx'] > $maxx ? $component_bbox['maxx'] : $maxx;
      $maxy = $component_bbox['maxy'] > $maxy ? $component_bbox['maxy'] : $maxy;
      $minx = $component_bbox['minx'] < $minx ? $component_bbox['minx'] : $minx;
      $miny = $component_bbox['miny'] < $miny ? $component_bbox['miny'] : $miny;
      $i++;
    }
    
    return array(
      'maxy' => $maxy,
      'miny' => $miny,
      'maxx' => $maxx,
      'minx' => $minx,
    );
  }

  public function getArea() {
    $area = 0;
    foreach ($this->components as $component) {
      $area += $component->getArea();
    }
    return $area;
  }

  public function intersects($geometry) {
    //TODO
  }

}

