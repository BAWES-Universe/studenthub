# Contract Models

## Base Contract Model

### Overview
The Contract model serves as the base class for different types of employment contracts in the system. It provides common functionality for managing employment agreements.

### Table Name
`contract`

### Primary Key
- `contract_uuid` (char(60))

### Key Attributes

#### Basic Information
- `company_id` (integer) - Company reference
- `type` (string) - Contract type
  - FIXED_PRICE
  - HOURLY_RATE
  - MONTHLY_SALARY
- `detail` (text) - Contract details
- `start_date` (datetime) - Contract start date
- `end_date` (datetime) - Contract end date
- `transfer_cost` (decimal(12,3)) - Transfer cost
- `currency_code` (char(3)) - Currency code (default: KWD)
- `status` (tinyint) - Contract status
  - STATUS_DRAFT (0)
  - STATUS_ACTIVE (10)
  - STATUS_COMPLETED (20)
  - STATUS_TERMINATED (30)
  - STATUS_CANCELLED (40)

#### System Fields
- `created_by` (integer) - Creator reference
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Foreign Keys
- `company_id` -> `company.company_id`
- `created_by` -> `staff.staff_id`

## Monthly Salary Contract Model

### Overview
Represents employment contracts with monthly salary payments.

### Table Name
`monthly_salary_contract`

### Primary Key
- `ms_contract_uuid` (char(60))

### Key Attributes
- `contract_uuid` (char(60)) - Base contract reference
- `candidate_total` (decimal(12,3)) - Total candidate salary
- `company_total` (decimal(12,3)) - Total company cost
- `salary_day` (tinyint) - Salary payment day of month

## Hourly Contract Model

### Overview
Represents contracts based on hourly rate billing.

### Table Name
`hourly_contract`

### Primary Key
- `h_contract_uuid` (char(60))

### Key Attributes
- `contract_uuid` (char(60)) - Base contract reference
- `candidate_hourly_rate` (decimal(12,3)) - Candidate's hourly rate
- `company_hourly_rate` (decimal(12,3)) - Company's hourly rate

## Fixed Price Contract Model

### Overview
Represents contracts with a fixed total price for the entire project or work period.

### Table Name
`fixed_price_contract`

### Primary Key
- `fp_contract_uuid` (char(60))

### Key Attributes
- `contract_uuid` (char(60)) - Base contract reference
- `candidate_total` (decimal(12,3)) - Total payment to candidate
- `company_total` (decimal(12,3)) - Total cost to company
- `completion_percentage` (tinyint) - Project completion percentage

## Relationships

### Contract
- `company` -> `Company` (belongs to)
- `creator` -> `Staff` (belongs to)
- `fixedPriceContract` -> `FixedPriceContract` (has one)
- `hourlyContract` -> `HourlyContract` (has one)
- `monthlySalaryContract` -> `MonthlySalaryContract` (has one)
- `workHistories` -> `CandidateWorkHistory[]` (has many)

### Contract Types
Each contract type (Fixed Price, Hourly, Monthly Salary) has:
- `contract` -> `Contract` (belongs to)

## Common Operations

```php
// Create new contract
$contract = new Contract([
    'type' => Contract::TYPE_MONTHLY_SALARY,
    'company_id' => $companyId,
    'start_date' => $startDate,
    'currency_code' => 'KWD'
]);

// Create monthly salary details
$salaryContract = new MonthlySalaryContract([
    'contract_uuid' => $contract->contract_uuid,
    'candidate_total' => $candidateTotal,
    'company_total' => $companyTotal,
    'salary_day' => 5
]);

// Get contract with type-specific details
Contract::find()
    ->with(['monthlySalaryContract'])
    ->where(['contract_uuid' => $uuid])
    ->one();

// Get company's active contracts
Contract::find()
    ->where([
        'company_id' => $companyId,
        'status' => Contract::STATUS_ACTIVE
    ])
    ->all();

// Calculate contract financials
$contract->calculateTotals();
```

## Implementation Details
- Uses UUID for primary keys
- Implements contract lifecycle management
- Handles different payment structures
- Supports multiple contract types
- Tracks contract changes
- Manages contract states

## Business Rules
1. Contract type cannot be changed after creation
2. Each contract must have type-specific details
3. Financial calculations follow type-specific rules
4. Contract dates must be valid
5. Status transitions follow defined workflow
6. Currency code must be valid

## Security Notes
1. Contract creation requires company association
2. Financial data requires special permissions
3. Contract modifications are logged
4. Status changes require authorization
5. Currency conversions use official rates