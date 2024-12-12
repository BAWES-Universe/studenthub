# Analytics Integration

## Segment Events

### Public Events
These events can be fired manually by uploading event excel sheet:

- Request Activity Added
- Transfer Created/Updated
- Fulltimer Created/Updated
- Suggestion Created/Updated
- Transfer Marked As Payment Received
- Candidate Transfer Paid
- Candidate Profile Created/Updated
- Candidate Invitation (Accepted/Rejected/Invited)
- Wallet Events (New Entry/Paid By Wallet)
- Expense Added
- Company Profile Created

### Additional Events
- Story Created/Updated
- Staff Created/Updated
- Candidate Profile Completed
- Company Profile Updated
- Payable Candidates

## Implementation Notes

- Mixpanel + Segment integration needed in frontend apps
- Campaign tracking plugin needed for:
  - Campaign cost
  - ROI
  - Conversion Rate from Campaign Traffic
  - Customer Acquisition Cost

## Configuration Tasks

- [ ] Add ability to configure Mixpanel account/key from admin
- [ ] Add ability to configure Segment account/key from admin
- [ ] Add ability to enable/disable Segment/Mixpanel from admin
- [ ] Test in production mode for Segment/Mixpanel integration

Note: For manual event uploads, use the `Datetime` column in excel to upload past events. 