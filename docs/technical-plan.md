# Fargona Invest GIS Technical Plan

## Goal

Create a high-performance GIS system for Fargona Invest where Excel rows are
linked to KML/KMZ geometries. Users should be able to open the map, switch base
map types, click a feature, and see the matching Excel data.

## Core Decisions

- Use Laravel as the backend API and import engine.
- Use Nuxt 3 as the frontend application.
- Use MySQL 8 as the database to avoid extra VDS setup.
- Keep GIS logic behind service classes so PostGIS can be adopted later if the
  project outgrows MySQL spatial support.
- Use OpenLayers for the map because it is stronger for GIS-style tools such as
  drawing, measuring, editing, layer control, and QGIS-like workflows.

## Database Direction

Main tables:

- `projects`: logical project groups.
- `layers`: imported or manually created map layers.
- `map_features`: spatial objects from KML/KMZ.
- `feature_properties`: normalized searchable attributes when needed.
- `excel_imports`: uploaded Excel batches.
- `excel_rows`: raw and normalized Excel rows.
- `attachments`: files/images linked to features.
- `users`, `roles`, `permissions`: access control.

`map_features` important fields:

- `id`
- `project_id`
- `layer_id`
- `name`
- `external_id`
- `cadastre_number`
- `geometry`
- `geometry_simplified`
- `properties` JSON
- `excel_row_id`
- `status`
- timestamps

## Import Pipeline

1. User uploads Excel and KML/KMZ.
2. Laravel stores the original files.
3. Excel is parsed into normalized rows.
4. KMZ is unzipped and KML is extracted.
5. KML placemarks are converted to geometry plus properties.
6. Features are matched to Excel rows by a configured key:
   - cadastral number
   - object ID
   - object name
   - another selected Excel column
7. Geometry is stored in MySQL spatial columns.
8. Searchable attributes are indexed.
9. Import report shows matched, unmatched, and duplicate records.

## Map Frontend

Base map modes:

- Normal
- Satellite
- Hybrid

Map tools for MVP:

- Layer list and visibility toggle
- Feature click details panel
- Search by name, ID, cadastral number, or Excel attributes
- Attribute filters
- Zoom to object
- Distance and area measurement
- Import status screen

QGIS/QField-like later features:

- Draw point/line/polygon
- Edit feature geometry
- Attach field photos/files
- GPS location
- Offline package support
- Export Excel, GeoJSON, KML
- Change history and audit log

## Performance Strategy With MySQL

- Do not render raw KML/KMZ directly in the browser for large files.
- Parse imports on the backend.
- Store spatial data in MySQL geometry columns.
- Add spatial indexes to feature geometry.
- Load features by bounding box and zoom level.
- Store simplified geometry for low zoom levels.
- Cache expensive map responses.
- Return lightweight GeoJSON only for visible map area.
- Consider vector tiles if the dataset becomes very large.

## API Draft

- `POST /api/imports/excel`
- `POST /api/imports/kml`
- `POST /api/imports/kmz`
- `POST /api/imports/{id}/match`
- `GET /api/projects`
- `GET /api/layers`
- `GET /api/map/features?bbox=&zoom=&layers=`
- `GET /api/features/{id}`
- `PATCH /api/features/{id}`
- `GET /api/search?q=`
- `GET /api/filters`

## First Build Checklist

1. Scaffold Laravel backend.
2. Scaffold Nuxt frontend.
3. Configure MySQL connection.
4. Add initial migrations.
5. Implement KML/KMZ parser service.
6. Implement Excel parser service.
7. Implement matching service.
8. Add map feature bbox endpoint.
9. Build OpenLayers map UI.
10. Build import and matching screens.

