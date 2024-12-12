# Accounting Models

## Balance Account Model

### Overview
The Balance Account model represents financial accounts in the system, tracking balances and transactions for various entities.

### Table Name
`balance_account`

### Primary Key
- `balance_account_id` (integer)

### Key Attributes

#### Account Information
- `account_number` (string) - Unique account number
- `account_name` (string) - Account name
- `account_type` (string) - Type of account
  - OPERATING
  - ESCROW
  - PAYROLL
  - TAX
  - RESERVE
- `currency_code` (string) - Account currency
- `description` (text) - Account description

#### Balance Information
- `current_balance` (decimal) - Current account balance
- `available_balance` (decimal) - Available balance
- `reserved_balance` (decimal) - Reserved/held amount
- `minimum_balance` (decimal) - Required minimum balance
- `credit_limit` (decimal) - Credit limit if applicable

#### Status Information
- `account_status` (integer) - Account status
  - STATUS_ACTIVE (10)
  - STATUS_FROZEN (0)
  - STATUS_CLOSED (20)
- `last_transaction_date` (datetime) - Last transaction date
- `last_reconciliation_date` (datetime) - Last reconciliation date

#### System Fields
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Foreign Keys
- `company_id` -> `company.company_id`
- `created_by` -> `staff.staff_id`
- `updated_by` -> `staff.staff_id`

## Balance Transaction Model

### Overview
Records all balance changes and financial movements within accounts.

### Table Name
`balance_transaction`

### Primary Key
- `transaction_id` (integer)

### Key Attributes

#### Transaction Details
- `transaction_type` (string) - Type of transaction
  - CREDIT
  - DEBIT
  - TRANSFER
  - ADJUSTMENT
- `transaction_number` (string) - Unique transaction number
- `amount` (decimal) - Transaction amount
- `running_balance` (decimal) - Balance after transaction
- `description` (text) - Transaction description
- `reference` (string) - Reference number
- `transaction_date` (datetime) - Transaction date

#### Related Information
- `related_transaction_id` (integer) - Related transaction
- `source_type` (string) - Source of transaction
- `source_id` (integer) - Source reference
- `batch_id` (string) - Batch identifier

#### Status Information
- `transaction_status` (integer) - Status
  - STATUS_PENDING (0)
  - STATUS_COMPLETED (10)
  - STATUS_FAILED (20)
  - STATUS_REVERSED (30)
- `verification_status` (integer) - Verification status
- `notes` (text) - Transaction notes

## Invoice Model

### Overview
Manages billing and invoicing for services and contracts.

### Table Name
`invoice`

### Key Attributes

#### Invoice Information
- `invoice_number` (string) - Unique invoice number
- `invoice_date` (date) - Invoice date
- `due_date` (date) - Payment due date
- `total_amount` (decimal) - Total invoice amount
- `tax_amount` (decimal) - Tax amount
- `discount_amount` (decimal) - Discount amount
- `net_amount` (decimal) - Net payable amount
- `currency_code` (string) - Invoice currency

#### Status Information
- `invoice_status` (integer) - Status
  - STATUS_DRAFT (0)
  - STATUS_SENT (10)
  - STATUS_PAID (20)
  - STATUS_OVERDUE (30)
  - STATUS_CANCELLED (40)
- `payment_status` (integer) - Payment status
- `sent_date` (datetime) - Invoice sent date
- `paid_date` (datetime) - Payment received date

## Common Operations

```php
// Get account balance
BalanceAccount::find()
    ->select(['account_number', 'current_balance', 'available_balance'])
    ->where(['balance_account_id' => $id])
    ->one();

// Process balance transaction
$transaction = new BalanceTransaction([
    'transaction_type' => 'CREDIT',
    'amount' => $amount
]);
$transaction->process();

// Generate invoice
$invoice = new Invoice();
$invoice->generateFromContract($contract);

// Get account transactions
BalanceTransaction::find()
    ->where(['balance_account_id' => $accountId])
    ->orderBy(['transaction_date' => SORT_DESC])
    ->all();

// Reconcile account
$account->reconcile($toDate);
```

## Implementation Details
- Implements double-entry accounting
- Supports multi-currency accounts
- Real-time balance updates
- Transaction verification workflow
- Automated reconciliation
- Invoice generation and tracking

## Business Rules
1. Balance cannot go below minimum unless authorized
2. Transactions require appropriate permissions
3. Reconciliation performed daily
4. Invoice numbers follow defined format
5. Currency conversions use official rates
6. Audit trail maintained for all changes 