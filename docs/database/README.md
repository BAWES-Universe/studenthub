# Database Documentation

## Database Diagram
The database structure can be viewed and edited at:
[Database Diagram Link](https://dbdiagram.io/d/Studenthub-66e02334550cd927eabe2c68) 

## Core Entities

### Candidate
The central entity representing job seekers in the system.
- Primary key: `candidate_id`
- Key attributes:
  - Personal info (name, email, phone, address)
  - Professional details (hourly rate, job search status)
  - Documents (resume, civil ID, photos)
  - Authentication (password hash, auth key)
- Status types:
  - READY (1)
  - PENDING (0)
  - ACTIVE (10)
- Related entities:
  - CandidateToken (authentication tokens)
  - CandidateStats (performance metrics)
  - CandidateTag (categorization)
  - CandidateNotification (communication)
  - CandidateIdCard (identification documents)

### Company
Represents organizations that hire candidates.
- Primary key: `company_id`
- Key attributes:
  - Company details (name, description, size)
  - Contact information
  - Billing details
- Related entities:
  - CompanyContact (contact persons)
  - CompanyRequest (job postings)
  - CompanyStats (performance metrics)
  - Brand (company branding)

### Request
Job requests/positions posted by companies.
- Primary key: `request_id`
- Key attributes:
  - Job details (title, description, requirements)
  - Status and timeline
  - Budget and payment terms
- Related entities:
  - RequestApplication (candidate applications)
  - RequestInterview (interview scheduling)
  - RequestSkill (required skills)
  - RequestChecklist (hiring process steps)

### Staff & Admin
Platform administrators and staff members.
- Types:
  - Staff (internal employees)
  - Admin (system administrators)
  - Inspector (quality control)
- Related entities:
  - StaffExpenses
  - StaffLeave
  - StaffSalary
  - StaffNotification
  - AdminToken
  - InspectorToken

## Supporting Entities

### User Management
- Authentication:
  - CandidateToken
  - AdminToken
  - ContactToken
  - ManagerToken
- Permissions:
  - PermissionSection
  - PermissionSubSection
  - PermissionUser

### Location & Geography
- Area (geographical regions)
- Country (supported countries)
- Mall (physical locations)
- Location tracking with coordinates

### Education & Skills
- CandidateEducation (academic history)
- CandidateSkill (professional skills)
- Degree (qualification types)
- DegreeGroup (qualification categories)
- Major (fields of study)
- University (educational institutions)
- CandidateCertificate (professional certifications)

### Experience & Work History
- CandidateExperience (work experience)
- CandidateWorkHistory (detailed work records)
- CandidateWorkingHour (time tracking)
- CandidateWorkLogFeedback (performance feedback)
- CandidateWorkingDate (availability)
- Fulltimer (full-time employee records)
- FulltimerExperience
- FulltimerSkill

### Evaluation & Assessment
- Exam (skill assessments)
- ExamQuestion (assessment content)
- ExamQuestionAnswer (candidate responses)
- ExamQuestionChoice (multiple choice options)
- InterviewEvaluation (interview feedback)
- InterviewEvaluationNote (detailed comments)
- CandidateEvaluation (overall assessment)
- CandidateEvalDeptQues (department-specific questions)

### Financial Management
- Contract types:
  - FixedPriceContract
  - HourlyContract
  - MonthlySalaryContract
- Banking:
  - Bank
  - BankTransaction
  - BankTransactionContact
  - BankTransactionLineItem
- Accounting:
  - BalanceAccount
  - BalanceTransaction
  - Invoice
  - Expense
- Discounts:
  - Discount
  - DiscountCategory

### Communication
- Chat (messaging system)
- ChatMessage (individual messages)
- MobileNotification (push notifications)
- MailLog (email tracking)
- EmailCampaign (marketing communications)
- EmailCampaignFilter (targeting rules)
- Note (internal comments)

### Security & Monitoring
- BlockedIp (security restrictions)
- BlockedIPSearch (IP monitoring)
- FiringHitmap (system activity tracking)
- CandidateVideoLog (video interview logs)
- CandidateWarning (warning records)

## Key Relationships
1. Candidate -> Company (through RequestApplication)
2. Company -> Request (one-to-many)
3. Request -> RequestApplication (one-to-many)
4. Candidate -> CandidateEducation (one-to-many)
5. Candidate -> CandidateWorkHistory (one-to-many)
6. Company -> CompanyContact (one-to-many)
7. Request -> RequestInterview (one-to-many)
8. Candidate -> CandidateSkill (many-to-many)
9. Staff -> StaffSalary (one-to-many)
10. Company -> Brand (many-to-one)

## Important Notes
- Most entities include timestamps (`_created_at`, `_updated_at`)
- Soft deletion is implemented through `deleted` flag
- Multi-language support with Arabic translations for key fields
- Location tracking with latitude/longitude for geographical features
- File attachments handled through dedicated Attachment model
- Comprehensive audit logging for sensitive operations
- Token-based authentication for all user types
- Hierarchical permission system for staff access control