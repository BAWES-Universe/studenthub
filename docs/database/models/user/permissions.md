# Permission Models

## Permission Section Model

### Overview
The Permission Section model represents high-level permission categories in the system.

### Table Name
`permission_section`

### Primary Key
- `section_id` (integer)

### Key Attributes

#### Basic Information
- `section_name` (string) - Section name in English
- `section_name_ar` (string) - Section name in Arabic
- `section_code` (string) - Unique identifier
- `section_description` (text) - Section description
- `section_order` (integer) - Display order

#### Configuration
- `is_active` (integer) - Active status
- `is_visible` (integer) - Visibility flag
- `requires_approval` (integer) - Approval requirement
- `default_role_access` (json) - Default role access

## Permission Subsection Model

### Overview
Represents detailed permission items within sections.

### Table Name
`permission_subsection`

### Key Attributes

#### Basic Information
- `section_id` (integer) - Section reference
- `subsection_name` (string) - Name in English
- `subsection_name_ar` (string) - Name in Arabic
- `subsection_code` (string) - Unique identifier
- `subsection_description` (text) - Description

#### Access Control
- `permission_type` (string) - Permission type
  - VIEW
  - CREATE
  - UPDATE
  - DELETE
  - MANAGE
- `access_level` (integer) - Required level
- `dependencies` (json) - Required permissions

#### Configuration
- `is_active` (integer) - Active status
- `is_critical` (integer) - Critical operation flag
- `requires_2fa` (integer) - 2FA requirement
- `audit_logging` (integer) - Audit requirement

## Permission User Model

### Overview
Maps permissions to users and roles.

### Table Name
`permission_user`

### Key Attributes

#### Assignment Details
- `user_id` (integer) - User reference
- `user_type` (string) - User type
  - STAFF
  - ADMIN
  - MANAGER
  - INSPECTOR
- `section_id` (integer) - Section reference
- `subsection_id` (integer) - Subsection reference

#### Access Control
- `access_type` (string) - Access type
  - ALLOW
  - DENY
  - INHERIT
- `access_level` (integer) - Access level
- `restrictions` (json) - Access restrictions
- `conditions` (json) - Access conditions

#### Validity
- `valid_from` (datetime) - Start validity
- `valid_until` (datetime) - End validity
- `last_used` (datetime) - Last usage
- `granted_by` (integer) - Grantor reference

## Common Operations

```php
// Check user permission
$hasAccess = PermissionUser::find()
    ->where([
        'user_id' => $userId,
        'section_id' => $sectionId,
        'access_type' => 'ALLOW'
    ])
    ->andWhere(['<=', 'valid_from', time()])
    ->andWhere(['>=', 'valid_until', time()])
    ->exists();

// Get user permissions
$permissions = PermissionUser::find()
    ->select(['section_id', 'subsection_id', 'access_type'])
    ->where(['user_id' => $userId])
    ->asArray()
    ->all();

// Grant permission
$permission = new PermissionUser([
    'user_id' => $userId,
    'section_id' => $sectionId,
    'access_type' => 'ALLOW',
    'granted_by' => Yii::$app->user->id
]);
$permission->save();

// Get section permissions
$subsections = PermissionSubsection::find()
    ->where(['section_id' => $sectionId])
    ->orderBy(['subsection_code' => SORT_ASC])
    ->all();
```

## Implementation Details
- Role-based access control
- Permission inheritance
- Access level hierarchy
- Condition evaluation
- Audit logging
- Cache management

## Business Rules
1. Permission hierarchy
2. Access inheritance
3. Critical operation rules
4. Validity periods
5. Approval workflows
6. Audit requirements

## Security Features
1. Permission validation
2. Access logging
3. Critical operation handling
4. Condition enforcement
5. Role separation
6. Least privilege principle

## Security Considerations
1. Permission escalation
2. Access control bypass
3. Role separation
4. Audit trail
5. Critical operations 