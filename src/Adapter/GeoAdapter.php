<?php

namespace geoPHP\Adapter;

use geoPHP\Geometry\Geometry;

/*
 * (c) Patrick Hayes 2011
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * GeoAdapter : Interface of adapters
 * for reading and writing to and from Geometry objects
 *
 */
interface GeoAdapter {

    /**
     * Read input and return a Geometry
     *
     * @param string $input
     * @return Geometry
     */
    public function read($input);

    /**
     * Write out a Geometry in the adapter's format
     *
     * @param Geometry $geometry
     * @return string
     */
    public function write(Geometry $geometry);
}
