# Banking Models

## Bank Model

### Overview
The Bank model represents banking institutions and their relationships with the system.

### Table Name
`bank`

### Primary Key
- `bank_id` (integer)

### Key Attributes
- `bank_name` (string) - Bank name in English
- `bank_name_ar` (string) - Bank name in Arabic
- `bank_code` (string) - Bank identifier code
- `swift_code` (string) - SWIFT/BIC code
- `routing_number` (string) - Bank routing number
- `country_id` (integer) - Country reference
- `status` (integer) - Bank status
  - STATUS_ACTIVE (10)
  - STATUS_INACTIVE (0)

## Bank Transaction Model

### Overview
Records all banking transactions in the system.

### Table Name
`bank_transaction`

### Primary Key
- `transaction_id` (integer)

### Key Attributes

#### Transaction Details
- `transaction_type` (string) - Type of transaction
  - DEPOSIT
  - WITHDRAWAL
  - TRANSFER
  - PAYMENT
  - REFUND
- `transaction_number` (string) - Unique transaction number
- `transaction_date` (datetime) - Transaction date
- `value_date` (date) - Value date
- `amount` (decimal) - Transaction amount
- `currency_code` (string) - Transaction currency
- `exchange_rate` (decimal) - Exchange rate if applicable
- `fees` (decimal) - Transaction fees
- `description` (text) - Transaction description
- `reference` (string) - External reference number

#### Status Information
- `transaction_status` (integer) - Status
  - STATUS_PENDING (0)
  - STATUS_COMPLETED (10)
  - STATUS_FAILED (20)
  - STATUS_CANCELLED (30)
- `verification_status` (integer) - Verification status
- `reconciliation_status` (integer) - Reconciliation status

#### System Fields
- `created_by` (integer) - Creator reference
- `updated_by` (integer) - Last updater reference
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Foreign Keys
- `bank_id` -> `bank.bank_id`
- `company_id` -> `company.company_id`
- `balance_account_id` -> `balance_account.balance_account_id`

## Bank Transaction Contact Model

### Overview
Links transactions to contacts involved in the transaction.

### Table Name
`bank_transaction_contact`

### Key Attributes
- `transaction_id` (integer) - Transaction reference
- `contact_id` (integer) - Contact reference
- `role` (string) - Contact's role
  - SENDER
  - RECEIVER
  - AUTHORIZED_BY
  - PROCESSED_BY
- `notes` (text) - Role-specific notes

## Bank Transaction Line Item Model

### Overview
Detailed breakdown of transaction components.

### Table Name
`bank_transaction_line_item`

### Key Attributes
- `transaction_id` (integer) - Transaction reference
- `item_type` (string) - Type of line item
- `description` (string) - Item description
- `amount` (decimal) - Item amount
- `tax_rate` (decimal) - Tax rate if applicable
- `tax_amount` (decimal) - Tax amount
- `account_code` (string) - Accounting code
- `cost_center` (string) - Cost center

## Common Operations

```php
// Find bank transactions by date range
BankTransaction::find()
    ->where(['>=', 'transaction_date', $startDate])
    ->andWhere(['<=', 'transaction_date', $endDate])
    ->all();

// Get transaction with all related data
BankTransaction::find()
    ->with(['contacts', 'lineItems', 'bank'])
    ->where(['transaction_id' => $id])
    ->one();

// Process new transaction
$transaction = new BankTransaction([
    'transaction_type' => 'TRANSFER',
    'amount' => $amount,
    'currency_code' => $currency
]);
$transaction->process();

// Reconcile transactions
BankTransaction::reconcileBatch($transactions);
```

## Implementation Details
- Implements double-entry accounting principles
- Supports multi-currency transactions
- Handles transaction reconciliation
- Tracks transaction statuses
- Manages banking relationships
- Supports audit trail

## Business Rules
1. Transactions must balance (debits = credits)
2. Currency conversions use official exchange rates
3. Certain transactions require multiple approvals
4. Reconciliation follows defined schedule
5. Failed transactions trigger notifications
6. Large transactions require additional verification 