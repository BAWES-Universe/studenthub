# Database Models Documentation

This directory contains detailed documentation for all database models in the system.

## Directory Structure

### Core Models (`core/`)
- `Candidate.md` - Job seekers and their profiles
- `Company.md` - Hiring organizations
- `Request.md` - Job postings and positions
- `Staff.md` - Internal system users

### User Management (`user/`)
- Authentication Models:
  - `AdminToken` - Admin authentication tokens
  - `CandidateToken` - Candidate authentication tokens
  - `ContactToken` - Contact authentication tokens
  - `ManagerToken` - Manager authentication tokens
  - `InspectorToken` - Inspector authentication tokens
- Permission Models:
  - `PermissionSection` - Permission categories
  - `PermissionSubSection` - Detailed permissions
  - `PermissionUser` - User-permission assignments

### Education & Skills (`education/`)
- Academic Models:
  - `CandidateEducation` - Educational history
  - `Degree` - Qualification types
  - `DegreeGroup` - Qualification categories
  - `Major` - Fields of study
  - `University` - Educational institutions
- Skills & Certifications:
  - `CandidateSkill` - Professional skills
  - `CandidateCertificate` - Professional certifications
  - `RequestSkill` - Required skills for positions

### Work Experience (`work/`)
- History Models:
  - `CandidateExperience` - Work experience records
  - `CandidateWorkHistory` - Detailed work history
  - `FulltimerExperience` - Full-time employment records
- Time Tracking:
  - `CandidateWorkingHour` - Work hours tracking
  - `CandidateWorkingDate` - Availability tracking
- Performance:
  - `CandidateWorkLogFeedback` - Work performance feedback
  - `CandidateWarning` - Warning records

### Assessment & Evaluation (`evaluation/`)
- Exam Models:
  - `Exam` - Assessment definitions
  - `ExamQuestion` - Assessment questions
  - `ExamQuestionAnswer` - Candidate responses
  - `ExamQuestionChoice` - Multiple choice options
- Interview Models:
  - `InterviewEvaluation` - Interview assessments
  - `InterviewEvaluationNote` - Interview feedback
  - `InterviewEvaluationNoteVersion` - Feedback history
- Department Evaluation:
  - `CandidateEvaluation` - Overall assessments
  - `CandidateEvalDeptQues` - Department-specific questions

### Financial Management (`financial/`)
- Contract Models:
  - `Contract` - Base contract model
  - `FixedPriceContract` - Fixed-price agreements
  - `HourlyContract` - Hourly rate agreements
  - `MonthlySalaryContract` - Monthly salary agreements
- Banking Models:
  - `Bank` - Banking institutions
  - `BankTransaction` - Transaction records
  - `BankTransactionContact` - Transaction parties
  - `BankTransactionLineItem` - Transaction details
- Accounting Models:
  - `BalanceAccount` - Account balances
  - `BalanceTransaction` - Balance changes
  - `Invoice` - Billing records
  - `Expense` - Expense records
- Discount Models:
  - `Discount` - Discount definitions
  - `DiscountCategory` - Discount categories

### Communication (`communication/`)
- Messaging Models:
  - `Chat` - Chat conversations
  - `ChatMessage` - Individual messages
  - `Note` - Internal notes
- Notification Models:
  - `CandidateNotification` - Candidate notifications
  - `StaffNotification` - Staff notifications
  - `MobileNotification` - Push notifications
- Email Models:
  - `MailLog` - Email tracking
  - `EmailCampaign` - Marketing campaigns
  - `EmailCampaignFilter` - Campaign targeting

### Location & Geography (`location/`)
- `Area` - Geographic areas
- `Country` - Country definitions
- `Mall` - Physical locations
- Location tracking fields in various models

### Security & Monitoring (`security/`)
- Security Models:
  - `BlockedIp` - IP restrictions
  - `BlockedIPSearch` - IP monitoring
- Monitoring Models:
  - `FiringHitmap` - System activity tracking
  - `CandidateVideoLog` - Video interview logs

## Common Attributes

Most models in the system share these common attributes:

- `created_at` - Timestamp of record creation
- `updated_at` - Timestamp of last update
- `deleted` - Soft deletion flag (0/1)
- `status` - Record status (typically ACTIVE=10, PENDING=0)
- `created_by` - Creator reference (for staff-created records)
- `updated_by` - Last updater reference

## Model Relationships

Common relationship patterns:

1. Core Entity Relationships:
   - Candidate -> Company (through RequestApplication)
   - Company -> Request (one-to-many)
   - Request -> RequestApplication (one-to-many)

2. Profile Relationships:
   - Candidate -> CandidateEducation (one-to-many)
   - Candidate -> CandidateExperience (one-to-many)
   - Candidate -> CandidateSkill (many-to-many)

3. Operational Relationships:
   - Staff -> Request (creator/updater)
   - Company -> CompanyContact (one-to-many)
   - Request -> RequestInterview (one-to-many)

4. Financial Relationships:
   - Company -> Invoice (one-to-many)
   - Company -> BalanceTransaction (one-to-many)
   - Staff -> StaffSalary (one-to-many)

## Implementation Notes

1. All models extend `\yii\db\ActiveRecord`
2. Common behaviors:
   - TimestampBehavior (created_at/updated_at)
   - BlameableBehavior (created_by/updated_by)
3. Soft deletion implemented across all models
4. Multi-language support for text fields (_ar suffix)
5. File handling through the Attachment model
6. Comprehensive audit logging for sensitive operations
7. Role-based access control integration
8. Geographic location tracking where relevant