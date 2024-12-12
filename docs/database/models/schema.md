# Database Schema Diagrams

## Core Entity Relationships

```mermaid
erDiagram
    Candidate ||--o{ CandidateEducation : has
    Candidate ||--o{ CandidateExperience : has
    Candidate ||--o{ CandidateSkill : has
    Candidate ||--o{ CandidateWorkHistory : has
    Candidate ||--o{ RequestApplication : submits
    Candidate ||--o{ CandidateToken : has
    Candidate ||--o{ CandidateNotification : receives
    Candidate ||--o{ CandidateIdCard : has
    Candidate ||--o{ CandidateWarning : receives
    Candidate ||--o{ CandidateTag : has
    Candidate ||--o{ CandidateStats : has
    Candidate ||--o{ CandidateWorkingHour : logs
    Candidate ||--o{ CandidateWorkingDate : schedules
    Candidate ||--o{ CandidateVideoLog : has
    Candidate ||--o{ CandidateEvaluation : receives
    Candidate ||--o{ CandidateCertificate : has
    Candidate }|--|| Country : belongs_to
    Candidate }|--|| Bank : has
    Candidate }|--|| University : attended

    Company ||--o{ CompanyContact : has
    Company ||--o{ Request : posts
    Company ||--o{ CompanyStats : has
    Company ||--o{ Invoice : has
    Company ||--o{ BalanceTransaction : has
    Company }|--|| Brand : belongs_to
    Company }|--|| Country : located_in
    Company }|--|| Area : located_in

    Request ||--o{ RequestApplication : receives
    Request ||--o{ RequestInterview : schedules
    Request ||--o{ RequestSkill : requires
    Request ||--o{ RequestChecklist : has
    Request }|--|| Company : belongs_to
    Request }|--|| Staff : created_by

    Staff ||--o{ StaffExpenses : has
    Staff ||--o{ StaffLeave : takes
    Staff ||--o{ StaffSalary : earns
    Staff ||--o{ StaffNotification : receives
    Staff ||--o{ Request : manages
    Staff }|--|| Department : belongs_to
```

## Authentication & Permissions

```mermaid
erDiagram
    Admin ||--o{ AdminToken : has
    Staff ||--o{ ManagerToken : has
    Inspector ||--o{ InspectorToken : has
    Contact ||--o{ ContactToken : has
    Candidate ||--o{ CandidateToken : has

    PermissionUser }|--|| PermissionSection : has
    PermissionUser }|--|| PermissionSubSection : has
    Staff }|--o{ PermissionUser : has
```

## Financial System

```mermaid
erDiagram
    Company ||--o{ Invoice : generates
    Company ||--o{ BalanceTransaction : has
    Company ||--o{ BankTransaction : makes

    Contract ||--o{ FixedPriceContract : type
    Contract ||--o{ HourlyContract : type
    Contract ||--o{ MonthlySalaryContract : type

    BankTransaction ||--o{ BankTransactionLineItem : contains
    BankTransaction ||--o{ BankTransactionContact : involves

    BalanceAccount ||--o{ BalanceTransaction : records
    
    Discount }|--|| DiscountCategory : belongs_to
```

## Communication System

```mermaid
erDiagram
    Chat ||--o{ ChatMessage : contains
    Contact ||--o{ ContactEmail : has
    Contact ||--o{ ContactPhone : has
    Contact ||--o{ ContactInvitation : sends

    EmailCampaign ||--o{ EmailCampaignFilter : has
    MailLog }|--|| EmailCampaign : tracks

    MobileNotification }|--|| Candidate : notifies
    MobileNotification }|--|| Staff : notifies
```

## Evaluation System

```mermaid
erDiagram
    Exam ||--o{ ExamQuestion : contains
    ExamQuestion ||--o{ ExamQuestionChoice : has
    ExamQuestion ||--o{ ExamQuestionAnswer : receives

    InterviewEvaluation ||--o{ InterviewEvaluationNote : has
    InterviewEvaluationNote ||--o{ InterviewEvaluationNoteVersion : tracks

    CandidateEvaluation ||--o{ CandidateEvaluationAnswer : has
    CandidateEvaluation }|--|| CandidateEvalQues : uses
    CandidateEvaluation }|--|| CandidateEvalDeptQues : uses
```

## Location & Settings

```mermaid
erDiagram
    Country ||--o{ Area : contains
    Area ||--o{ Mall : contains
    
    Setting ||--o{ Currency : configures
    Setting ||--o{ BlockedIp : manages
    Setting ||--o{ BlockedIPSearch : tracks
```

## Work Management

```mermaid
erDiagram
    Fulltimer ||--o{ FulltimerExperience : has
    Fulltimer ||--o{ FulltimerSkill : has
    Fulltimer ||--o{ FulltimerTags : has

    DailyStandupQuestion ||--o{ DailyStandupAnswer : receives

    CandidateWorkHistory ||--o{ CandidateWorkLogFeedback : receives
    CandidateWorkingDate ||--o{ CandidateWorkingHour : schedules
``` 