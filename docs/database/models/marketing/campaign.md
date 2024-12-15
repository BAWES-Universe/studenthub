# Campaign Model

## Overview
The Campaign model represents marketing campaigns and recruitment drives. It manages promotional activities and tracks candidate acquisition channels.

## Table Name
`campaign`

## Primary Key
- `campaign_id` (integer)

## Key Attributes

### Basic Information
- `campaign_name` (string) - Campaign name
- `campaign_description` (text) - Campaign description
- `campaign_type` (string) - Type of campaign
- `campaign_source` (string) - Source/channel of campaign
- `campaign_medium` (string) - Marketing medium used
- `campaign_content` (text) - Campaign content details

### Tracking
- `utm_source` (string) - UTM source parameter
- `utm_medium` (string) - UTM medium parameter
- `utm_campaign` (string) - UTM campaign name
- `utm_term` (string) - UTM term parameter
- `utm_content` (string) - UTM content parameter

### Timeline
- `campaign_start_date` (datetime) - Campaign start date
- `campaign_end_date` (datetime) - Campaign end date
- `campaign_duration` (integer) - Duration in days

### Performance Metrics
- `target_audience` (string) - Target demographic
- `expected_reach` (integer) - Expected reach count
- `actual_reach` (integer) - Actual reach achieved
- `conversion_rate` (decimal) - Conversion rate
- `cost_per_acquisition` (decimal) - Cost per acquisition
- `budget` (decimal) - Campaign budget
- `spent` (decimal) - Amount spent

### Status & Control
- `campaign_status` (integer) - Campaign status
  - STATUS_DRAFT (0)
  - STATUS_ACTIVE (10)
  - STATUS_PAUSED (20)
  - STATUS_COMPLETED (30)
  - STATUS_CANCELLED (40)
- `is_featured` (integer) - Featured campaign flag
- `deleted` (integer) - Soft delete flag
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Foreign Keys
- `created_by` -> `staff.staff_id`
- `updated_by` -> `staff.staff_id`

## Relationships

### Has Many
- `candidates` -> `Candidate[]`
- `emailCampaigns` -> `EmailCampaign[]`
- `campaignStats` -> `CampaignStats[]`

### Belongs To
- `creator` -> `Staff`
- `updater` -> `Staff`

## Behaviors
- TimestampBehavior
- BlameableBehavior

## Implementation Details
- Uses soft deletion
- Implements UTM parameter tracking
- Tracks campaign performance metrics
- Integrates with email marketing system
- Supports multiple campaign types

## Common Operations
```php
// Find active campaigns
Campaign::find()
    ->where(['campaign_status' => Campaign::STATUS_ACTIVE])
    ->andWhere(['<=', 'campaign_start_date', date('Y-m-d')])
    ->andWhere(['>=', 'campaign_end_date', date('Y-m-d')])
    ->all();

// Get campaign performance
Campaign::find()
    ->select([
        'campaign_id',
        'campaign_name',
        'actual_reach',
        'conversion_rate',
        'cost_per_acquisition'
    ])
    ->where(['campaign_id' => $id])
    ->asArray()
    ->one();

// Get candidates from campaign
Campaign::find()
    ->with(['candidates'])
    ->where(['campaign_id' => $id])
    ->one();

// Track campaign metrics
$campaign->updateMetrics();
``` 