# Education & Academic Models

## Candidate Education Model

### Overview
The Candidate Education model tracks educational history and qualifications of candidates.

### Table Name
`candidate_education`

### Primary Key
- `education_id` (integer)

### Key Attributes

#### Basic Information
- `candidate_id` (integer) - Candidate reference
- `degree_id` (integer) - Degree reference
- `major_id` (integer) - Major reference
- `university_id` (integer) - University reference
- `graduation_year` (integer) - Year of graduation
- `graduation_month` (integer) - Month of graduation

#### Academic Details
- `gpa` (decimal) - Grade point average
- `gpa_scale` (decimal) - GPA scale (e.g., 4.0)
- `honors` (string) - Academic honors
- `thesis_title` (string) - Thesis/dissertation
- `field_of_study` (string) - Specialization

#### Documentation
- `degree_certificate` (string) - Certificate file
- `transcript` (string) - Transcript file
- `verification_status` (integer) - Verification flag
- `verified_by` (integer) - Verifier reference
- `verification_date` (datetime) - Verification time

## Degree Model

### Overview
Represents academic degrees and qualifications.

### Table Name
`degree`

### Key Attributes

#### Basic Information
- `degree_name` (string) - Name in English
- `degree_name_ar` (string) - Name in Arabic
- `degree_code` (string) - Unique identifier
- `degree_group_id` (integer) - Group reference
- `degree_level` (integer) - Academic level

#### Classification
- `qualification_type` (string) - Type of degree
  - BACHELOR
  - MASTER
  - DOCTORATE
  - DIPLOMA
  - CERTIFICATE
- `study_duration` (integer) - Duration in months
- `credits_required` (integer) - Required credits

## Degree Group Model

### Overview
Categorizes degrees into logical groups.

### Table Name
`degree_group`

### Key Attributes

#### Basic Information
- `group_name` (string) - Name in English
- `group_name_ar` (string) - Name in Arabic
- `group_code` (string) - Unique identifier
- `parent_group_id` (integer) - Parent reference

#### Classification
- `academic_level` (string) - Academic level
- `field_category` (string) - Field category
- `industry_relevance` (json) - Industry mapping

## Major Model

### Overview
Represents fields of study and specializations.

### Table Name
`major`

### Key Attributes

#### Basic Information
- `major_name` (string) - Name in English
- `major_name_ar` (string) - Name in Arabic
- `major_code` (string) - Unique identifier
- `degree_group_id` (integer) - Group reference

#### Classification
- `field_type` (string) - Field type
  - TECHNICAL
  - HUMANITIES
  - SCIENCES
  - BUSINESS
- `specialization_areas` (json) - Sub-specialties
- `industry_alignment` (json) - Industry relevance

## University Model

### Overview
Represents educational institutions.

### Table Name
`university`

### Key Attributes

#### Basic Information
- `university_name` (string) - Name in English
- `university_name_ar` (string) - Name in Arabic
- `university_code` (string) - Unique identifier
- `country_id` (integer) - Country reference

#### Details
- `establishment_year` (integer) - Founded year
- `university_type` (string) - Institution type
  - PUBLIC
  - PRIVATE
  - TECHNICAL
- `accreditation` (json) - Accreditations
- `ranking` (json) - Rankings data

#### Contact
- `website` (string) - Official website
- `email` (string) - Contact email
- `phone` (string) - Contact phone
- `address` (text) - Physical address

## Common Operations

```php
// Get candidate's education
CandidateEducation::find()
    ->with(['degree', 'major', 'university'])
    ->where(['candidate_id' => $candidateId])
    ->orderBy(['graduation_year' => SORT_DESC])
    ->all();

// Get degrees by group
Degree::find()
    ->where(['degree_group_id' => $groupId])
    ->orderBy(['degree_level' => SORT_ASC])
    ->all();

// Get majors by field
Major::find()
    ->where(['field_type' => $fieldType])
    ->orderBy(['major_name' => SORT_ASC])
    ->all();

// Get universities by country
University::find()
    ->where([
        'country_id' => $countryId,
        'university_type' => 'PUBLIC'
    ])
    ->orderBy(['university_name' => SORT_ASC])
    ->all();
```

## Implementation Details
- Multi-language support
- Document management
- Verification workflow
- Academic hierarchy
- Institution management
- Qualification tracking

## Business Rules
1. Degree prerequisites
2. GPA validation
3. Document requirements
4. Verification process
5. Institution accreditation
6. Major-degree alignment

## Reporting Features
1. Education statistics
2. Qualification analysis
3. Institution reports
4. Verification status
5. Academic distribution
6. Field popularity

## Security Considerations
1. Document verification
2. Institution validation
3. Qualification authenticity
4. Data privacy
5. Access control
``` 