# Company Model

## Overview
The Company model represents organizations that hire candidates through the platform. It contains comprehensive information about employers, their requirements, and business details.

## Table Name
`company`

## Primary Key
- `company_id` (integer)

## Key Attributes

### Basic Information
- `company_name` (string) - Company name in English
- `company_name_ar` (string) - Company name in Arabic
- `company_description` (text) - Company description
- `company_size` (integer) - Number of employees
- `company_website` (string) - Official website URL
- `company_logo` (string) - Company logo file path
- `company_banner` (string) - Company banner image path

### Contact Details
- `company_email` (string) - Primary email address
- `company_phone` (string) - Primary phone number
- `company_address` (string) - Physical address
- `company_area_uuid` (string) - Geographic area identifier

### Business Information
- `company_registration_no` (string) - Business registration number
- `company_tax_no` (string) - Tax registration number
- `company_industry` (string) - Industry sector
- `company_type` (string) - Company type/category
- `company_establishment_date` (date) - Founding date

### Location Data
- `company_latitude` (decimal) - Geographic latitude
- `company_longitude` (decimal) - Geographic longitude

### Financial Details
- `company_credit_limit` (decimal) - Credit limit
- `company_balance` (decimal) - Current balance
- `company_currency` (string) - Preferred currency
- `payment_terms` (integer) - Payment terms in days
- `billing_cycle` (string) - Billing frequency

### System Fields
- `company_status` (integer) - Account status
  - STATUS_ACTIVE (10)
  - STATUS_PENDING (0)
  - STATUS_SUSPENDED (2)
- `verified` (integer) - Verification status
- `featured` (integer) - Featured status flag
- `deleted` (integer) - Soft delete flag
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

## Foreign Keys
- `brand_id` -> `brand.brand_id`
- `country_id` -> `country.country_id`
- `area_id` -> `area.area_id`

## Relationships

### Has Many
- `companyContacts` -> `CompanyContact[]`
- `companyRequests` -> `CompanyRequest[]`
- `companyStats` -> `CompanyStats[]`
- `invoices` -> `Invoice[]`
- `balanceTransactions` -> `BalanceTransaction[]`
- `notes` -> `Note[]`

### Belongs To
- `brand` -> `Brand`
- `country` -> `Country`
- `area` -> `Area`

## Behaviors
- TimestampBehavior (handles created_at/updated_at)
- BlameableBehavior (tracks creator/updater)

## Implementation Details
- Uses soft deletion
- Supports multi-language (Arabic/English)
- Includes comprehensive validation rules
- Handles file uploads for logo and banner
- Implements credit limit checking
- Tracks company performance metrics

## Common Operations
```php
// Find active companies
Company::find()->where(['company_status' => Company::STATUS_ACTIVE])->all();

// Get company with related contacts
Company::find()
    ->with(['companyContacts'])
    ->where(['company_id' => $id])
    ->one();

// Get companies by industry
Company::find()
    ->where(['company_industry' => $industry])
    ->andWhere(['company_status' => Company::STATUS_ACTIVE])
    ->all();

// Get company balance
Company::find()
    ->select(['company_id', 'company_name', 'company_balance'])
    ->where(['company_id' => $id])
    ->asArray()
    ->one();
``` 