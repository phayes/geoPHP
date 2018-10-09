<?php

namespace geoPHP\Geometry;

/**
 * A Surface is a 2-dimensional abstract geometric object.
 *
 * OGC 06-103r4 6.1.10 specification:
 * A simple Surface may consists of a single “patch” that is associated with one “exterior boundary” and 0 or more
 * “interior” boundaries. A single such Surface patch in 3-dimensional space is isometric to planar Surfaces, by a
 * simple affine rotation matrix that rotates the patch onto the plane z = 0. If the patch is not vertical, the
 * projection onto the same plane is an isomorphism, and can be represented as a linear transformation, i.e. an affine.
 *
 * @package geoPHP\Geometry
 */
abstract class Surface extends Collection {

    public function geometryType() {
        return Geometry::SURFACE;
    }

    public function dimension() {
        return 2;
    }

    public function startPoint() {
        return null;
    }

    public function endPoint() {
        return null;
    }

    public function pointN($n) {
        return null;
    }

    public function isClosed() {
        return null;
    }

    public function isRing() {
        return null;
    }

    public function length() {
        return 0;
    }

    public function length3D() {
        return 0;
    }

    public function haversineLength() {
        return 0;
    }

    public function greatCircleLength($radius = null) {
        return 0;
    }

    public function minimumZ() {
        return null;
    }

    public function maximumZ() {
        return null;
    }

    public function minimumM() {
        return null;
    }

    public function maximumM() {
        return null;
    }

    public function elevationGain($verticalTolerance = 0) {
        return null;
    }

    public function elevationLoss($verticalTolerance = 0) {
        return null;
    }

    public function zDifference() {
        return null;
    }


}

