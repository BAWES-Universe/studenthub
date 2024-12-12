# Request Model

## Overview
The Request model represents job postings or positions that companies are looking to fill. It contains detailed information about job requirements, compensation, and hiring process.

## Table Name
`request`

## Primary Key
- `request_id` (integer)

## Key Attributes

### Basic Information
- `request_title` (string) - Job title in English
- `request_title_ar` (string) - Job title in Arabic
- `request_description` (text) - Job description
- `request_requirements` (text) - Job requirements
- `request_responsibilities` (text) - Job responsibilities
- `request_type` (string) - Employment type (Full-time, Part-time, Contract)
- `request_duration` (integer) - Contract duration in months
- `request_urgency` (integer) - Urgency level

### Location Details
- `request_location` (string) - Job location
- `request_area_uuid` (string) - Geographic area identifier
- `request_latitude` (decimal) - Geographic latitude
- `request_longitude` (decimal) - Geographic longitude
- `remote_work` (integer) - Remote work possibility flag

### Compensation
- `request_salary_from` (decimal) - Minimum salary
- `request_salary_to` (decimal) - Maximum salary
- `request_currency` (string) - Salary currency
- `salary_type` (string) - Salary type (Monthly, Hourly, Project-based)
- `benefits` (text) - Additional benefits
- `budget` (decimal) - Project/Position budget

### Requirements
- `experience_years` (integer) - Required years of experience
- `education_level` (string) - Required education level
- `skills_required` (text) - Required skills
- `languages_required` (text) - Required languages
- `certifications_required` (text) - Required certifications

### Timeline
- `request_start_date` (date) - Position start date
- `request_end_date` (date) - Position end date
- `application_deadline` (date) - Last date to apply
- `expected_hiring_date` (date) - Expected hiring date

### Process Status
- `request_status` (integer) - Request status
  - STATUS_DRAFT (0)
  - STATUS_ACTIVE (10)
  - STATUS_CLOSED (20)
  - STATUS_CANCELLED (30)
  - STATUS_ON_HOLD (40)
- `visibility` (integer) - Public/Private status
- `featured` (integer) - Featured status
- `positions_count` (integer) - Number of open positions
- `positions_filled` (integer) - Number of filled positions

### System Fields
- `company_id` (integer) - Company reference
- `department_id` (integer) - Department reference
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `deleted` (integer) - Soft delete flag
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Foreign Keys
- `company_id` -> `company.company_id`
- `department_id` -> `department.department_id`
- `created_by` -> `staff.staff_id`
- `updated_by` -> `staff.staff_id`

## Relationships

### Has Many
- `requestApplications` -> `RequestApplication[]`
- `requestInterviews` -> `RequestInterview[]`
- `requestSkills` -> `RequestSkill[]`
- `requestChecklists` -> `RequestChecklist[]`
- `notes` -> `Note[]`

### Belongs To
- `company` -> `Company`
- `department` -> `Department`
- `creator` -> `Staff`
- `updater` -> `Staff`

## Behaviors
- TimestampBehavior (handles created_at/updated_at)
- BlameableBehavior (tracks creator/updater)

## Implementation Details
- Uses soft deletion
- Supports multi-language (Arabic/English)
- Implements status workflow
- Tracks application statistics
- Handles location-based matching
- Supports skill-based matching

## Common Operations
```php
// Find active requests
Request::find()
    ->where(['request_status' => Request::STATUS_ACTIVE])
    ->andWhere(['<', 'application_deadline', date('Y-m-d')])
    ->all();

// Get request with applications
Request::find()
    ->with(['requestApplications', 'requestInterviews'])
    ->where(['request_id' => $id])
    ->one();

// Find requests by skill
Request::find()
    ->joinWith('requestSkills')
    ->where(['request_skill.skill_id' => $skillId])
    ->all();

// Get company's active requests
Request::find()
    ->where([
        'company_id' => $companyId,
        'request_status' => Request::STATUS_ACTIVE
    ])
    ->all();
``` 