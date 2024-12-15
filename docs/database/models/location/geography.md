# Geography & Location Models

## Country Model

### Overview
The Country model represents countries and their specific details.

### Table Name
`country`

### Primary Key
- `country_id` (integer)

### Key Attributes

#### Basic Information
- `country_code` (char(2)) - ISO country code
- `country_name` (string) - Name in English
- `country_name_ar` (string) - Name in Arabic
- `nationality` (string) - Nationality name
- `nationality_ar` (string) - Nationality in Arabic
- `currency_code` (char(3)) - Currency code
- `phone_code` (string) - Calling code

#### Configuration
- `is_active` (integer) - Active status
- `is_gcc` (integer) - GCC country flag
- `is_arab` (integer) - Arab country flag
- `time_zone` (string) - Default timezone
- `date_format` (string) - Date format
- `week_start` (integer) - Week start day

#### Localization
- `language_codes` (json) - Official languages
- `address_format` (json) - Address template
- `postal_code_format` (string) - Postal format
- `phone_format` (string) - Phone format

## City Model

### Overview
Represents cities and urban areas.

### Table Name
`city`

### Key Attributes

#### Basic Information
- `city_name` (string) - Name in English
- `city_name_ar` (string) - Name in Arabic
- `country_id` (integer) - Country reference
- `state_id` (integer) - State reference
- `city_code` (string) - City identifier

#### Geography
- `latitude` (decimal) - Geographic latitude
- `longitude` (decimal) - Geographic longitude
- `elevation` (decimal) - Elevation (meters)
- `area` (decimal) - City area (km²)
- `population` (integer) - Population count

#### Administrative
- `is_capital` (integer) - Capital city flag
- `is_major` (integer) - Major city flag
- `admin_level` (integer) - Admin hierarchy
- `city_type` (string) - City classification

## Area Model

### Overview
Manages districts and areas within cities.

### Table Name
`area`

### Key Attributes

#### Basic Information
- `area_name` (string) - Name in English
- `area_name_ar` (string) - Name in Arabic
- `city_id` (integer) - City reference
- `area_code` (string) - Area identifier
- `postal_codes` (json) - Postal codes

#### Geography
- `boundaries` (polygon) - Area boundaries
- `center_lat` (decimal) - Center latitude
- `center_lng` (decimal) - Center longitude
- `area_size` (decimal) - Area size (km²)

#### Classification
- `area_type` (string) - Area type
  - RESIDENTIAL
  - COMMERCIAL
  - INDUSTRIAL
  - MIXED
- `development_status` (string) - Development state
- `population_density` (integer) - Population density

## Location Model

### Overview
Represents specific locations and addresses.

### Table Name
`location`

### Key Attributes

#### Address Details
- `address_line1` (string) - Street address
- `address_line2` (string) - Additional info
- `area_id` (integer) - Area reference
- `city_id` (integer) - City reference
- `country_id` (integer) - Country reference
- `postal_code` (string) - Postal/ZIP code

#### Coordinates
- `latitude` (decimal) - Location latitude
- `longitude` (decimal) - Location longitude
- `altitude` (decimal) - Elevation level
- `accuracy` (decimal) - Coordinate accuracy

#### Classification
- `location_type` (string) - Location type
  - OFFICE
  - WAREHOUSE
  - RETAIL
  - RESIDENTIAL
- `building_type` (string) - Building category
- `floor_number` (integer) - Floor level
- `unit_number` (string) - Unit identifier

#### Additional Info
- `landmark` (string) - Nearby landmark
- `directions` (text) - Access directions
- `parking_info` (text) - Parking details
- `accessibility` (json) - Access features

## Common Operations

```php
// Get active countries
Country::find()
    ->where(['is_active' => 1])
    ->orderBy(['country_name' => SORT_ASC])
    ->all();

// Get cities by country
City::find()
    ->where(['country_id' => $countryId])
    ->andWhere(['is_major' => 1])
    ->orderBy(['city_name' => SORT_ASC])
    ->all();

// Get areas by city
Area::find()
    ->where([
        'city_id' => $cityId,
        'area_type' => 'RESIDENTIAL'
    ])
    ->orderBy(['area_name' => SORT_ASC])
    ->all();

// Find nearby locations
Location::find()
    ->select([
        '*',
        'ST_Distance(
            point(longitude, latitude),
            point(:lng, :lat)
        ) as distance'
    ])
    ->where(['location_type' => $type])
    ->having(['<=', 'distance', $radius])
    ->orderBy(['distance' => SORT_ASC])
    ->all();
```

## Implementation Details
- Geocoding support
- Distance calculations
- Boundary management
- Address formatting
- Location validation
- Timezone handling

## Business Rules
1. Country activation rules
2. City classification
3. Area boundaries
4. Address validation
5. Location accuracy
6. Timezone compliance

## Reporting Features
1. Geographic distribution
2. Population density
3. Coverage analysis
4. Location clustering
5. Distance matrices
6. Territory mapping

## Security Considerations
1. Location data privacy
2. Coordinate accuracy
3. Address verification
4. Access restrictions
5. Data sensitivity