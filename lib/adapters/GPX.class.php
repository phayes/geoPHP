<?php
/*
 * Copyright (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
* PHP Geometry/GPX encoder/decoder
*
* This has been extended from the original geoPHP codebase to support
* reading and writing metadata tags including Garmin extensions for tracks, routes, and waypoints.
*/

class GPX extends GeoAdapter {
	private $namespace = FALSE;
	private $nss = ''; // Name-space string. eg 'georss:'

	// -------------------------------------------------

	/**
	* Read GPX string into geometry objects
	*
	* @param string $gpx A GPX string
	*
	* @return Geometry|GeometryCollection
	*/

	public function read($gpx) {
		return $this->geomFromText($gpx);
	}

	// -------------------------------------------------

	/**
	* Serialize geometries into a GPX string.
	*
	* @param Geometry $geometry
	*
	* @return string The GPX string representation of the input geometries and metdata (if present)
	*/

	public function write( Geometry $geometry, $namespace = FALSE ) {
		if ($geometry->isEmpty()) return NULL;
		if ($namespace) {
			$this->namespace = $namespace;
			$this->nss = $namespace.':';    
		}

		$gpx = '<' . $this->nss .'gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:gpxx="http://www.garmin.com/xmlschemas/GpxExtensions/v3" xmlns:gpxtpx="http://www.garmin.com/xmlschemas/TrackPointExtension/v1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" creator="geoPHPwithFeatures" version="1.0" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd">';

		// if there is metadata associated with the top level geometry we add it as children to a <metadata> tag. 

		if (( $meta_data = $geometry->getMetaData()) != NULL ) {

			$gpx .= '<metadata>' . $this->metaDataToGPX( $meta_data ) . '</metadata>';

		}

		$gpx .= $this->geometryToGPX($geometry).'</'.$this->nss.'gpx>';

		return $gpx;

	} // end of write()

	// -------------------------------------------------

	/**
	* given a GPX string, generate geometries
	*/
  
	public function geomFromText( $text ) {

		// Change to lower-case and strip all CDATA
		// $text = strtolower($text);

		//  $text = preg_replace('/<!\[cdata\[(.*?)\]\]>/s','',$text);
    
		// Load into DOMDocument

		$xmlobj = new DOMDocument();

		@$xmlobj->loadXML($text);

		if ($xmlobj === false) {
			throw new Exception("Invalid GPX: ". $text);
		}
    
		$this->xmlobj = $xmlobj;

		try {
			$geom = $this->geomFromXML();
		} catch(InvalidText $e) {
			throw new Exception("Cannot Read Geometry From GPX: ". $text);
		} catch(Exception $e) {
			throw $e;
		}

		return $geom;

	} // end of geomFromText()

	// -------------------------------------------------
  
	protected function geomFromXML() {
		$geometries = array();
		$geometries = array_merge($geometries, $this->parseWaypoints());
		$geometries = array_merge($geometries, $this->parseTracks());
		$geometries = array_merge($geometries, $this->parseRoutes());
    
		if (empty($geometries)) {
			throw new Exception("Invalid / Empty GPX");
		}
    
		$geom = geoPHP::geometryReduce($geometries); 

		// it's a bit forced, but we store the top level metadata info in the geometrycollection.

		$geom->setMetaData( $this->parseMetaData() );

		return $geom;

	} // end of geomFromXML()

	// -------------------------------------------------
  
	protected function childElements($xml, $nodename = '') {
		$children = array();
		foreach ($xml->childNodes as $child) {
			if ($child->nodeName == $nodename) {
				$children[] = $child;
			}
		}

		return $children;

	} // end of childElements()

	// -------------------------------------------------

	/**
	* parse the metadata tag
	*
	* There is only ever one of these tags in a GPX file.
	*
	* These metadata values are stored in the top geometrycollection object.
	*
	* @return array nested array of metadata.
	*
	* @link http://www.topografix.com/gpx/1/1/#type_metadataType
	*/
  
	protected function parseMetaData() {

		$meta_data_elements = $this->xmlobj->getElementsByTagName('metadata');

		if ( $meta_data_elements->length > 0 ) {
			return $this->parseMetaDataChildren( $meta_data_elements->item(0) );
		} else {
			return NULL;
		}

		

	} // end of parseMetaData()

	// -------------------------------------------------

	/**
	* parse waypoints
	*
	* @link http://www.topografix.com/gpx/1/1/#type_wptType
	*/
  
	protected function parseWaypoints() {
		$points = array();
		$wpt_elements = $this->xmlobj->getElementsByTagName('wpt');
		
		foreach ($wpt_elements as $wpt) {
			$lat = $wpt->attributes->getNamedItem("lat")->nodeValue;
			$lon = $wpt->attributes->getNamedItem("lon")->nodeValue;

			$elevation = NULL;

			// waypoint meta data.

			$meta_data = $this->parseMetaDataChildren( $wpt );

			if ( isset( $meta_data[ 'elevation' ] ) ) {
				$elevation = @$meta_data[ 'elevation' ];
			}

			$points[] = new Point($lon, $lat, $elevation, $meta_data);

		}

		return $points;

	} // end of parseWayPoints()

	// -------------------------------------------------

	/**
	* parse tracks
	*
	* Each track is represented by a single linestring.
	*
	* @link http://www.topografix.com/gpx/1/1/#type_trkType
	*
	* @todo evaluate cases where a single track has multiple track segments.
	*/

	protected function parseTracks() {
		$lines = array();
		$trk_elements = $this->xmlobj->getElementsByTagName('trk');

		foreach ($trk_elements as $trk) {

			$components = array();

			foreach ($this->childElements($trk, 'trkseg') as $trkseg) {
				foreach ($this->childElements($trkseg, 'trkpt') as $trkpt) {
					$lat = $trkpt->attributes->getNamedItem("lat")->nodeValue;
					$lon = $trkpt->attributes->getNamedItem("lon")->nodeValue;

					$elevation = NULL;

					$meta_data = $this->parseMetaDataChildren( $trkpt );

					if ( isset( $meta_data[ 'elevation' ] ) ) {
						$elevation = @$meta_data[ 'elevation' ];
					}

					$components[] = new Point($lon, $lat, $elevation, $meta_data );

				}
			}

			if ($components) {

				// tracks may have a name and extensions childnodes.

				$meta_data = $this->parseMetaDataChildren( $trk );

				$meta_data[ 'line_type' ] = 'trk';
                	
				$lines[] = new LineString( $components, $meta_data );

			}
		}

		return $lines;

	} // end of parseTracks()

	// -------------------------------------------------

	/**
	* parse routes
	*
	* Each route is represented by a linestring
	*
	* @link http://www.topografix.com/gpx/1/1/#type_rteType
	*/
  
	protected function parseRoutes() {
		$lines = array();
		$rte_elements = $this->xmlobj->getElementsByTagName('rte');

		foreach ($rte_elements as $rte) {
			$components = array();
			foreach ($this->childElements($rte, 'rtept') as $rtept) {
				$lat = $rtept->attributes->getNamedItem("lat")->nodeValue;
				$lon = $rtept->attributes->getNamedItem("lon")->nodeValue;

				$elevation = NULL;

				$meta_data = $this->parseMetaDataChildren( $rtept );

				if ( isset( $meta_data[ 'elevation' ] ) ) {
					$elevation = @$meta_data[ 'elevation' ];
				}

				$components[] = new Point($lon, $lat, $elevation, $meta_data );
			}

		$meta_data = $this->parseMetaDataChildren( $rte );

		$meta_data[ 'line_type' ] = 'rte';
 
		$lines[] = new LineString( $components, $meta_data );
		}

	return $lines;

	} // end of parseRoutes()

	// -------------------------------------------------

	/**
	* parse metadata child nodes 
	*
	* This parses both 'metadata' child nodes of wpt's, trks, etc 
	* but also the <metadata> tag below the root. It's a bit of a catch
	* all and there are paths, if the geometry data is not correct, where
	* it can generate invalid GPX.
	*
	* @param $node DOMNode root 
	* @return array list of meta data values
	*/

	protected function parseMetaDataChildren( $node ) {

		$meta_data = [];

		foreach ($node->childNodes as $child) {

			switch ( strtolower( $child->nodeName )) {

				case 'name' :

					$meta_data[ 'name' ] = $child->firstChild->nodeValue;

					break;

				case 'cmt' :

					$meta_data[ 'cmt' ] = $child->firstChild->nodeValue;

					break;

				case 'link' :

					// there may be multiple links.

					$meta_data[ 'link' ][] = $this->parseLink( $child );

					break;

				case 'ele':

					$meta_data[ 'elevation' ] = $child->firstChild->nodeValue;

					break;

				case 'time':

					$meta_data[ 'time' ] = $child->firstChild->nodeValue;

					break;

				case 'desc':

					$meta_data[ 'desc' ] = $child->firstChild->nodeValue;

					break;

				case 'sym':

					$meta_data[ 'sym' ] = $child->firstChild->nodeValue;

					break;

				case 'src':

					$meta_data[ 'src' ] = $child->firstChild->nodeValue;

					break;

				case 'type':

					$meta_data[ 'type' ] = $child->firstChild->nodeValue;

					break;

				case 'keywords':

					$meta_data[ 'keywords' ] = $child->firstChild->nodeValue;

					break;

				case 'bounds':

					// this has four attributes that we need to 

					$meta_data[ 'bounds' ][ 'minlat' ] = $child->attributes->getNamedItem( 'minlat' )->nodeValue;
					$meta_data[ 'bounds' ][ 'minlon' ] = $child->attributes->getNamedItem( 'minlon' )->nodeValue;
					$meta_data[ 'bounds' ][ 'maxlat' ] = $child->attributes->getNamedItem( 'maxlat' )->nodeValue;
					$meta_data[ 'bounds' ][ 'maxlon' ] = $child->attributes->getNamedItem( 'maxlon' )->nodeValue;

					break;

				case 'extensions':

					$meta_data[ 'extensions' ] = $this->parseExtensions( $child );

					break;

			} // end of switch

		} // end of foreach

	return $meta_data;

	} // end of parseMetaDataChildren()

	// -------------------------------------------------

	/**  
	* parse Garmin meta data extensions
	*/

	protected function parseExtensions( $node ) {

		$meta_data = [];

		foreach ($node->childNodes as $child) {

			switch( strtolower( $child->nodeName )) {

				case 'gpxx:waypointextension':

					$meta_data[ 'gpxx:waypointextension' ] = $this->parseWaypointExtension( $child );

					break;

				case 'gpxx:routepointextension':

					$meta_data[ 'gpxx:routepointextension' ] = $this->parseRoutepointExtension( $child );

			}

		}

	return $meta_data;

	} // end of parseExtensions

	// -------------------------------------------------

	/**
	* parse a link node
	*
	* @link http://www.topografix.com/gpx/1/1/#type_linkType
	*/

	protected function parseLink( $node ) {

		$meta_data = [];

		$meta_data[ 'href' ] = $node->attributes->getNamedItem( 'href' )->nodeValue; 

		// may have a text and or type child

		foreach ( $node->childNodes as $child ) {

			switch( strtolower( $child->nodeName )) {

				case 'text':

					$meta_data[ 'text' ] = $child->nodeValue;

					break;

				case 'type': // mime type of content

					$meta_data[ 'type' ] = $child->nodeValue;

					break;

			}

		}

		return $meta_data;

	} // end of parseLink()

	// -------------------------------------------------

	/**
	* parse a copyright node
	*
	* @link http://www.topografix.com/gpx/1/1/#type_copyrightType
	*/

	protected function parseCopyright( $node ) {

		$meta_data = [];

		$meta_data[ 'author' ] = $node->attributes->getNamedItem( 'author' )->nodeValue; 

		foreach ( $node->childNodes as $child ) {

			switch( strtolower( $child->nodeName )) {

				case 'year':

					$meta_data[ 'year' ] = $child->nodeValue;

					break;

				case 'license': // uri of license

					$meta_data[ 'license' ] = $child->nodeValue;

					break;

			}

		}

		return $meta_data;

	} // end of parseCopyright()

	// -------------------------------------------------

	/**
	* parse an author node
	*
	* @link http://www.topografix.com/gpx/1/1/#type_personType
	*/

	protected function parseAuthor( $node ) {

		$meta_data = [];

		foreach ( $node->childNodes as $child ) {

			switch( strtolower( $child->nodeName )) {

				case 'name':

					$meta_data[ 'name' ] = $child->nodeValue;

					break;

				case 'email': 

					$meta_data[ 'email' ][ 'id' ] = $child->attributes->getNamedItem( 'id' )->nodeValue; 
					$meta_data[ 'email' ][ 'domain' ] = $child->attributes->getNamedItem( 'domain' )->nodeValue; 

					break;

				case 'link': 

					$meta_data[ 'link' ] = $this->parseLink( $child );

					break;

			}

		}

		return $meta_data;


	} // end of parseAuthor()

	// -------------------------------------------------

	/**  
	* parse Garmin meta data extensions
	*/

	protected function parseWaypointExtension( $node ) {

		$meta_data = [];

		foreach ($node->childNodes as $child) {

			switch( strtolower( $child->nodeName )) {

				case 'gpxx:displaymode' :

					$meta_data[ 'gpxx:displaymode' ] = $child->nodeValue;

					break;

				case 'gpxx:categories':

					$meta_data[ 'categories' ] = $this->parseCategories( $child );

					break;

				case 'gpxx:address':

					$meta_data[ 'gpxx:address' ] = $this->parseAddress( $child );

					break;

				case 'gpxx:phonenumber':

					$meta_data[ 'gpxx:phonenumber' ] = $this->parsePhoneNumbers( $child );

					break;
			}

		}

	return $meta_data;

	} // end of parseWaypointExtensions

	// -------------------------------------------------

	/**  
	* parse Garmin meta data extensions for routes
	*
	* I assume to distinguish between routes calculated by the GPS unit itself
	* and routes generated elsewhere, routes calculated by Garmin GPS units
	* are included under a RoutePointExtension.
	*
	* We ignore the gpxx:SubClass nodes. Not sure what they are used for.
	*
	* @return array of lat/lon coords. We are not using Point objects here. 
	*/

	protected function parseRoutepointExtension( $node ) {

		$meta_data = [];

		foreach ($node->childNodes as $child) {

			switch( strtolower( $child->nodeName )) {

				case 'gpxx:rpt' :

					$lat = $child->attributes->getNamedItem( 'lat' )->nodeValue;
					$lon = $child->attributes->getNamedItem( 'lon' )->nodeValue;

					$meta_data[] = array( $lat, $lon ); 

					break;
			}

		}

	return $meta_data;

	} // end of parseRoutepointExtensions

	// -------------------------------------------------

	/**  
	* parse Garmin meta data extensions
	*/

	protected function parseCategories( $node ) {

		$meta_data = [];

		foreach ($node->childNodes as $child) {

			switch( strtolower( $child->nodeName )) {

				case 'gpxx:category':

					$meta_data[] = $child->nodeValue;

					break;

			}

		}

		return $meta_data;

	} // end of parseCategories

	// -------------------------------------------------

	/**  
	* parse Garmin address extension
	*/

	protected function parseAddress( $node ) {

		$meta_data = [];

		foreach ($node->childNodes as $child) {

			switch( strtolower( $child->nodeName )) {

				case 'gpxx:streetaddress' :

					$meta_data[ 'gpxx:streetaddress' ] = $child->nodeValue;

					break;

				case 'gpxx:city' :

					$meta_data[ 'gpxx:city' ] = $child->nodeValue;

					break;

				case 'gpxx:state' :

					$meta_data[ 'gpxx:state' ] = $child->nodeValue;

					break;

				case 'gpxx:country' :

					$meta_data[ 'gpxx:country' ] = $child->nodeValue;

					break;

				case 'gpxx:postalcode' :

					$meta_data[ 'gpxx:postalcode' ] = $child->nodeValue;

					break;

			}

		}

	return $meta_data;

	} // end of parseAddress()

	// -------------------------------------------------

	/**  
	* parse phone numbers
	*/

	protected function parsePhoneNumbers( $node ) {

		$meta_data = [];

		foreach ($node->childNodes as $child) {

			switch( strtolower( $child->nodeName )) {

				case 'gpxx:phonenumber':

					if (( $category =  $child->attributes->getNamedItem("Category")->nodeValue) != NULL ) {
						$meta_data[ $category ] = $child->nodeValue;
					} else {
						$meta_data[ 'primary' ] = $child->nodeValue;
					}

					break;

			}

		}

		return $meta_data;

	} // end of parsePhoneNumbers

	// -------------------------------------------------
  
	protected function geometryToGPX($geom) {
		$type = strtolower($geom->getGeomType());

		switch ($type) {
			case 'point':
				return $this->pointToGPX($geom);
				break;
			case 'linestring':
				return $this->linestringToGPX($geom);
				break;
			case 'polygon':
			case 'multipoint':
			case 'multilinestring':
			case 'multipolygon':
			case 'geometrycollection':
				return $this->collectionToGPX($geom);
				break;
		}

	} // end of geometryToGPX()

	// -------------------------------------------------

	/**
	* generate wpt xml
	*/
  
	private function pointToGPX($geom) {
		$gpx = '<'.$this->nss.'wpt lat="'.$geom->getY().'" lon="'.$geom->getX() . '"';

		if (( $meta_data = $geom->getMetaData()) != NULL ) {
			$gpx .= '>' . $this->metaDataToGPX( $meta_data ) . '</' . $this->nss . 'wpt>';
		} else {
			$gpx .= '/>';
		}

		return $gpx;

	} // end of pointToGPX()

	// -------------------------------------------------

	/**
	* Given a linestring generate a track or route
	*/
  
	private function linestringToGPX($geom) {

		$meta_data = $geom->getMetaData();

		if (( $meta_data == NULL ) || ( isset( $meta_data[ 'line_type' ] ) &&  $meta_data[ 'line_type' ] == 'trk' )) { 

			$gpx = $this->linestringToTrk( $geom );

		} else {

			$gpx = $this->linestringToRte( $geom ) ;

		}

		return $gpx;

	}

	// ---------------------------------------------------

	/**
	* given a linestring generate a track
	*/

	private function linestringToTrk( $geom ) {

		$gpx = '<'.$this->nss.'trk>';

		// if metadata contains junk this may generate incorrect GPX.

		if (( $meta_data = $geom->getMetaData()) != NULL ) {
			$gpx .= $this->metaDataToGPX( $meta_data );
		}

		// Not sure if this is correct, but it seems trkseg's 
		// don't support any feature metadata.

		$gpx .= '<' . $this->nss . 'trkseg>';
    
		foreach ($geom->getComponents() as $comp) {
			$gpx .= '<'.$this->nss.'trkpt lat="'.$comp->getY().'" lon="'.$comp->getX() . '"';

			if (( $meta_data = $comp->getMetaData()) != NULL ) {
				$gpx .= '>' . $this->metaDataToGPX( $meta_data ) . '</' . $this->nss . 'trkpt>';
			} else {
				$gpx .= '/>';
			}

		} // end of foreach.
    
		$gpx .= '</'.$this->nss.'trkseg></'.$this->nss.'trk>';
    
		return $gpx;

	} // end of linestringToTrk()

	// ---------------------------------------------------

	/**
	* given a linestring generate a route
	*
	* Garmin RoutePointExtensions are stored under the metadata. 
	*/

	private function linestringToRte( $geom ) {

		$gpx = '<'.$this->nss.'rte>';

		// if metadata contains junk this may generate incorrect GPX.

		if (( $meta_data = $geom->getMetaData()) != NULL ) {
			$gpx .= $this->metaDataToGPX( $meta_data );
		}

		foreach ($geom->getComponents() as $comp) {
			$gpx .= '<'.$this->nss.'rtept lat="'.$comp->getY().'" lon="'.$comp->getX() . '"';

			if (( $meta_data = $comp->getMetaData()) != NULL ) {
				$gpx .= '>' . $this->metaDataToGPX( $meta_data ) . '</' . $this->nss . 'rtept>';
			} else {
				$gpx .= '/>';
			}

		} // end of foreach.
    
		$gpx .= '</' . $this->nss . 'rte>';
    
		return $gpx;

	} // end of linestringToRte()

	// -------------------------------------------------

	/**
	* generate GPX from a geometrycollection
	*/
  
	public function collectionToGPX($geom) {
		$gpx = '';
		$components = $geom->getComponents();

		foreach ($geom->getComponents() as $comp) {
			$gpx .= $this->geometryToGPX($comp);
		}
    
		return $gpx;

	} // end of collectionToGPX()

	// -------------------------------------------------

	/**
	* parse features metadata child nodes 
	*
	* @param $node DOMNode root 
	* @return array list of meta data values
	*/

	protected function metaDataToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $key => $data ) {

			switch ( $key ) {

				case 'name' :

					$gpx .= '<' . $this->nss . 'name>' . $data . '</' . $this->nss . 'name>';

					break;

				case 'cmt' :

					$gpx .= '<' . $this->nss . 'cmt>' . $data . '</' . $this->nss . 'cmt>';

					break;

				case 'link' :

					// FIXME: Broken.

					$gpx .=  $this->linksToGPX( $data ) ;

					break;

				case 'ele':

					$gpx .= '<' . $this->nss . 'ele>' . $data . '</' . $this->nss . 'ele>';

					break;

				case 'time':

					$gpx .= '<' . $this->nss . 'time>' . $data . '</' . $this->nss . 'time>';

					break;

				case 'desc':

					$gpx .= '<' . $this->nss . 'desc>' . $data . '</' . $this->nss . 'desc>';

					break;

				case 'sym':

					$gpx .= '<' . $this->nss . 'sym>' . $data . '</' . $this->nss . 'sym>';

					break;

				case 'type':

					$gpx .= '<' . $this->nss . 'type>' . $data . '</' . $this->nss . 'type>';

					break;

				case 'author':

					$gpx .= $this->authorToGPX( $data );

					break;

				case 'copyright':

					$gpx .= $this->copyrightToGPX( $data );

					break;

				case 'extensions':

					$gpx .= $this->extensionsToGPX( $data );

					break;

			} // end of switch

		} // end of foreach

	return $gpx;

	} // end of metaDataToGPX()

	// ------------------------------------------------

	/**
	* generate GPX for links
	*
	* There may be any number of links included in a GPX file
	*
	* @param array $meta_data array of link objects.
	*/

	protected function linksToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $offset => $link ) {

			$gpx .= '<link href="' . $link[ 'href' ] . '>';

			if ( isset( $link[ 'text' ] ) ) {
				$gpx .= '<text>' . $link[ 'text' ] . '</text>';
			}

			if ( isset( $link[ 'type' ] )) {
				$gpx .= '<type>' . $link[ 'type' ] . '</type>';
			}

			$gpx .= '</link>';
				
		}

	} // end of linksToGPX()

	// -------------------------------------------------

	/**  
	* generate Garmin meta data extensions
	*/

	protected function extensionsToGPX( $meta_data ) {

		$gpx = '<' . $this->nss . 'extensions>';

		foreach ( $meta_data as $key => $data ) {

			switch( $key ) {

				case 'gpxx:waypointextension':

					$gpx .= '<' . $this->nss . 'gpxx:WaypointExtension>' . $this->waypointExtensionToGPX( $data ) . '</' . $this->nss . 'gpxx:WaypointExtension>';

					break;

				case 'gpxx:routepointextension':

					$gpx .= '<' . $this->nss . 'gpxx:RoutepointExtension>' . $this->routepointExtensionToGPX( $data ) . '</' . $this->nss . 'gpxx:RoutepointExtension>';

			}

		}

		$gpx .= '</' . $this->nss . 'extensions>'; 

		return $gpx;

	} // end of extensionsToGPX()

	// -------------------------------------------------

	/**  
	* generate Garmin waypoint extension
	*/

	protected function waypointExtensionToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $key => $data ) {

			switch( $key ) {

				case 'gpxx:displaymode' :

					$gpx .= '<' . $this->nss . 'gpxx:DisplayMode>' . $data . '</' . $this->nss . 'gpxx:DisplayMode>';

					break;

				case 'gpxx:Categories':

					$gpx .= '<' . $this->nss . 'gpxx:Categories>' . $this->categoriesToGPX( $data ) . '</' . $this->nss . 'gpxx:Categories>';

					break;

				case 'gpxx:address':

					$gpx .= '<' . $this->nss . 'gpxx:Address>' . $this->addressToGPX( $data ) . '</' . $this->nss . 'gpxx:Address>';

					break;

				case 'gpxx:phonenumber':

					$gpx .= '<' . $this->nss . 'gpxx:PhoneNumber>' . $this->phonenumberToGPX( $data ) . '</' . $this->nss . 'gpxx:PhoneNumber>';

					break;
			}

		}

	return $gpx;

	} // end of waypointExtensionsToGPX()

	// -------------------------------------------------

	/**  
	* generate Garmin routepoint extension points
	*
	* The routepoint extension data is a simple array of lat,lons
	*/

	protected function routepointExtensionToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $offset => $array ) {

			$gpx .= '<' . $this->nss . 'gpxx:rpt lat="' . $array[0] . '" lon="' . $array[1] . '" />';

		}

	return $gpx;

	} // end of routepointExtensionsToGPX()

	// -------------------------------------------------

	/**  
	* generate Garmin category extensions
	*/

	protected function categoriesToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $key => $data ) {

			switch( $key ) {

				case 'gpxx:category':

					foreach ( $data as $category ) {

						$gpx .= '<' . $this->nss . 'gpxx:Category>' . $category . '</' . $this->nss . 'gpxx:Category>';

					}

					break;

			}

		}

		return $gpx;

	} // end of categoriesToGPX()

	// -------------------------------------------------

	/**  
	* generate Garmin address extension
	*/

	protected function addressToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $key => $data ) {

			switch( $key ) {

				case 'gpxx:streetaddress' :

					$gpx .= '<' . $this->nss . 'gpxx:StreetAddress>' . $data . '</' . $this->nss . 'gpxx:StreetAddress>';

					break;

				case 'gpxx:city' :

					$gpx .= '<' . $this->nss . 'gpxx:City>' . $data . '</' . $this->nss . 'gpxx:City>';

					break;

				case 'gpxx:state' :

					$gpx .= '<' . $this->nss . 'gpxx:State>' . $data . '</' . $this->nss . 'gpxx:State>';

					break;

				case 'gpxx:postalcode' :

					$gpx .= '<' . $this->nss . 'gpxx:PostalCode>' . $data . '</' . $this->nss . 'gpxx:PostalCode>';

					break;

			}

		}

		return $gpx;

	} // end of addressToGPX()

	// -------------------------------------------------

	/**  
	* generate phone numbers
	*/

	protected function phoneNumbersToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $key => $data ) {

			switch( $key ) {

				case 'gpxx:phonenumber':

					foreach ( $data as $category => $value ) {

						$gpx .= '<' . $this->nss . 'gpxx:PhoneNumber';

						if ( $category != 'primary' ) {
							$gpx .= ' Category="' . $category . '"';
						}

						$gpx .= '>' . $value . '</' . $this->nss . 'gpxx:PhoneNumber>';

					}

					break;

			}

		}

		return $gpx;

	} // end of phoneNumbersToGPX()

	// -------------------------------------------------

	/**
	* generate a copyright node
	*
	* @link http://www.topografix.com/gpx/1/1/#type_copyrightType
	*/

	protected function copyrightToGPX( $meta_data ) {

		$gpx = '<copyright author="' . $meta_data[ 'author' ] . '">';

		if ( isset( $meta_data[ 'year' ] )) {
			$gpx .= '<year>' . $meta_data[ 'year' ] . '</year>';

		}

		if ( isset( $meta_data[ 'license' ] )) {

			$gpx .= '<license>' . $meta_data[ 'license' ] . '</license>';
		}

		return $gpx;

	} // end of copyrightToGPX()

	// -------------------------------------------------

	/**
	* generate an author node
	*
	* @link http://www.topografix.com/gpx/1/1/#type_personType
	*/

	protected function authorToGPX( $meta_data ) {

		$gpx = '';

		foreach ( $meta_data as $key => $data ) {

			switch( $key ) {

				case 'name':

					$gpx .= '<name>' . $meta_data[ 'name' ] . '</name>';

					break;

				case 'email': 

					$gpx .= '<email>';

					$gpx .= '<id>' . $meta_data[ 'email' ][ 'id' ] . '</id>';
					$gpx .= '<domain>' . $meta_data[ 'domain' ] . '</domain>';

					break;

				case 'link': 

					$gpx .= $this->linkToGPX( $data );

					break;

			}

		}

		return $gpx;


	} // end of authorToGPX()

} // end of class GPX

// END
