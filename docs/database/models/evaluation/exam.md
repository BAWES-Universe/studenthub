# Exam Models

## Exam Model

### Overview
The Exam model represents assessment tests used to evaluate candidate skills and knowledge.

### Table Name
`exam`

### Primary Key
- `exam_id` (integer)

### Key Attributes

#### Basic Information
- `exam_title` (string) - Exam title in English
- `exam_title_ar` (string) - Exam title in Arabic
- `exam_description` (text) - Exam description
- `exam_type` (string) - Type of exam
  - SKILLS_ASSESSMENT
  - PERSONALITY_TEST
  - TECHNICAL_TEST
  - LANGUAGE_TEST
- `exam_category` (string) - Exam category
- `difficulty_level` (integer) - Difficulty level (1-5)

#### Configuration
- `total_questions` (integer) - Number of questions
- `passing_score` (integer) - Required passing score
- `time_limit` (integer) - Time limit in minutes
- `attempts_allowed` (integer) - Maximum attempts allowed
- `randomize_questions` (integer) - Question randomization flag
- `show_answers` (integer) - Show correct answers flag
- `certificate_provided` (integer) - Certificate flag

#### Status Information
- `exam_status` (integer) - Status
  - STATUS_DRAFT (0)
  - STATUS_ACTIVE (10)
  - STATUS_INACTIVE (20)
  - STATUS_ARCHIVED (30)
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Exam Question Model

### Overview
Represents individual questions within an exam.

### Table Name
`exam_question`

### Key Attributes

#### Question Content
- `question_text` (text) - Question text in English
- `question_text_ar` (text) - Question text in Arabic
- `question_type` (string) - Type of question
  - MULTIPLE_CHOICE
  - SINGLE_CHOICE
  - TRUE_FALSE
  - OPEN_ENDED
  - CODING
- `difficulty_level` (integer) - Question difficulty (1-5)
- `points` (integer) - Points awarded
- `time_limit` (integer) - Time limit per question

#### Additional Content
- `code_snippet` (text) - Code sample if applicable
- `image_url` (string) - Image reference if any
- `explanation` (text) - Answer explanation
- `hints` (text) - Available hints
- `tags` (string) - Question tags

## Exam Question Choice Model

### Overview
Represents answer choices for exam questions.

### Table Name
`exam_question_choice`

### Key Attributes
- `choice_text` (text) - Choice text in English
- `choice_text_ar` (text) - Choice text in Arabic
- `is_correct` (integer) - Correct answer flag
- `explanation` (text) - Choice explanation
- `order` (integer) - Display order

## Exam Question Answer Model

### Overview
Records candidate responses to exam questions.

### Table Name
`exam_question_answer`

### Key Attributes
- `candidate_id` (integer) - Candidate reference
- `exam_id` (integer) - Exam reference
- `question_id` (integer) - Question reference
- `selected_choice_id` (integer) - Selected answer
- `answer_text` (text) - Written answer
- `is_correct` (integer) - Correctness flag
- `points_awarded` (integer) - Points awarded
- `time_taken` (integer) - Time taken in seconds
- `answered_at` (datetime) - Answer timestamp

## Common Operations

```php
// Get active exams
Exam::find()
    ->where(['exam_status' => Exam::STATUS_ACTIVE])
    ->all();

// Get exam with questions
Exam::find()
    ->with(['questions', 'questions.choices'])
    ->where(['exam_id' => $id])
    ->one();

// Get candidate exam results
ExamQuestionAnswer::find()
    ->select([
        'exam_id',
        'SUM(points_awarded) as total_points',
        'AVG(CASE WHEN is_correct THEN 1 ELSE 0 END) as accuracy'
    ])
    ->where(['candidate_id' => $candidateId])
    ->groupBy(['exam_id'])
    ->all();

// Check if candidate passed exam
$exam->checkPassingScore($candidateId);
```

## Implementation Details
- Supports multiple question types
- Handles multi-language content
- Implements scoring system
- Tracks attempt history
- Manages time limits
- Supports media content

## Business Rules
1. Questions must have at least one correct answer
2. Time limits enforced per question/exam
3. Attempts limited per candidate
4. Passing score requirements
5. Question randomization options
6. Answer review policies
``` 