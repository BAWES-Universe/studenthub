# Candidate Evaluation Models

## Candidate Evaluation Model

### Overview
The Candidate Evaluation model manages comprehensive assessments of candidates, including skills, experience, and departmental evaluations.

### Table Name
`candidate_evaluation`

### Primary Key
- `evaluation_id` (integer)

### Key Attributes

#### Basic Information
- `candidate_id` (integer) - Candidate reference
- `evaluator_id` (integer) - Evaluator reference
- `evaluation_type` (string) - Type of evaluation
  - SKILLS_ASSESSMENT
  - EXPERIENCE_VERIFICATION
  - DEPARTMENT_EVALUATION
  - PERFORMANCE_REVIEW
- `evaluation_date` (datetime) - Evaluation date

#### Evaluation Areas
- `technical_skills` (integer) - Technical capability (1-5)
- `soft_skills` (integer) - Soft skills rating (1-5)
- `experience_relevance` (integer) - Experience match (1-5)
- `potential_growth` (integer) - Growth potential (1-5)
- `cultural_fit` (integer) - Cultural alignment (1-5)
- `overall_rating` (integer) - Composite rating (1-5)

#### Detailed Assessment
- `strengths` (text) - Key strengths
- `weaknesses` (text) - Areas for improvement
- `skill_gaps` (text) - Identified skill gaps
- `training_needs` (text) - Required training
- `recommendations` (text) - Recommendations
- `comments` (text) - Additional comments

#### Status Information
- `evaluation_status` (integer) - Status
  - STATUS_DRAFT (0)
  - STATUS_SUBMITTED (10)
  - STATUS_REVIEWED (20)
  - STATUS_APPROVED (30)
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Candidate Evaluation Question Model

### Overview
Defines standard evaluation questions for different departments and roles.

### Table Name
`candidate_eval_ques`

### Key Attributes
- `question_text` (text) - Question in English
- `question_text_ar` (text) - Question in Arabic
- `question_type` (string) - Question type
  - RATING
  - TEXT
  - MULTIPLE_CHOICE
  - YES_NO
- `category` (string) - Question category
- `weight` (decimal) - Question weight
- `required` (integer) - Required flag
- `order` (integer) - Display order

## Candidate Evaluation Department Question Model

### Overview
Department-specific evaluation questions and criteria.

### Table Name
`candidate_eval_dept_ques`

### Key Attributes
- `department_id` (integer) - Department reference
- `question_text` (text) - Question in English
- `question_text_ar` (text) - Question in Arabic
- `importance` (integer) - Question importance (1-3)
- `evaluation_criteria` (text) - Scoring criteria
- `minimum_score` (integer) - Required minimum score
- `maximum_score` (integer) - Maximum possible score

## Candidate Evaluation Answer Model

### Overview
Records responses to evaluation questions.

### Table Name
`candidate_evaluation_answer`

### Key Attributes
- `evaluation_id` (integer) - Evaluation reference
- `question_id` (integer) - Question reference
- `answer_text` (text) - Text response
- `rating_value` (integer) - Numeric rating
- `notes` (text) - Answer notes
- `answered_by` (integer) - Responder reference
- `answered_at` (datetime) - Response timestamp

## Common Operations

```php
// Get candidate's latest evaluation
CandidateEvaluation::find()
    ->where(['candidate_id' => $candidateId])
    ->orderBy(['evaluation_date' => SORT_DESC])
    ->one();

// Get evaluation with answers
CandidateEvaluation::find()
    ->with(['answers', 'departmentQuestions'])
    ->where(['evaluation_id' => $id])
    ->one();

// Calculate department scores
CandidateEvaluation::find()
    ->select([
        'department_id',
        'AVG(rating_value) as avg_score',
        'MIN(rating_value) as min_score',
        'MAX(rating_value) as max_score'
    ])
    ->joinWith('answers')
    ->where(['evaluation_id' => $id])
    ->groupBy(['department_id'])
    ->all();

// Check evaluation completion
$evaluation->checkCompletion();
```

## Implementation Details
- Supports multiple evaluation types
- Handles department-specific criteria
- Implements weighted scoring
- Tracks evaluation progress
- Manages question banks
- Supports multi-language content

## Business Rules
1. Minimum required questions per evaluation
2. Department-specific passing criteria
3. Weighted score calculations
4. Required evaluator qualifications
5. Review and approval workflow
6. Score normalization across departments

## Evaluation Process
1. Question selection/customization
2. Response collection
3. Score calculation
4. Review and validation
5. Approval workflow
6. Results communication

## Security Notes
1. Role-based access control
2. Department-specific permissions
3. Answer confidentiality
4. Audit trail maintenance
5. Data retention policies 