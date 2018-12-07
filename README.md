# SilverStripe GIS Module

[![Build Status](https://travis-ci.org/smindel/silverstripe-gis.svg?branch=master)](https://travis-ci.org/smindel/silverstripe-gis)

Adds support for geographic types.

## Features

- adds new data type Geography to DataObjects
- POINT, LINESTRING, POLYGON and MULTIPOLYGON types
- built in support for WGS 84 / EPSG:4326
- supports alternative projection
- MapField to edit a DataObject's geography (currently only supports Point, LineString and Polygon) in a form
- GridFieldMap component to search for DataObjects on a map
- map widgets for MapField and GridFieldMap are powered by Leaflet
- DataList filters
    - Within(Geography)
    - DWithin(Geography,distance), not supported by MariaDB
    - Intersects(Geograpy)
- Map tile service
- GeoJson web service
- GeoJson importer

## Requirements

- MySQL 5.7+ or Postgres with PostGIS extension
- SilverStripe framework 4

## Installation

    $ composer require smindel/silverstripe-gis dev-master`
    $ vendor/bin/sake dev/build flush=all

### MySQL

MySQL natively supports geodetic coordinate systems for geometries since version 5.7.6.

### MariaDB

MariaDB does not currently support ST_Distance_Sphere(), so that you cannot calculate distances.

### Postgres

When using Postgres you have to install PostGIS:

    sudo apt-get install postgis
    sudo -u postgres psql SS_gis -c "create extension postgis;"

If you get errors try:

    sudo apt-get install postgresql-9.5-postgis-scripts
    sudo apt-get install postgresql-9.5-postgis-2.2

## Configuration

### Alternative projections

By default the module uses WGS 84 aka LatLon (EPSG:4326). You can register other [projections in proj4 format](https://epsg.io/) change the default:

app/_config/config.yml
    Smindel\GIS\ORM\FieldType\DBGeography:
      default_projection: 2193
      projections:
         2193: "+proj=tmerc +lat_0=0 +lon_0=173 +k=0.9996 +x_0=1600000 +y_0=10000000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs"

or

app/_config.php
    // defaults to 4326
    DBGeography::config()->set('default_projection', 2193);
    // New Zealand Transverse Mercator 
    DBGeography::config()->set('projections', [
        2193 => '+proj=tmerc +lat_0=0 +lon_0=173 +k=0.9996 +x_0=1600000 +y_0=10000000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs',
    ]);

### Default map center

MapField and GridFieldMap will show the default_location if no data is available.

app/_config/config.yml
    Smindel\GIS\ORM\FieldType\DBGeography:
      default_location: [5900755,1782733]
      
app/_config.php
    // defaults to [10,53.5]
    DBGeography::config()->get('default_location', [5900755,1782733]);

### TileRender

app/_config/config.yml
    SilverStripe\Core\Injector\Injector:
      TileRenderer:
        class: Smindel\GIS\Service\GDTileRenderer

### Adding Geography attributes to DataObjects

Add Geography attributes like any other attribute using the new type Geography:

`
class City extends DataObject
{
    private static $db = [
        'Name' => 'Varchar',
        'Location' => 'Geography',
    ];
}
`

### Transforming Geographies from PHP to WKT

Internally Geographies are represented as extended Well Known Text (eWKT, https://en.wikipedia.org/wiki/Well-known_text#Geometric_objects). You can use the helper DBGeography::from_array() to create eWKT from PHP arrays:

- `DBGeography::from_array([10,30])` creates "SRID=0000;POINT (30 10)"
- `DBGeography::from_array([[10,30],[30,10],[40,40]])` creates "SRID=0000;LINESTRING (30 10, 10 30, 40 40)"
- `DBGeography::from_array([[[10,30],[40,40],[40,20],[20,10],[10,30]]])` creates "SRID=0000;POLYGON ((30 10, 40 40, 20 40, 10 20, 30 10))"

### Spacial queries

#### Query Within

To find all DataObjects within a polygon:

`$cities = City::get()->filter('Location:WithinGeo', DBGeography::from_array([[[10,30],[40,40],[40,20],[20,10],[10,30]]]));`

#### Query Overlap

To find all DataObjects intersects with a polygon:

`$cities = City::get()->filter('Location:IntersectsGeo', DBGeography::from_array([[[10,30],[40,40],[40,20],[20,10],[10,30]]]));`

#### Query Whithin Distance

To find all DataObjects within a 100000m of a point:

`$cities = City::get()->filter('Location:DWithinGeo', [DBGeography::from_array([10,30]), 100000]);`

#### Compute Distance

To compute the distance in meters between two points:

`$distance = DBGeography::distance(DBGeography::from_array([10,30]), DBGeography::from_array([40,40]));`

### Geographies and forms

The module comes with a new form field type, the MapField. For a point it renders a point picker widget. For other Geography types the field is readonly.

A GridField component for displaying and filtering Geographies is under construction.

### ToDo

- gridfield: replace webservice with a filtered requirements::customScript(geojson)
- enter coordinates into MapField
- readonly mapfield
- cluster GridFieldMap
- Web service filter
- Polygon editor editable
- WFS
- WMS