<?php

namespace Smindel\GIS\Service;

use Smindel\GIS\GIS;

class GeoJsonImporter
{
    public static function import($class, $geoJson, $propertyMap = null, $geoProperty = null, $featureCallback = null)
    {
        $geoProperty = $geoProperty ?: GIS::of($class);
        $features = (is_array($geoJson) ? $geoJson : json_decode($geoJson, true))['features'];
        foreach ($features as $feature) {
            if (is_callable($featureCallback)) {
                $feature = $featureCallback($feature);
            }

            if ($feature['type'] != 'Feature') {
                continue;
            }

            if ($propertyMap === null) {
                $propertyMap = array_intersect(array_keys($class::config()->get('db')), array_keys($feature['properties']));
                $propertyMap = array_combine($propertyMap, $propertyMap);
            }

            $obj = $class::create();
            $obj->$geoProperty = GIS::to_ewkt($feature['geometry']);
            foreach ($propertyMap as $doProperty => $jsonProperty) {
                $obj->$doProperty = $feature['properties'][$jsonProperty];
            }
            $obj->write();
        }
    }
}
