# Daily Standup Models

## Daily Standup Question Model

### Overview
The Daily Standup Question model manages the questions used in daily standup meetings and progress tracking.

### Table Name
`daily_standup_question`

### Primary Key
- `question_id` (integer)

### Key Attributes

#### Question Details
- `question_text` (text) - Question in English
- `question_text_ar` (text) - Question in Arabic
- `question_type` (string) - Type of question
  - PROGRESS
  - BLOCKER
  - PLAN
  - GENERAL
  - CUSTOM
- `category` (string) - Question category
- `order` (integer) - Display order

#### Configuration
- `is_required` (integer) - Required flag
- `is_active` (integer) - Active status
- `applies_to` (json) - Applicable roles
- `validation_rules` (json) - Answer validation
- `hint_text` (text) - Helper text

#### Schedule
- `schedule_type` (string) - Question frequency
  - DAILY
  - WEEKLY
  - CUSTOM
- `active_days` (json) - Active days
- `time_slot` (json) - Time restrictions

#### System Fields
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Daily Standup Answer Model

### Overview
Records responses to daily standup questions.

### Table Name
`daily_standup_answer`

### Key Attributes

#### Basic Information
- `question_id` (integer) - Question reference
- `user_id` (integer) - Respondent reference
- `answer_date` (date) - Response date
- `answer_text` (text) - Response content
- `answer_type` (string) - Response type
  - TEXT
  - CHOICE
  - RATING
  - LINK

#### Progress Tracking
- `work_status` (string) - Work status
  - ON_TRACK
  - DELAYED
  - BLOCKED
  - COMPLETED
- `completion_percentage` (integer) - Task completion
- `time_spent` (integer) - Time spent (minutes)
- `remaining_time` (integer) - Time remaining

#### Issues & Blockers
- `has_blockers` (integer) - Blocker flag
- `blocker_description` (text) - Blocker details
- `assistance_needed` (text) - Help required
- `priority_level` (integer) - Issue priority

#### Follow-up
- `requires_followup` (integer) - Follow-up flag
- `followup_assignee` (integer) - Assignee reference
- `followup_date` (date) - Follow-up date
- `resolution_status` (string) - Resolution status

## Common Operations

```php
// Get today's questions
DailyStandupQuestion::find()
    ->where([
        'is_active' => 1,
        'schedule_type' => 'DAILY'
    ])
    ->orderBy(['order' => SORT_ASC])
    ->all();

// Submit standup answers
$answer = new DailyStandupAnswer([
    'question_id' => $questionId,
    'user_id' => $userId,
    'answer_text' => $response,
    'work_status' => 'ON_TRACK'
]);
$answer->save();

// Get team's standup summary
DailyStandupAnswer::find()
    ->select([
        'user_id',
        'GROUP_CONCAT(answer_text) as responses',
        'MIN(CASE WHEN has_blockers = 1 THEN blocker_description END) as blockers'
    ])
    ->where([
        'answer_date' => date('Y-m-d'),
        'user_id' => $teamMemberIds
    ])
    ->groupBy(['user_id'])
    ->all();

// Track blockers requiring attention
DailyStandupAnswer::find()
    ->where([
        'has_blockers' => 1,
        'resolution_status' => null
    ])
    ->orderBy(['priority_level' => SORT_DESC])
    ->all();
```

## Implementation Details
- Question scheduling system
- Answer validation rules
- Progress tracking
- Blocker management
- Follow-up workflow
- Team reporting

## Business Rules
1. Answer submission timeframe
2. Required question handling
3. Blocker escalation rules
4. Follow-up assignment
5. Progress calculation
6. Schedule enforcement

## Reporting Features
1. Daily team summaries
2. Blocker analysis
3. Progress tracking
4. Time allocation
5. Participation metrics
6. Resolution tracking

## Security Considerations
1. Answer visibility control
2. Team-based access
3. Historical data access
4. Sensitive info handling
5. Audit logging