# Notification Models

## Mobile Notification Model

### Overview
The Mobile Notification model manages push notifications and in-app messages for mobile devices.

### Table Name
`mobile_notification`

### Primary Key
- `notification_id` (integer)

### Key Attributes

#### Basic Information
- `notification_type` (string) - Type of notification
  - PUSH
  - IN_APP
  - ALERT
  - SILENT
- `title` (string) - Notification title
- `message` (text) - Notification message
- `deep_link` (string) - App deep link
- `image_url` (string) - Rich media URL
- `action_buttons` (json) - Interactive buttons

#### Recipient Information
- `recipient_type` (string) - Type of recipient
  - USER
  - GROUP
  - SEGMENT
  - ALL
- `recipient_id` (string) - Recipient identifier
- `device_types` (json) - Target device types
- `platforms` (json) - Target platforms
- `languages` (json) - Target languages

#### Scheduling
- `scheduled_at` (datetime) - Send time
- `expiry_at` (datetime) - Expiry time
- `time_zone` (string) - Recipient time zone
- `quiet_hours` (json) - Do not disturb hours
- `batch_size` (integer) - Batch sending size

#### Status Information
- `status` (integer) - Notification status
  - STATUS_DRAFT (0)
  - STATUS_SCHEDULED (10)
  - STATUS_SENDING (20)
  - STATUS_SENT (30)
  - STATUS_FAILED (40)
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Candidate Notification Model

### Overview
Manages notifications specific to candidates.

### Table Name
`candidate_notification`

### Key Attributes
- `candidate_id` (integer) - Candidate reference
- `notification_type` (string) - Type of notification
  - APPLICATION_UPDATE
  - INTERVIEW_SCHEDULE
  - DOCUMENT_REQUEST
  - OFFER_UPDATE
  - SYSTEM_ALERT
- `title` (string) - Notification title
- `message` (text) - Notification content
- `data` (json) - Additional data
- `read_at` (datetime) - Read timestamp
- `action_url` (string) - Action link

## Staff Notification Model

### Overview
Manages notifications for staff members.

### Table Name
`staff_notification`

### Key Attributes
- `staff_id` (integer) - Staff reference
- `notification_type` (string) - Type of notification
  - TASK_ASSIGNMENT
  - APPROVAL_REQUEST
  - SYSTEM_ALERT
  - PERFORMANCE_UPDATE
- `priority` (integer) - Priority level (1-5)
- `title` (string) - Notification title
- `message` (text) - Notification content
- `data` (json) - Additional data
- `read_at` (datetime) - Read timestamp
- `action_required` (integer) - Action flag

## Notification Template Model

### Overview
Manages reusable notification templates.

### Table Name
`notification_template`

### Key Attributes
- `template_code` (string) - Template identifier
- `template_name` (string) - Template name
- `title_template` (string) - Title format
- `message_template` (text) - Message format
- `variables` (json) - Template variables
- `platforms` (json) - Supported platforms
- `category` (string) - Template category

## Common Operations

```php
// Send push notification
$notification = new MobileNotification([
    'notification_type' => 'PUSH',
    'title' => $title,
    'message' => $message
]);
$notification->send();

// Get unread notifications
CandidateNotification::find()
    ->where([
        'candidate_id' => $candidateId,
        'read_at' => null
    ])
    ->orderBy(['created_at' => SORT_DESC])
    ->all();

// Mark notifications as read
StaffNotification::updateAll(
    ['read_at' => new Expression('NOW()')],
    ['staff_id' => $staffId, 'read_at' => null]
);

// Create from template
$notification = NotificationTemplate::create(
    'INTERVIEW_SCHEDULED',
    ['interview_time' => $time, 'location' => $location]
);
```

## Implementation Details
- Push notification service integration
- Template system
- Batch processing
- Delivery tracking
- Rich media support
- Deep linking

## Business Rules
1. Notification frequency limits
2. Quiet hours respect
3. Language preferences
4. Platform-specific formatting
5. Priority handling
6. Expiry handling

## Security Considerations
1. Device verification
2. Content encryption
3. Rate limiting
4. Permission validation
5. Data privacy
6. Token management
``` 