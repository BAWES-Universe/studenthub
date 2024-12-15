# Fulltimer Model

## Overview
The Fulltimer model represents full-time employees in the system. It extends the basic Candidate model with additional attributes and relationships specific to full-time employment.

## Table Name
`fulltimer`

## Primary Key
- `fulltimer_id` (integer)

## Key Attributes

### Basic Information
- `fulltimer_name` (string) - Full name in English
- `fulltimer_name_ar` (string) - Full name in Arabic
- `fulltimer_email` (string) - Work email address
- `fulltimer_phone` (string) - Contact phone number
- `fulltimer_photo` (string) - Profile photo path
- `fulltimer_position` (string) - Current position
- `fulltimer_department` (string) - Department name

### Employment Details
- `employment_type` (string) - Type of employment
- `employment_status` (integer) - Employment status
  - STATUS_ACTIVE (10)
  - STATUS_PROBATION (5)
  - STATUS_TERMINATED (0)
- `joining_date` (date) - Start date
- `confirmation_date` (date) - Probation end date
- `termination_date` (date) - End date if terminated
- `notice_period` (integer) - Notice period in days
- `probation_period` (integer) - Probation period in days

### Compensation
- `base_salary` (decimal) - Base salary amount
- `housing_allowance` (decimal) - Housing allowance
- `transport_allowance` (decimal) - Transport allowance
- `other_allowances` (decimal) - Other allowances
- `total_package` (decimal) - Total compensation
- `currency_code` (string) - Salary currency

### Work Schedule
- `working_hours` (integer) - Standard working hours
- `overtime_eligible` (integer) - Overtime eligibility
- `shift_type` (string) - Shift pattern
- `weekend_days` (string) - Weekend configuration
- `annual_leave_days` (decimal) - Annual leave entitlement

### Documents
- `contract_file` (string) - Employment contract path
- `visa_file` (string) - Visa document path
- `passport_file` (string) - Passport copy path
- `other_documents` (text) - Additional documents

### System Fields
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `deleted` (integer) - Soft delete flag
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Foreign Keys
- `candidate_id` -> `candidate.candidate_id`
- `company_id` -> `company.company_id`
- `department_id` -> `department.department_id`
- `manager_id` -> `staff.staff_id`

## Relationships

### Has Many
- `fulltimerExperiences` -> `FulltimerExperience[]`
- `fulltimerSkills` -> `FulltimerSkill[]`
- `fulltimerTags` -> `FulltimerTags[]`
- `salaryHistory` -> `StaffSalary[]`
- `leaveRecords` -> `StaffLeave[]`
- `attendanceRecords` -> `Attendance[]`
- `performanceReviews` -> `PerformanceReview[]`

### Belongs To
- `candidate` -> `Candidate`
- `company` -> `Company`
- `department` -> `Department`
- `manager` -> `Staff`

## Behaviors
- TimestampBehavior
- BlameableBehavior
- SoftDeleteBehavior

## Implementation Details
- Extends Candidate functionality
- Uses soft deletion
- Supports multi-language (Arabic/English)
- Manages employment lifecycle
- Tracks compensation history
- Handles document management
- Implements leave management
- Monitors performance metrics

## Common Operations
```php
// Find active fulltime employees
Fulltimer::find()
    ->where(['employment_status' => Fulltimer::STATUS_ACTIVE])
    ->all();

// Get fulltimer with related data
Fulltimer::find()
    ->with([
        'fulltimerExperiences',
        'fulltimerSkills',
        'salaryHistory',
        'leaveRecords'
    ])
    ->where(['fulltimer_id' => $id])
    ->one();

// Find fulltime employees by department
Fulltimer::find()
    ->where([
        'department_id' => $departmentId,
        'employment_status' => Fulltimer::STATUS_ACTIVE
    ])
    ->all();

// Calculate total compensation
Fulltimer::find()
    ->select([
        'fulltimer_id',
        'fulltimer_name',
        'base_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'total_package'
    ])
    ->where(['fulltimer_id' => $id])
    ->asArray()
    ->one();

// Process probation confirmation
$fulltimer->confirmProbation();
```

## Business Rules
1. Probation period must be completed before confirmation
2. Notice period required for termination
3. Leave balance calculated based on employment duration
4. Salary changes require management approval
5. Document updates trigger notifications
6. Performance reviews scheduled periodically 