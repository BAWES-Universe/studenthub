# Location Models

## Store Model

### Overview
The Store model represents physical retail locations and business establishments.

### Table Name
`store`

### Primary Key
- `store_id` (integer)

### Key Attributes

#### Basic Information
- `store_name` (string) - Store name in English
- `store_name_ar` (string) - Store name in Arabic
- `store_code` (string) - Unique store identifier
- `store_type` (string) - Type of store
  - RETAIL
  - RESTAURANT
  - SERVICE
  - OFFICE
- `brand_id` (integer) - Brand reference
- `mall_id` (integer) - Mall reference

#### Contact Details
- `store_phone` (string) - Contact phone
- `store_email` (string) - Contact email
- `store_manager` (string) - Manager name
- `store_hours` (json) - Operating hours
- `delivery_available` (integer) - Delivery flag

#### Location
- `store_floor` (string) - Floor number/name
- `store_unit` (string) - Unit number
- `store_area` (decimal) - Store area size
- `latitude` (decimal) - Geographic latitude
- `longitude` (decimal) - Geographic longitude
- `location_description` (text) - Location details

#### Status Information
- `store_status` (integer) - Store status
  - STATUS_ACTIVE (10)
  - STATUS_CLOSED (0)
  - STATUS_TEMPORARY_CLOSED (2)
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Mall Model

### Overview
Represents shopping malls and commercial complexes.

### Table Name
`mall`

### Key Attributes

#### Basic Information
- `mall_name` (string) - Mall name in English
- `mall_name_ar` (string) - Mall name in Arabic
- `mall_code` (string) - Unique mall identifier
- `area_id` (integer) - Area reference
- `country_id` (integer) - Country reference

#### Contact Details
- `mall_phone` (string) - Contact phone
- `mall_email` (string) - Contact email
- `mall_website` (string) - Website URL
- `management_contact` (string) - Management contact

#### Location
- `address` (text) - Physical address
- `latitude` (decimal) - Geographic latitude
- `longitude` (decimal) - Geographic longitude
- `total_area` (decimal) - Total mall area
- `parking_capacity` (integer) - Parking spaces

#### Operating Hours
- `working_hours` (json) - Operating schedule
- `delivery_hours` (json) - Delivery times
- `special_hours` (json) - Holiday/special hours
- `ramadan_hours` (json) - Ramadan schedule

#### Features
- `facilities` (json) - Available facilities
- `services` (json) - Available services
- `amenities` (json) - Mall amenities
- `parking_info` (json) - Parking details

## Brand Model

### Overview
Represents retail and business brands operating in the system.

### Table Name
`brand`

### Key Attributes

#### Basic Information
- `brand_name` (string) - Brand name in English
- `brand_name_ar` (string) - Brand name in Arabic
- `brand_code` (string) - Unique brand identifier
- `company_id` (integer) - Company reference
- `industry_type` (string) - Industry category

#### Brand Assets
- `brand_logo` (string) - Logo file path
- `brand_colors` (json) - Brand colors
- `brand_guidelines` (text) - Brand guidelines
- `marketing_assets` (json) - Marketing materials

#### Contact Information
- `brand_email` (string) - Contact email
- `brand_phone` (string) - Contact phone
- `brand_website` (string) - Website URL
- `social_media` (json) - Social media links

#### Business Details
- `founding_year` (integer) - Establishment year
- `target_market` (string) - Target demographic
- `market_position` (string) - Market positioning
- `competitor_info` (json) - Competitor analysis

## Common Operations

```php
// Get mall stores
Store::find()
    ->where([
        'mall_id' => $mallId,
        'store_status' => Store::STATUS_ACTIVE
    ])
    ->all();

// Get brand stores by area
Store::find()
    ->joinWith('mall')
    ->where([
        'brand_id' => $brandId,
        'mall.area_id' => $areaId
    ])
    ->all();

// Get mall operating hours
Mall::find()
    ->select(['mall_id', 'mall_name', 'working_hours'])
    ->where(['mall_id' => $id])
    ->asArray()
    ->one();

// Get brand performance by mall
Store::find()
    ->select([
        'mall_id',
        'COUNT(*) as store_count',
        'AVG(performance_rating) as avg_performance'
    ])
    ->where(['brand_id' => $brandId])
    ->groupBy(['mall_id'])
    ->all();
```

## Implementation Details
- Supports multi-language content
- Handles location-based queries
- Manages operating hours
- Tracks store performance
- Supports brand management
- Handles mall facilities

## Business Rules
1. Store codes must be unique
2. Operating hours validation
3. Location coordinates required
4. Brand association required
5. Status change tracking
6. Contact info validation

## Security Notes
1. Location data protection
2. Brand asset security
3. Contact info privacy
4. Performance data access
5. Management permissions 