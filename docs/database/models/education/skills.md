# Skills & Certification Models

## Candidate Skill Model

### Overview
The Candidate Skill model tracks professional skills and competencies of candidates.

### Table Name
`candidate_skill`

### Primary Key
- `skill_id` (integer)

### Key Attributes

#### Basic Information
- `candidate_id` (integer) - Candidate reference
- `skill_name` (string) - Skill name
- `skill_category` (string) - Skill category
- `proficiency_level` (integer) - Skill level (1-5)
- `years_experience` (decimal) - Years of practice

#### Validation
- `is_verified` (integer) - Verification status
- `verification_method` (string) - Verification type
  - ASSESSMENT
  - INTERVIEW
  - CERTIFICATE
  - REFERENCE
- `verified_by` (integer) - Verifier reference
- `verified_at` (datetime) - Verification time

#### Usage
- `is_primary` (integer) - Primary skill flag
- `is_featured` (integer) - Featured status
- `last_used_date` (date) - Last usage date
- `usage_frequency` (string) - Usage frequency

## Candidate Certificate Model

### Overview
Manages professional certifications and qualifications.

### Table Name
`candidate_certificate`

### Key Attributes

#### Basic Information
- `candidate_id` (integer) - Candidate reference
- `certificate_name` (string) - Certificate name
- `issuing_organization` (string) - Issuer name
- `certificate_id` (string) - Certificate number
- `certification_url` (string) - Verification URL

#### Timeline
- `issue_date` (date) - Issue date
- `expiry_date` (date) - Expiry date
- `completion_date` (date) - Completion date
- `hours_completed` (integer) - Training hours

#### Documentation
- `certificate_file` (string) - Certificate file
- `verification_status` (integer) - Verification flag
- `verification_notes` (text) - Verification notes
- `credential_id` (string) - Credential ID

## Request Skill Model

### Overview
Defines required skills for job positions.

### Table Name
`request_skill`

### Key Attributes

#### Skill Requirements
- `request_id` (integer) - Request reference
- `skill_name` (string) - Required skill
- `minimum_years` (decimal) - Required experience
- `minimum_level` (integer) - Required level
- `is_mandatory` (integer) - Mandatory flag

#### Preferences
- `preferred_certifications` (json) - Preferred certs
- `alternative_skills` (json) - Alternative skills
- `skill_importance` (integer) - Priority level
- `assessment_required` (integer) - Assessment flag

## Skill Assessment Model

### Overview
Tracks skill assessments and evaluations.

### Table Name
`skill_assessment`

### Key Attributes

#### Assessment Details
- `candidate_id` (integer) - Candidate reference
- `skill_id` (integer) - Skill reference
- `assessment_type` (string) - Assessment type
  - PRACTICAL
  - THEORETICAL
  - INTERVIEW
  - PORTFOLIO
- `assessment_score` (decimal) - Score achieved
- `assessment_date` (datetime) - Assessment date

#### Evaluation
- `evaluator_id` (integer) - Evaluator reference
- `evaluation_notes` (text) - Assessment notes
- `strengths` (text) - Identified strengths
- `weaknesses` (text) - Areas for improvement
- `recommendations` (text) - Recommendations

## Common Operations

```php
// Get candidate's top skills
CandidateSkill::find()
    ->where(['candidate_id' => $candidateId])
    ->andWhere(['>=', 'proficiency_level', 4])
    ->orderBy(['years_experience' => SORT_DESC])
    ->limit(5)
    ->all();

// Get required skills for position
RequestSkill::find()
    ->where(['request_id' => $requestId])
    ->andWhere(['is_mandatory' => 1])
    ->orderBy(['skill_importance' => SORT_DESC])
    ->all();

// Get valid certificates
CandidateCertificate::find()
    ->where(['candidate_id' => $candidateId])
    ->andWhere(['>', 'expiry_date', date('Y-m-d')])
    ->orderBy(['issue_date' => SORT_DESC])
    ->all();

// Get recent assessments
SkillAssessment::find()
    ->where([
        'candidate_id' => $candidateId,
        'assessment_type' => 'PRACTICAL'
    ])
    ->orderBy(['assessment_date' => SORT_DESC])
    ->limit(10)
    ->all();
```

## Implementation Details
- Skill categorization
- Proficiency tracking
- Certificate validation
- Assessment workflow
- Requirement matching
- Experience verification

## Business Rules
1. Skill validation rules
2. Certificate expiry
3. Assessment criteria
4. Requirement matching
5. Experience calculation
6. Verification process

## Reporting Features
1. Skill gap analysis
2. Certification status
3. Assessment results
4. Requirement coverage
5. Skill distribution
6. Validation statistics

## Security Considerations
1. Certificate verification
2. Assessment integrity
3. Experience validation
4. Data privacy
5. Access control
``` 