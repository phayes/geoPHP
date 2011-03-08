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


}

