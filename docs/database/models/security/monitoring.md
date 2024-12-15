# Security & Monitoring Models

## Activity Log Model

### Overview
The Activity Log model tracks user actions and system events for auditing and monitoring purposes.

### Table Name
`activity_log`

### Primary Key
- `log_id` (integer)

### Key Attributes

#### Event Information
- `event_type` (string) - Type of event
  - USER_LOGIN
  - USER_LOGOUT
  - DATA_CREATE
  - DATA_UPDATE
  - DATA_DELETE
  - FILE_UPLOAD
  - API_ACCESS
  - SECURITY_ALERT
- `event_severity` (string) - Event severity
  - INFO
  - WARNING
  - ERROR
  - CRITICAL
- `event_description` (text) - Event details
- `event_category` (string) - Event category

#### User Context
- `user_id` (integer) - User reference
- `user_type` (string) - User type
- `ip_address` (string) - IP address
- `user_agent` (string) - Browser/client info
- `session_id` (string) - Session reference

#### Data Changes
- `table_name` (string) - Affected table
- `record_id` (string) - Record reference
- `old_values` (json) - Previous values
- `new_values` (json) - Updated values
- `change_summary` (text) - Change description

#### System Context
- `component` (string) - System component
- `action_status` (string) - Action result
- `error_code` (string) - Error reference
- `stack_trace` (text) - Error details

#### Metadata
- `created_at` (datetime) - Event timestamp
- `processed_at` (datetime) - Processing time
- `archived_at` (datetime) - Archive timestamp

## Security Alert Model

### Overview
Manages security alerts and incidents.

### Table Name
`security_alert`

### Key Attributes

#### Alert Details
- `alert_type` (string) - Alert category
  - UNAUTHORIZED_ACCESS
  - SUSPICIOUS_ACTIVITY
  - POLICY_VIOLATION
  - DATA_BREACH
  - SYSTEM_ANOMALY
- `alert_severity` (integer) - Severity level (1-5)
- `alert_message` (text) - Alert description
- `detection_source` (string) - Detection method

#### Incident Information
- `incident_id` (string) - Incident reference
- `affected_systems` (json) - Impacted systems
- `affected_users` (json) - Impacted users
- `impact_scope` (text) - Impact assessment
- `data_exposure` (text) - Exposed data

#### Response Actions
- `status` (string) - Alert status
  - NEW
  - INVESTIGATING
  - RESOLVED
  - FALSE_POSITIVE
- `assigned_to` (integer) - Handler reference
- `resolution_steps` (text) - Resolution actions
- `resolution_time` (integer) - Resolution duration

## System Health Model

### Overview
Monitors system performance and health metrics.

### Table Name
`system_health`

### Key Attributes

#### Performance Metrics
- `cpu_usage` (decimal) - CPU utilization
- `memory_usage` (decimal) - Memory usage
- `disk_usage` (decimal) - Storage usage
- `network_traffic` (json) - Network stats
- `response_times` (json) - API latencies

#### System Status
- `component_status` (json) - Component health
- `service_status` (json) - Service health
- `error_count` (integer) - Error frequency
- `warning_count` (integer) - Warning count
- `health_score` (decimal) - Overall health

#### Availability
- `uptime` (integer) - System uptime
- `last_downtime` (datetime) - Last outage
- `maintenance_mode` (integer) - Maintenance flag
- `degraded_services` (json) - Affected services

## Common Operations

```php
// Log user activity
$log = new ActivityLog([
    'event_type' => 'DATA_UPDATE',
    'user_id' => $userId,
    'table_name' => $table,
    'old_values' => $oldData,
    'new_values' => $newData
]);
$log->save();

// Create security alert
$alert = new SecurityAlert([
    'alert_type' => 'SUSPICIOUS_ACTIVITY',
    'alert_severity' => 4,
    'alert_message' => $message
]);
$alert->triggerResponse();

// Monitor system health
SystemHealth::find()
    ->select([
        'component_status',
        'AVG(health_score) as avg_health',
        'COUNT(CASE WHEN error_count > 0 THEN 1 END) as error_components'
    ])
    ->where(['>=', 'created_at', '-1 hour'])
    ->groupBy(['component_status'])
    ->all();

// Get critical security events
ActivityLog::find()
    ->where([
        'event_severity' => 'CRITICAL',
        'event_type' => ['SECURITY_ALERT', 'UNAUTHORIZED_ACCESS']
    ])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(10)
    ->all();
```

## Implementation Details
- Real-time monitoring
- Alert notification system
- Performance tracking
- Audit trail maintenance
- Incident response workflow
- Health check automation

## Business Rules
1. Log retention policy
2. Alert escalation rules
3. Health threshold limits
4. Incident response SLAs
5. Data archival rules
6. Access monitoring

## Reporting Capabilities
1. Security incident reports
2. System health dashboards
3. User activity analysis
4. Performance trends
5. Compliance reporting
6. Audit trail exports

## Security Considerations
1. Log data encryption
2. Access control
3. Data retention
4. Alert verification
5. Privacy compliance
``` 