<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Adapter to implement for Dependecy Injection in GeoJSON loader
 *
 * @package    sfMapFishPlugin
 * @subpackage GeoJSON
 * @author     Camptocamp <info@camptocamp.com>
 */
interface GeoJSON_Adapter
{

  /**
   * Returns if a Feature or a FeatureCollection should be created
   *
   * @return boolean
   */
  public function isMultiple($object);

  /**
   * Returns an iterable for muiltiple object
   */
  public function getIterable($object);

  /**
   * Returns WKT string for passed object
   *
   * @return string
   */
  public function getObjectGeometry($object);

  /**
   * Returns passed object identifier
   *
   * @return mixed 
   */
  public function getObjectId($object);

  /**
   * Returns passed object attributes
   *
   * @return array
   */
  public function getObjectProperties($object);

}
