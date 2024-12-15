# Candidate Model

## Overview
The Candidate model represents job seekers in the system. It is one of the core entities and contains comprehensive information about candidates' personal details, professional information, and authentication data.

## Table Name
`candidate`

## Primary Key
- `candidate_id` (integer)

## Key Attributes

### Personal Information
- `candidate_name` (string) - Full name in English
- `candidate_name_ar` (string) - Full name in Arabic
- `candidate_gender` (string) - Gender
- `candidate_birth_date` (date) - Date of birth
- `candidate_email` (string) - Primary email address
- `candidate_phone` (string) - Contact phone number
- `candidate_address_line1` (string) - Primary address
- `candidate_area_uuid` (string) - Geographic area identifier

### Professional Details
- `candidate_objective` (text) - Career objectives
- `candidate_intro` (text) - Professional introduction
- `candidate_hourly_rate` (float) - Desired hourly compensation
- `candidate_job_search_status` (integer) - Job search status
  - ACTIVELY_LOOKING_FOR_JOB (1)
  - NOT_LOOKING_FOR_JOB (0)
- `candidate_committed` (integer) - Commitment status
  - COMMITTED (1)
  - NOT_COMMITTED (0)

### Documents & Media
- `candidate_personal_photo` (string) - Profile photo path
- `candidate_video` (string) - Introduction video path
- `candidate_resume` (string) - Resume file path
- `candidate_civil_id` (string) - Civil ID number
- `candidate_civil_photo_front` (string) - Civil ID front photo
- `candidate_civil_photo_back` (string) - Civil ID back photo
- `candidate_driving_license` (string) - Driving license details

### Authentication & Security
- `candidate_auth_key` (string) - Authentication key
- `candidate_password_hash` (string) - Hashed password
- `candidate_password_reset_token` (string) - Password reset token
- `candidate_email_verification` (integer) - Email verification status
  - EMAIL_VERIFIED (1)
  - EMAIL_NOT_VERIFIED (0)

### Location Data
- `candidate_latitude` (decimal) - Geographic latitude
- `candidate_longitude` (decimal) - Geographic longitude

### System Fields
- `utm_uuid` (string) - UTM tracking identifier
- `candidate_uid` (string) - Unique identifier
- `candidate_status` (integer) - Account status
  - STATUS_ACTIVE (10)
  - STATUS_PENDING (0)
  - STATUS_READY (1)
- `approved` (integer) - Approval status
- `deleted` (integer) - Soft delete flag
- `is_duplicate` (integer) - Duplicate account flag
- `candidate_created_at` (datetime) - Creation timestamp
- `candidate_updated_at` (datetime) - Last update timestamp

## Foreign Keys
- `store_id` -> `store.store_id`
- `bank_id` -> `bank.bank_id`
- `university_id` -> `university.university_id`
- `country_id` -> `country.country_id`

## Relationships

### Has Many
- `candidateIdCards` -> `CandidateIdCard[]`
- `accessTokens` -> `CandidateToken[]`
- `candidateEducations` -> `CandidateEducation[]`
- `candidateExperiences` -> `CandidateExperience[]`
- `candidateSkills` -> `CandidateSkill[]`
- `candidateWorkHistories` -> `CandidateWorkHistory[]`
- `candidateNotifications` -> `CandidateNotification[]`
- `notes` -> `Note[]`

### Belongs To
- `bank` -> `Bank`
- `country` -> `Country`
- `store` -> `Store`
- `university` -> `University`
- `campaign` -> `Campaign`

## Behaviors
- TimestampBehavior (handles created_at/updated_at)

## Implementation Details
- Implements `\yii\web\IdentityInterface` for authentication
- Uses soft deletion
- Supports multi-language (Arabic/English)
- Includes comprehensive validation rules
- Handles file uploads for documents and media

## Common Operations
```php
// Find active candidates
Candidate::find()->where(['candidate_status' => Candidate::STATUS_ACTIVE])->all();

// Find candidates by skill
Candidate::find()
    ->joinWith('candidateSkills')
    ->where(['candidate_skill.skill_id' => $skillId])
    ->all();

// Get candidate with related data
Candidate::find()
    ->with(['candidateEducations', 'candidateExperiences', 'candidateSkills'])
    ->where(['candidate_id' => $id])
    ->one();
```