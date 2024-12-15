# Email Campaign Models

## Email Campaign Model

### Overview
The Email Campaign model manages marketing and communication campaigns through email channels.

### Table Name
`email_campaign`

### Primary Key
- `campaign_id` (integer)

### Key Attributes

#### Basic Information
- `campaign_name` (string) - Campaign name
- `campaign_description` (text) - Campaign description
- `campaign_type` (string) - Type of campaign
  - MARKETING
  - NEWSLETTER
  - ANNOUNCEMENT
  - NOTIFICATION
  - ONBOARDING
- `subject_line` (string) - Email subject
- `from_name` (string) - Sender name
- `reply_to` (string) - Reply-to address
- `template_id` (string) - Email template reference

#### Content
- `email_content` (text) - Email content/body
- `plain_text_content` (text) - Plain text version
- `preview_text` (string) - Email preview text
- `language` (string) - Content language
- `dynamic_fields` (json) - Personalization fields
- `attachments` (json) - Attachment details

#### Targeting
- `recipient_list` (json) - Target recipients
- `segment_id` (integer) - Target segment
- `filter_criteria` (json) - Filtering rules
- `exclusion_list` (json) - Excluded recipients
- `max_recipients` (integer) - Recipient limit

#### Schedule
- `scheduled_at` (datetime) - Scheduled send time
- `time_zone` (string) - Schedule time zone
- `send_window_start` (time) - Daily send window start
- `send_window_end` (time) - Daily send window end
- `expiry_date` (datetime) - Campaign expiry

#### Tracking
- `utm_source` (string) - UTM source
- `utm_medium` (string) - UTM medium
- `utm_campaign` (string) - UTM campaign
- `tracking_enabled` (integer) - Tracking flag
- `click_tracking` (integer) - Click tracking flag
- `open_tracking` (integer) - Open tracking flag

#### Status Information
- `campaign_status` (integer) - Status
  - STATUS_DRAFT (0)
  - STATUS_SCHEDULED (10)
  - STATUS_SENDING (20)
  - STATUS_COMPLETED (30)
  - STATUS_PAUSED (40)
  - STATUS_CANCELLED (50)
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Email Campaign Filter Model

### Overview
Defines filtering rules for campaign recipient targeting.

### Table Name
`email_campaign_filter`

### Key Attributes
- `campaign_id` (integer) - Campaign reference
- `filter_type` (string) - Type of filter
  - DEMOGRAPHIC
  - BEHAVIORAL
  - GEOGRAPHIC
  - CUSTOM
- `field_name` (string) - Filter field
- `operator` (string) - Comparison operator
- `value` (string) - Filter value
- `logic_group` (integer) - Grouping identifier
- `order` (integer) - Filter order

## Mail Log Model

### Overview
Tracks email delivery and engagement metrics.

### Table Name
`mail_log`

### Key Attributes
- `campaign_id` (integer) - Campaign reference
- `recipient_email` (string) - Recipient address
- `recipient_name` (string) - Recipient name
- `sent_at` (datetime) - Send timestamp
- `delivered_at` (datetime) - Delivery timestamp
- `opened_at` (datetime) - First open timestamp
- `clicked_at` (datetime) - First click timestamp
- `bounce_type` (string) - Bounce category
- `bounce_reason` (string) - Bounce details
- `status` (string) - Delivery status
- `tracking_data` (json) - Engagement data

## Common Operations

```php
// Create new campaign
$campaign = new EmailCampaign([
    'campaign_type' => EmailCampaign::TYPE_MARKETING,
    'subject_line' => $subject
]);
$campaign->setTemplate($templateId);

// Add campaign filters
$campaign->addFilter([
    'filter_type' => 'DEMOGRAPHIC',
    'field_name' => 'country',
    'operator' => '=',
    'value' => 'UAE'
]);

// Schedule campaign
$campaign->schedule([
    'scheduled_at' => $datetime,
    'time_zone' => 'Asia/Dubai'
]);

// Get campaign statistics
MailLog::find()
    ->select([
        'campaign_id',
        'COUNT(*) as sent',
        'SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened',
        'SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked'
    ])
    ->where(['campaign_id' => $id])
    ->groupBy(['campaign_id'])
    ->one();
```

## Implementation Details
- Template system integration
- Dynamic content personalization
- Delivery rate management
- Bounce handling
- Engagement tracking
- A/B testing support

## Business Rules
1. Recipient opt-out compliance
2. Send window restrictions
3. Rate limiting per domain
4. Bounce handling policies
5. Content validation rules
6. Scheduling constraints

## Security & Compliance
1. SPAM compliance
2. Unsubscribe handling
3. Privacy regulations
4. Data retention
5. Content filtering
6. Authentication (SPF/DKIM)
``` 