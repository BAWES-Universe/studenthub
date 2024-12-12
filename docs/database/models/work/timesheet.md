# Time Tracking Models

## Candidate Working Hour Model

### Overview
The Candidate Working Hour model tracks time entries and work hours for candidates.

### Table Name
`candidate_working_hour`

### Primary Key
- `working_hour_id` (integer)

### Key Attributes

#### Basic Information
- `candidate_id` (integer) - Candidate reference
- `contract_uuid` (char(60)) - Contract reference
- `work_date` (date) - Work date
- `start_time` (time) - Start time
- `end_time` (time) - End time
- `break_duration` (integer) - Break minutes
- `total_hours` (decimal) - Total work hours

#### Work Details
- `work_type` (string) - Type of work
  - REGULAR
  - OVERTIME
  - WEEKEND
  - HOLIDAY
- `location_type` (string) - Work location
  - ONSITE
  - REMOTE
  - HYBRID
- `project_code` (string) - Project reference
- `task_description` (text) - Work description
- `deliverables` (text) - Work output

#### Verification
- `status` (integer) - Entry status
  - STATUS_PENDING (0)
  - STATUS_APPROVED (10)
  - STATUS_REJECTED (20)
  - STATUS_MODIFIED (30)
- `verified_by` (integer) - Verifier reference
- `verification_note` (text) - Verification comments
- `verification_date` (datetime) - Verification timestamp

#### System Fields
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Candidate Working Date Model

### Overview
Manages work schedule and availability for candidates.

### Table Name
`candidate_working_date`

### Key Attributes

#### Schedule Information
- `candidate_id` (integer) - Candidate reference
- `date` (date) - Work date
- `availability_type` (string) - Availability
  - AVAILABLE
  - UNAVAILABLE
  - PARTIAL
- `shift_type` (string) - Shift preference
  - MORNING
  - AFTERNOON
  - EVENING
  - NIGHT
- `hours_available` (integer) - Available hours

#### Preferences
- `location_preference` (string) - Preferred location
- `work_type_preference` (string) - Work type preference
- `notes` (text) - Additional notes

## Work Log Feedback Model

### Overview
Tracks feedback and performance metrics for work entries.

### Table Name
`candidate_work_log_feedback`

### Key Attributes

#### Feedback Details
- `working_hour_id` (integer) - Work hour reference
- `reviewer_id` (integer) - Reviewer reference
- `rating` (integer) - Performance rating (1-5)
- `quality_score` (integer) - Work quality (1-5)
- `timeliness_score` (integer) - Timeliness (1-5)
- `feedback_text` (text) - Detailed feedback
- `improvement_areas` (text) - Areas for improvement

#### Follow-up
- `requires_action` (integer) - Action needed flag
- `action_description` (text) - Required actions
- `due_date` (date) - Action due date
- `completion_date` (date) - Action completion

## Common Operations

```php
// Log work hours
$workHours = new CandidateWorkingHour([
    'candidate_id' => $candidateId,
    'work_date' => $date,
    'start_time' => $startTime,
    'end_time' => $endTime
]);
$workHours->calculateTotalHours();

// Get candidate's weekly hours
CandidateWorkingHour::find()
    ->select([
        'work_date',
        'SUM(total_hours) as daily_hours'
    ])
    ->where([
        'candidate_id' => $candidateId,
        'status' => CandidateWorkingHour::STATUS_APPROVED
    ])
    ->andWhere(['>=', 'work_date', $weekStart])
    ->andWhere(['<=', 'work_date', $weekEnd])
    ->groupBy(['work_date'])
    ->all();

// Update availability
CandidateWorkingDate::updateAll(
    ['availability_type' => 'UNAVAILABLE'],
    ['candidate_id' => $candidateId, 'date' => $date]
);

// Add work feedback
$feedback = new WorkLogFeedback([
    'working_hour_id' => $hourId,
    'rating' => $rating,
    'feedback_text' => $feedback
]);
$feedback->save();
```

## Implementation Details
- Automatic hour calculation
- Schedule conflict detection
- Overtime tracking
- Break time management
- Feedback workflow
- Performance metrics

## Business Rules
1. No overlapping time entries
2. Maximum daily hours limit
3. Break time requirements
4. Approval workflow
5. Feedback timeliness
6. Schedule notice period

## Reporting Capabilities
1. Hours by work type
2. Performance trends
3. Schedule adherence
4. Availability patterns
5. Feedback analysis
6. Cost calculations

## Security Considerations
1. Time entry verification
2. Schedule modification rights
3. Feedback confidentiality
4. Data audit trail
5. Access controls
``` 