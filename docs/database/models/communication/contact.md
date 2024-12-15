# Contact Model

## Overview
The Contact model represents external contacts and stakeholders in the system. It manages contact information, communication preferences, and relationship tracking.

## Table Name
`contact`

## Primary Key
- `contact_id` (integer)

## Key Attributes

### Basic Information
- `contact_name` (string) - Contact name in English
- `contact_name_ar` (string) - Contact name in Arabic
- `contact_title` (string) - Professional title
- `contact_company` (string) - Company affiliation
- `contact_department` (string) - Department
- `contact_position` (string) - Position/Role

### Contact Details
- Primary Contact Methods:
  - `contact_primary_email` (string) - Primary email address
  - `contact_primary_phone` (string) - Primary phone number
- Additional Contact Info:
  - `contact_address` (string) - Physical address
  - `contact_website` (string) - Website URL
  - `contact_social_linkedin` (string) - LinkedIn profile
  - `contact_social_twitter` (string) - Twitter handle

### Communication Preferences
- `preferred_language` (string) - Preferred language
- `contact_time_zone` (string) - Time zone
- `preferred_contact_method` (string) - Preferred contact method
- `communication_frequency` (string) - Communication frequency
- `subscribe_newsletter` (integer) - Newsletter subscription
- `subscribe_updates` (integer) - Updates subscription

### Relationship Data
- `contact_type` (string) - Type of contact
- `contact_source` (string) - Source of contact
- `contact_status` (integer) - Contact status
  - STATUS_ACTIVE (10)
  - STATUS_INACTIVE (0)
  - STATUS_BLOCKED (2)
- `relationship_strength` (integer) - Relationship score
- `last_contact_date` (datetime) - Last contact date
- `next_follow_up` (datetime) - Next follow-up date

### System Fields
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `deleted` (integer) - Soft delete flag
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Foreign Keys
- `company_id` -> `company.company_id`
- `created_by` -> `staff.staff_id`
- `updated_by` -> `staff.staff_id`

## Relationships

### Has Many
- `contactEmails` -> `ContactEmail[]`
- `contactPhones` -> `ContactPhone[]`
- `contactInvitations` -> `ContactInvitation[]`
- `contactTokens` -> `ContactToken[]`
- `bankTransactionContacts` -> `BankTransactionContact[]`
- `notes` -> `Note[]`

### Has One
- `contactStats` -> `ContactStats`
- `contactPreferences` -> `ContactPreferences`

### Belongs To
- `company` -> `Company`
- `creator` -> `Staff`
- `updater` -> `Staff`

## Behaviors
- TimestampBehavior
- BlameableBehavior
- SoftDeleteBehavior

## Implementation Details
- Uses soft deletion
- Supports multi-language (Arabic/English)
- Implements contact verification
- Tracks communication history
- Manages multiple contact methods
- Handles contact preferences
- Supports relationship scoring

## Common Operations
```php
// Find active contacts
Contact::find()
    ->where(['contact_status' => Contact::STATUS_ACTIVE])
    ->all();

// Get contact with all related data
Contact::find()
    ->with([
        'contactEmails',
        'contactPhones',
        'contactInvitations',
        'notes'
    ])
    ->where(['contact_id' => $id])
    ->one();

// Find contacts by company
Contact::find()
    ->where([
        'company_id' => $companyId,
        'contact_status' => Contact::STATUS_ACTIVE
    ])
    ->all();

// Get contacts needing follow-up
Contact::find()
    ->where(['<=', 'next_follow_up', date('Y-m-d')])
    ->andWhere(['contact_status' => Contact::STATUS_ACTIVE])
    ->all();

// Update contact relationship strength
$contact->updateRelationshipScore();
```

## Security Notes
1. Email verification required for primary email changes
2. Phone verification available for primary phone changes
3. Contact data is subject to privacy regulations
4. Access control based on relationship to company
5. Communication preferences must be respected 