# Contract Models

## Core Contract Models

### Contract
Base contract model that can be extended into specific contract types.

**Fields:**
- `id`: UUID (Primary Key)
- `type`: Enum ('fixed_price', 'hourly', 'monthly_salary')
- `status`: Enum ('draft', 'pending', 'active', 'completed', 'terminated')
- `start_date`: Date
- `end_date`: Date (optional)
- `company_id`: UUID (Foreign Key)
- `candidate_id`: UUID (Foreign Key)
- `request_id`: UUID (Foreign Key)
- `created_at`: Timestamp
- `updated_at`: Timestamp

### ContractLineItem
Individual items or terms within a contract.

**Fields:**
- `id`: UUID (Primary Key)
- `contract_id`: UUID (Foreign Key)
- `description`: Text
- `amount`: Decimal
- `currency_id`: UUID (Foreign Key)

## Contract Types

### FixedPriceContract
Extension of Contract for fixed-price agreements.

**Additional Fields:**
- `total_amount`: Decimal
- `payment_schedule`: JSON
- `deliverables`: JSON

### HourlyContract
Extension of Contract for hourly-rate agreements.

**Additional Fields:**
- `hourly_rate`: Decimal
- `minimum_hours`: Integer
- `maximum_hours`: Integer

### MonthlySalaryContract
Extension of Contract for monthly salary agreements.

**Additional Fields:**
- `monthly_salary`: Decimal
- `benefits`: JSON
- `working_hours`: JSON

## Supporting Models

### ContractRevision
Tracks changes to contract terms.

### ContractApproval
Manages the approval workflow for contracts.

### ContractTemplate
Reusable contract templates for different scenarios. 