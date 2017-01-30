# Entity Relationship Plan

## Admin
Oversee the project, create company accounts, and approve payouts.

* **admin_id**
* admin_name
* admin_email
* admin_auth_key
* admin_password_hash
* admin_password_reset_token
* admin_created_at
* admin_updated_at

## Staff
Studenthub Staff, will be handling communication.

* **staff_id**
* staff_name
* staff_email
* staff_auth_key
* staff_password_hash
* staff_password_reset_token
* staff_created_at
* staff_updated_at

## Company
The companies which will be assigned candidates to work for them.

* **company_id**
* company_name
* company_email
* company_auth_key
* company_password_hash
* company_password_reset_token
* company_created_at
* company_updated_at

## Candidate
A user is a candidate until he is assigned to a company, then he becomes their employee.
Once removed from a company, he still has his candidate account to show his work history.

* **candidate_id**
* **company_id** (default null) - which company he currently works for
* candidate_name
* candidate_email
* candidate_civil_id
* candidate_auth_key (random + hidden @ 1st phase)
* candidate_password_hash (random + hidden @ 1st phase)
* candidate_password_reset_token (random + hidden @ 1st phase)
* candidate_created_at
* candidate_updated_at
