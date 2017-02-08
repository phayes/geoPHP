<?php

use Phayes\GeoPHP\GeoPHP;

class AdaptersTests extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
    }

    function testAdapters()
    {
        foreach (scandir('./input') as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                $format = $parts[1];
                $input = file_get_contents('./input/' . $file);
                echo "\nloading: " . $file . " for format: " . $format;
                $geometry = GeoPHP::load($input, $format);

                // Test adapter output and input. Do a round-trip and re-test
                foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
                    if ($adapter_key != 'google_geocode') { //Don't test google geocoder regularily. Uncomment to test
                        $output = $geometry->out($adapter_key);
                        $this->assertNotNull($output, "Empty output on " . $adapter_key);
                        if ($output) {
                            $adapter_class = '\Phayes\GeoPHP\Adapters\\' . $adapter_class;
                            $adapter_loader = new $adapter_class();
                            $test_geom_1 = $adapter_loader->read($output);
                            $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));
                            $this->assertEquals($test_geom_1->out('wkt'), $test_geom_2->out('wkt'),
                                "Mismatched adapter output in " . $adapter_class . ' (test file: ' . $file . ')');
                        }
                    }
                }

                // Test to make sure adapter work the same wether GEOS is ON or OFF
                // Cannot test methods if GEOS is not intstalled
                if (!geoPHP::geosInstalled()) {
                    return;
                }

                foreach (geoPHP::getAdapterMap() as $adapter_key => $adapter_class) {
                    if ($adapter_key != 'google_geocode') { //Don't test google geocoder regularily. Uncomment to test
                        // Turn GEOS on
                        geoPHP::geosInstalled(true);

                        $output = $geometry->out($adapter_key);
                        if ($output) {
                            $adapter_loader = new $adapter_class();

                            $test_geom_1 = $adapter_loader->read($output);

                            // Turn GEOS off
                            geoPHP::geosInstalled(false);

                            $test_geom_2 = $adapter_loader->read($output);

                            // Turn GEOS back On
                            geoPHP::geosInstalled(true);

                            // Check to make sure a both are the same with geos and without
                            $this->assertEquals($test_geom_1->out('wkt'), $test_geom_2->out('wkt'),
                                "Mismatched adapter output between GEOS and NORM in " . $adapter_class . ' (test file: ' . $file . ')');
                        }
                    }
                }
            }
        }
    }
}
