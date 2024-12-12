# Authentication Models

## Base Token Model

### Overview
The base token model provides common functionality for all authentication tokens in the system.

### Key Attributes

#### Token Information
- `token_value` (string) - Unique token string
- `token_type` (string) - Type of token
  - ACCESS
  - REFRESH
  - RESET
  - VERIFICATION
- `token_status` (integer) - Token status
  - STATUS_ACTIVE (10)
  - STATUS_EXPIRED (0)
  - STATUS_REVOKED (20)
- `expires_at` (datetime) - Expiration timestamp
- `last_used_at` (datetime) - Last usage timestamp
- `created_at` (datetime) - Creation timestamp

#### Device Information
- `device_id` (string) - Device identifier
- `device_type` (string) - Device type
- `device_name` (string) - Device name
- `ip_address` (string) - IP address
- `user_agent` (string) - Browser/client info

## Admin Token Model

### Overview
Authentication tokens for administrative users.

### Table Name
`admin_token`

### Key Attributes
- `admin_id` (integer) - Admin reference
- `permissions` (json) - Token permissions
- `admin_area` (string) - Admin jurisdiction

## Candidate Token Model

### Overview
Authentication tokens for job candidates.

### Table Name
`candidate_token`

### Key Attributes
- `candidate_id` (integer) - Candidate reference
- `application_id` (string) - Application context
- `session_data` (json) - Session information

## Contact Token Model

### Overview
Authentication tokens for company contacts.

### Table Name
`contact_token`

### Key Attributes
- `contact_id` (integer) - Contact reference
- `company_id` (integer) - Company context
- `access_level` (string) - Access scope

## Manager Token Model

### Overview
Authentication tokens for company managers.

### Table Name
`manager_token`

### Key Attributes
- `manager_id` (integer) - Manager reference
- `store_id` (integer) - Store context
- `role_permissions` (json) - Role-based permissions

## Inspector Token Model

### Overview
Authentication tokens for system inspectors.

### Table Name
`inspector_token`

### Key Attributes
- `inspector_id` (integer) - Inspector reference
- `inspection_area` (string) - Inspection scope
- `special_access` (json) - Special permissions

## Common Operations

```php
// Generate new token
$token = new AdminToken([
    'admin_id' => $adminId,
    'token_type' => 'ACCESS',
    'expires_at' => time() + 3600
]);
$token->generateTokenValue();

// Validate token
$token = CandidateToken::find()
    ->where([
        'token_value' => $tokenString,
        'token_status' => Token::STATUS_ACTIVE
    ])
    ->andWhere(['>', 'expires_at', time()])
    ->one();

// Revoke token
$token->revoke();

// Refresh token
$newToken = $token->refresh();

// Check token permissions
$token->hasPermission('manage_users');
```

## Implementation Details
- Secure token generation
- Token expiration handling
- Permission validation
- Device tracking
- Session management
- Access control

## Business Rules
1. Token expiration policies
2. Permission inheritance
3. Device restrictions
4. IP validation
5. Usage tracking
6. Revocation rules

## Security Features
1. Token encryption
2. Rate limiting
3. Device fingerprinting
4. Access logging
5. Suspicious activity detection
6. Multi-factor support

## Security Considerations
1. Token storage security
2. Transport encryption
3. Permission validation
4. Session management
5. Device verification
``` 