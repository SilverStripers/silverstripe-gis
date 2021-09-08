# Smindel\GIS\Control\WebFeatureService (experiemental)

The configuration of the WebFeatureService applies to all WFS you expose. They can be overwritten in [your DataObject classes](DataObject-Example.md).

## Disclaimer

This service is not yet fully implemented. It supports the DescribeFeatureType and GetFeature operations, the GetCapabilities operation is missing. Leaflet works without it.

Filters are implemented the same as in the GeoJson and WMS and *not* according to WFS. This will be fixed soon. Currently

Transactions are not yet implemented.

## Configuration

### DataObject config

The service can just be turned on in the DataObject class with the default settings like this:

```php
private static $webfeatureservice = true; // enables WFS with default settings
```

... or control one or all configurable aspects:

```php
private static $webfeatureservice = [
    'geometry_field' => 'Location',     // set geometry field explicitly
    'searchable_fields' => [            // set fields that can be searched by through the service
        'FirstName' => [
            'title' => 'given name',
            'filter' => 'ExactMatchFilter',
        ],
        'Surname' => [
            'title' => 'name',
            'filter' => 'PartialMatchFilter',
        ],
    ],
    'code' => 'ADMIN',                  // restrict access to admins (see: Permission::check())
    'record_provider' => [              // callable to return a DataList of records to be served
        'SomeClass',                    // receives 2 parameters:
        'static_method'                 // the HTTPRequest and a reference which you can set to true
    ],                                  // in order to skip filtering further down in the stack
    'property_map' => [                 // map DataObject fields to WFS properties
        'ID' => 'id',
        'FirstName' => 'given name',
        'Surname' => 'name',
    ],
];
```

## Accessing the endpoint

In order to access the endpoint you have to use the namespaced class name with the backslashes replaced with dashes:

    http://yourdomain/webfeatureservice/VendorName-ProductName-DataObjectClassName

If you want to filter records, you can do so by using the configured or default search fields. You can even use filter modifiers:

    http://yourdomain/webfeatureservice/VendorName-ProductName-DataObjectClassName?FieldName:StartsWith:not=searchTerm

A Leaflet layer can be created like this, if you add the Leaflet.WFST and Proj4Leaflet plugins:

```javascript
L.wfs({
  url: 'webfeatureservice/City',
  typeNS: 'myns',
  typeName: 'City',
  geometryField: 'Area',
  crs: L.CRS.EPSG4326,
}).bindPopup(function (layer) { return layer.feature.properties.Name; }).addTo(map);
```
