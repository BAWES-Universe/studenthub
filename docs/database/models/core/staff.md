# Staff Model

## Overview
The Staff model represents internal system users who manage and operate the platform. It includes administrators, managers, and other internal employees with various permission levels.

## Table Name
`staff`

## Primary Key
- `staff_id` (integer)

## Key Attributes

### Personal Information
- `staff_name` (string) - Full name in English
- `staff_name_ar` (string) - Full name in Arabic
- `staff_email` (string) - Work email address
- `staff_phone` (string) - Contact phone number
- `staff_photo` (string) - Profile photo path
- `staff_position` (string) - Job position/title
- `staff_department` (string) - Department name

### Authentication
- `staff_username` (string) - Login username
- `staff_password_hash` (string) - Hashed password
- `staff_auth_key` (string) - Authentication key
- `staff_password_reset_token` (string) - Password reset token
- `staff_verification_token` (string) - Email verification token

### Permissions & Access
- `staff_role` (string) - Primary role
- `staff_permissions` (text) - JSON encoded permissions
- `staff_access_level` (integer) - Access level
  - LEVEL_ADMIN (100)
  - LEVEL_MANAGER (50)
  - LEVEL_STAFF (10)
- `staff_status` (integer) - Account status
  - STATUS_ACTIVE (10)
  - STATUS_INACTIVE (0)
  - STATUS_SUSPENDED (2)

### Work Details
- `staff_joining_date` (date) - Employment start date
- `staff_contract_type` (string) - Employment contract type
- `staff_salary` (decimal) - Base salary
- `staff_working_hours` (integer) - Standard working hours
- `staff_leave_balance` (decimal) - Available leave days

### System Fields
- `last_login` (datetime) - Last login timestamp
- `last_active` (datetime) - Last activity timestamp
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `deleted` (integer) - Soft delete flag
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Foreign Keys
- `department_id` -> `department.department_id`
- `created_by` -> `staff.staff_id`
- `updated_by` -> `staff.staff_id`

## Relationships

### Has Many
- `staffExpenses` -> `StaffExpenses[]`
- `staffLeaves` -> `StaffLeave[]`
- `staffSalaries` -> `StaffSalary[]`
- `staffNotifications` -> `StaffNotification[]`
- `createdRequests` -> `Request[]`
- `updatedRequests` -> `Request[]`
- `notes` -> `Note[]`

### Belongs To
- `department` -> `Department`
- `creator` -> `Staff`
- `updater` -> `Staff`

## Behaviors
- TimestampBehavior (handles created_at/updated_at)
- BlameableBehavior (tracks creator/updater)

## Implementation Details
- Implements `\yii\web\IdentityInterface`
- Uses soft deletion
- Supports multi-language (Arabic/English)
- Implements role-based access control (RBAC)
- Tracks activity and login history
- Handles file uploads for profile photos

## Common Operations
```php
// Find active staff members
Staff::find()
    ->where(['staff_status' => Staff::STATUS_ACTIVE])
    ->all();

// Get staff with related data
Staff::find()
    ->with(['staffLeaves', 'staffExpenses', 'staffSalaries'])
    ->where(['staff_id' => $id])
    ->one();

// Find staff by department
Staff::find()
    ->where([
        'department_id' => $departmentId,
        'staff_status' => Staff::STATUS_ACTIVE
    ])
    ->all();

// Get staff with specific role
Staff::find()
    ->where([
        'staff_role' => $role,
        'staff_status' => Staff::STATUS_ACTIVE
    ])
    ->all();

// Check staff permissions
$staff->hasPermission('manage_requests');
```

## Security Notes
1. Passwords are hashed using Yii2's security component
2. Role-based access control enforced at all levels
3. Activity logging for sensitive operations
4. IP-based access restrictions available
5. Two-factor authentication support 