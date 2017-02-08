# SH Payroll Platform
## Youth Training Program

The payroll platform enables the admin to create `Corporate` accounts and `Studenthub Staff` accounts that will manage the employees that are part of the program.

## Types of Users

### Studenthub Staff

Studenthub staff will be offering trainee recruitment and administrative services to the corporate.

* Create and manage Employee accounts
* Assign and unassign employee to a company.

### Corporate

* Will sign a contract with admin for a fixed hourly rate they will pay for their assigned trainees.
* Will be able to list and view details of their currently assigned trainees.
* Every month, they will need to create a `TransferRequest` and fill in the number of hours worked by every assigned employee. System will calculate the total amount of money they need to transfer to `Studenthub Admin` to be sent out to the `Employees`.
* Transfer requests need to be verified and accepted by the corporate before it is sent out to admin.
* Once the transfer is received by admin, the corporate will be notified and be sent a `Receipt`
* System will notify admin if a corporate hasn't created a transfer request by the X day of every month.

### Employee

Employees are recruited to join the training program by Studenthub staff. They have to sign a contract and provide their identity documents and bank info. They will then be assigned to work for companies.

Employees are to also sign a "Tanazol" document forfeiting their rights as a full timer.

### Admin

* Approve a transfer request, send employer a receipt.
* Send an approved transfer request to the payment company via API which will transfer the salaries out to the employees.
