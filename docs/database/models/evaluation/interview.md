# Interview Evaluation Models

## Interview Evaluation Model

### Overview
The Interview Evaluation model tracks and manages candidate interview assessments and feedback.

### Table Name
`interview_evaluation`

### Primary Key
- `evaluation_id` (integer)

### Key Attributes

#### Basic Information
- `interview_id` (integer) - Interview reference
- `candidate_id` (integer) - Candidate reference
- `evaluator_id` (integer) - Evaluator reference
- `evaluation_date` (datetime) - Evaluation date
- `interview_type` (string) - Type of interview
  - INITIAL_SCREENING
  - TECHNICAL
  - HR
  - CULTURAL_FIT
  - FINAL

#### Evaluation Scores
- `technical_score` (integer) - Technical skills (1-5)
- `communication_score` (integer) - Communication skills (1-5)
- `experience_score` (integer) - Experience relevance (1-5)
- `cultural_fit_score` (integer) - Cultural fit (1-5)
- `overall_score` (integer) - Overall rating (1-5)

#### Feedback
- `strengths` (text) - Candidate strengths
- `weaknesses` (text) - Areas for improvement
- `recommendations` (text) - Recommendations
- `salary_expectation` (decimal) - Expected compensation
- `availability` (string) - Availability information
- `notice_period` (integer) - Notice period in days

#### Decision
- `decision` (string) - Interview decision
  - PROCEED
  - REJECT
  - HOLD
  - NEXT_ROUND
- `decision_reason` (text) - Decision justification
- `next_steps` (text) - Recommended next steps

#### System Fields
- `status` (integer) - Evaluation status
  - STATUS_DRAFT (0)
  - STATUS_SUBMITTED (10)
  - STATUS_REVIEWED (20)
  - STATUS_APPROVED (30)
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Interview Evaluation Note Model

### Overview
Detailed notes and comments during the interview process.

### Table Name
`interview_evaluation_note`

### Key Attributes
- `evaluation_id` (integer) - Evaluation reference
- `note_type` (string) - Type of note
  - QUESTION_RESPONSE
  - OBSERVATION
  - CONCERN
  - FOLLOWUP
- `note_text` (text) - Note content
- `timestamp` (datetime) - Note timestamp
- `importance` (integer) - Note importance (1-3)

## Interview Evaluation Note Version Model

### Overview
Tracks changes and revisions to evaluation notes.

### Table Name
`interview_evaluation_note_version`

### Key Attributes
- `note_id` (integer) - Note reference
- `version_number` (integer) - Version number
- `previous_text` (text) - Previous content
- `new_text` (text) - New content
- `changed_by` (integer) - Editor reference
- `changed_at` (datetime) - Edit timestamp
- `change_reason` (string) - Reason for change

## Common Operations

```php
// Get candidate's evaluations
InterviewEvaluation::find()
    ->where(['candidate_id' => $candidateId])
    ->orderBy(['evaluation_date' => SORT_DESC])
    ->all();

// Get evaluation with notes
InterviewEvaluation::find()
    ->with(['notes', 'notes.versions'])
    ->where(['evaluation_id' => $id])
    ->one();

// Calculate average scores
InterviewEvaluation::find()
    ->select([
        'candidate_id',
        'AVG(technical_score) as avg_technical',
        'AVG(communication_score) as avg_communication',
        'AVG(overall_score) as avg_overall'
    ])
    ->where(['candidate_id' => $candidateId])
    ->groupBy(['candidate_id'])
    ->one();

// Track note changes
$note->trackVersion($newText, $reason);
```

## Implementation Details
- Supports multiple interview rounds
- Tracks evaluation history
- Maintains note versions
- Calculates composite scores
- Manages interview workflow
- Supports collaborative evaluation

## Business Rules
1. Evaluations require minimum score fields
2. Notes versioning for audit trail
3. Decision requires justification
4. Overall score calculated from components
5. Status transitions follow approval flow
6. Evaluation locked after approval

## Security Considerations
1. Access restricted by role
2. Note edits tracked with versions
3. Sensitive data handling
4. Evaluator verification
5. Time-based access controls 