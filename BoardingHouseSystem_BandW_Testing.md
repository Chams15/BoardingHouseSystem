

# **BOARDING HOUSE MANAGEMENT SYSTEM**

Comprehensive Black-Box & White-Box Test Case Specification

CL17L: SOFTWARE ENGINEERING 2 · Milestone 6: Functional & White-Box Test Case Specification

| System | Boarding House Management System (Laravel 11 \+ Inertia \+ React) |
| :---- | :---- |
| **Modules Covered** | A. Tenant Management B. Room & Facility Management C. Billing & Payment D. Maintenance & Complaint Management E. Security & Access Control |
| **Testing Types** | Black-Box (Functional) Testing \+ White-Box (Branch Coverage) Testing |

| SCENARIOS | EXPECTED RESULT | ACTUAL RESULT | TEST RESULT (Pass/Fail) | ACTION TAKEN |
| :---- | :---- | :---- | :---- | :---- |
| Admin Register: Cannot create tenant if full name is empty. | Display validation error: "The full name field is required." |  |  |  |
| Admin Register: Cannot create tenant if email is empty. | Display validation error: "The email field is required." |  |  |  |
| Admin Register: Cannot create tenant if email format is invalid. | Display validation error: "The email must be a valid email address." |  |  |  |
| Admin Register: Cannot create tenant if email already exists. | Display validation error: "The email has already been taken." |  |  |  |
| Admin Register: Cannot create tenant if contact number is empty. | Display validation error: "The contact number field is required." |  |  |  |
| Admin Register: Cannot create tenant if password is empty. | Display validation error: "The password field is required." |  |  |  |
| Admin Register: Cannot create tenant if password is fewer than 8 characters. | Display validation error: "The password must be at least 8 characters." |  |  |  |
| Admin Register: Create tenant successfully when all required fields are valid. | Display: "Tenant created successfully." and redirect to Tenants list. |  |  |  |
| Admin Update: Cannot update tenant if required fields are cleared. | Display warning and highlight empty required fields. |  |  |  |
| Admin Update: Cannot update tenant if email is changed to one already in use by another tenant. | Display validation error: "The email has already been taken." |  |  |  |
| Admin Update: Update tenant successfully with valid changes. | Display: "Tenant updated successfully." and redirect to Tenants list. |  |  |  |
| Admin Update: Deactivate tenant by setting is\_active to false. | Tenant account is deactivated; tenant cannot log in. |  |  |  |
| Admin Delete: Delete tenant; all active leases are auto-terminated and rooms set to Available. | Display: "Tenant deleted successfully. N active lease(s) were terminated and room(s) marked as available." |  |  |  |
| Admin Delete: Cancel deletion (no action taken). | No changes occur; tenant record remains. |  |  |  |
| Self-Register: Tenant cannot self-register if email is already blacklisted. | Display error: "This email address is not permitted to register." |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Self-Register: Tenant self-registers successfully with valid credentials. | Account created with Pending status; admin review required. |  |  |  |
| Verification: Admin approves pending tenant account. | Tenant account status set to Active; tenant can now log in. |  |  |  |
| Verification: Admin denies pending tenant account. | Tenant account is rejected; tenant notified. |  |  |  |
| Lease Create: Cannot create lease if tenant\_id is missing. | Display validation error: "The tenant id field is required." |  |  |  |
| Lease Create: Cannot create lease if room\_id is missing. | Display validation error: "The room id field is required." |  |  |  |
| Lease Create: Cannot create lease if tenant already has an active lease in the same room. | Display error: "This tenant already has an active lease in this room." |  |  |  |
| Lease Create: Cannot create lease if room already has an active lease. | Display error: "This room already has an active lease." |  |  |  |
| Lease Create: Create lease successfully with valid inputs; room status changes to Occupied. | Display: "Monthly lease created successfully with auto-renewal enabled." Room status updated to Occupied. |  |  |  |
| Lease Update: Terminate lease; room status reverts to Available. | Lease status set to Terminated; room status set to Available. |  |  |  |
| Lease Update: Re-activate a terminated lease; room status changes to Occupied. | Lease status set to Active; room status set to Occupied. |  |  |  |
| Move-Out: Admin processes move-out; lease status changes to Pending\_MoveOut. | Lease contract\_status updated to Pending\_MoveOut. |  |  |  |
| Move-Out: Admin approves pending move-out; room set to Available and lease Terminated. | Display: "Move-out approved. Room is now available." |  |  |  |
| Move-Out: Admin attempts to approve move-out on contract not in Pending\_MoveOut status. | Display error: "Contract does not have a pending move-out request." |  |  |  |
| Search: Admin searches tenants by email; matching results displayed. | Tenants matching the email filter are listed. |  |  |  |
| Search: Admin searches tenants by full name; matching results displayed. | Tenants matching the name filter are listed. |  |  |  |
| Search: Search returns empty list when no tenants match. | Empty list displayed with no results. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Add Room: Cannot add room if room number is empty. | Display validation error: "The room number field is required." |  |  |  |
| Add Room: Cannot add room if room number already exists. | Display validation error: "The room number has already been taken." |  |  |  |
| Add Room: Cannot add room if category is empty. | Display validation error: "The category field is required." |  |  |  |
| Add Room: Cannot add room if price\_monthly is empty. | Display validation error: "The price monthly field is required." |  |  |  |
| Add Room: Cannot add room if price\_monthly is negative. | Display validation error: "The price monthly must be at least 0." |  |  |  |
| Add Room: Cannot add room if capacity is less than 1\. | Display validation error: "The capacity must be at least 1." |  |  |  |
| Add Room: Cannot add room if status is not one of Available/Occupied/Maintenance. | Display validation error: "The selected status is invalid." |  |  |  |
| Add Room: Cannot upload room image if file type is not jpg/jpeg/png/webp. | Display validation error: "The room image must be a file of type: jpg, jpeg, png, webp." |  |  |  |
| Add Room: Cannot upload room image if file exceeds 10 MB. | Display validation error: "The room image must not be greater than 10240 kilobytes." |  |  |  |
| Add Room: Create room successfully with all valid inputs. | Display: "Room created successfully." and redirect to Rooms list. |  |  |  |
| Update Room: Update room details successfully with valid changes. | Display: "Room updated successfully." and redirect to Rooms list. |  |  |  |
| Update Room: Room image replaced when a new valid image is uploaded. | Old image deleted from storage; new image stored and displayed. |  |  |  |
| Delete Room: Cannot delete room that has active leases. | Display error: "Cannot delete a room with active leases. Please terminate all leases first." |  |  |  |
| Delete Room: Delete room successfully when no active leases exist. | Display: "Room deleted successfully." |  |  |  |
| Room Status: Admin marks room as Under Maintenance. | Room status updated to Maintenance; room no longer appears as Available. |  |  |  |
| Room Status: Admin marks room as Available. | Room status updated to Available; room appears in availability list. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Boarding Request: Tenant submits boarding request for available room. | Request saved with status Pending; admin notified. |  |  |  |
| Boarding Request: Admin approves boarding request; room becomes Occupied; lease created. | Display: "Request approved. Room is now occupied." Lease created with auto-renew enabled. |  |  |  |
| Boarding Request: Admin rejects boarding request; room remains Available. | Display: "Request rejected." Room status unchanged. |  |  |  |
| Boarding Request: Admin approves request but room is no longer available (concurrent race condition). | Display error: "Room is no longer available." |  |  |  |
| Boarding Request: Approving one request auto-rejects all other pending requests for the same room. | All other Pending requests for that room are set to Rejected. |  |  |  |
| Search: Admin searches rooms by room number; matching rooms displayed. | Rooms matching the room number filter are listed. |  |  |  |
| Search: Admin searches rooms by status; matching rooms displayed. | Rooms matching the status filter are listed. |  |  |  |
| Search: Tenant searches available rooms; only Available rooms are shown. | Only rooms with status Available are listed. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Monthly Billing: System generates rent bills for all Active leases. | Result message shows Created: N, Skipped: 0, Marked Overdue: 0\. |  |  |  |
| Monthly Billing: System skips bill generation if bill for the same period already exists (idempotent). | Skipped count increments; no duplicate bills created. |  |  |  |
| Monthly Billing: System marks unpaid bills as Overdue when due date has passed. | Bill payment\_status updated to Overdue. |  |  |  |
| Monthly Billing: System does NOT generate bills for Terminated or Pending\_MoveOut leases. | No bill created for non-Active contracts. |  |  |  |
| Discount Bill: Admin cannot apply discount to a Paid bill. | Display error: "Paid bills cannot be adjusted." |  |  |  |
| Discount Bill: Admin cannot apply discount exceeding remaining bill balance. | Display error: "Adjustment amount exceeds the remaining bill balance." |  |  |  |
| Discount Bill: Admin applies valid discount; amount\_due reduced correctly. | Display: "Discount applied successfully." amount\_due \= original \- discount. |  |  |  |
| Waive Bill: Admin waives full remaining bill amount; payment\_status set to Waived. | Display: "Bill waived successfully." payment\_status \= Waived. |  |  |  |
| Waive Bill: Waiving bill creates a corresponding Waiver payment record. | Payment record with method='Waiver' and provider\_status='waived' created. |  |  |  |
| Offline Payment: Admin records offline payment for an Unpaid bill. | Display: "Offline payment recorded successfully and receipt generated." Bill status updated. |  |  |  |
| Offline Payment: Admin cannot record offline payment for an already Paid bill. | Display error: "This bill is already settled." |  |  |  |
| Offline Payment: Admin cannot record payment if bill has no remaining balance. | Display error: "Bill has no remaining balance." |  |  |  |
| Offline Payment: Reference number auto-generated if not provided (format: OFF-XXXXXXXX). | Reference number auto-assigned with OFF- prefix. |  |  |  |
| Bill Status: Bill remains Unpaid when no payment exists and due date is in the future. | payment\_status \= Unpaid. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Bill Status: Bill status changes to Pending when an in-progress GCash/PayMongo checkout exists. | payment\_status \= Pending. |  |  |  |
| Bill Status: Bill status changes to Paid after successful PayMongo payment confirmation. | payment\_status \= Paid. |  |  |  |
| Financial Report: Admin views monthly financial summary. | Report displays revenue breakdown by month. |  |  |  |
| Financial Report: Admin filters report by date range. | Only records within the specified date range are shown. |  |  |  |
| Financial Report: Admin exports report successfully. | File downloaded in requested format. |  |  |  |
| Search Bills: Admin searches bills by tenant name; matching results shown. | Bills linked to matching tenants are listed. |  |  |  |
| Search Bills: Admin searches bills by room number; matching results shown. | Bills linked to matching rooms are listed. |  |  |  |
| Search Bills: Admin searches bills by payment status; matching results shown. | Bills matching the status filter are listed. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Submit Ticket: Cannot submit ticket if issue description is empty. | Display validation error: "The issue desc field is required." |  |  |  |
| Submit Ticket: Cannot submit ticket if priority is not Low/Medium/High. | Display validation error: "The selected priority is invalid." |  |  |  |
| Submit Ticket: Cannot attach issue photo if file type is not jpg/jpeg/png. | Display validation error: "Issue photo must be a JPG or PNG image." |  |  |  |
| Submit Ticket: Cannot attach issue photo if file exceeds 10 MB. | Display validation error: "Issue photo must not be greater than 10MB." |  |  |  |
| Submit Ticket: Tenant submits maintenance request successfully with valid inputs. | Display: "Maintenance request submitted." Ticket created with status Pending. |  |  |  |
| Submit Ticket: Ticket is created with status Pending by default. | New ticket status \= Pending. |  |  |  |
| Admin Update Ticket: Admin adds contractor notes to a Pending ticket; status changes to In Progress. | Display: "Ticket reply saved successfully." Status updated to In Progress. |  |  |  |
| Admin Update Ticket: Admin updates priority of a ticket without adding notes; status remains unchanged. | Only priority is updated; status unchanged. |  |  |  |
| Admin Update Ticket: Admin cannot update a Resolved ticket's status back to In Progress via notes. | Resolved ticket status remains Resolved regardless of notes. |  |  |  |
| Tenant Resolve: Tenant marks their own In Progress ticket as Resolved. | Display: "Ticket marked as resolved." Status set to Resolved; resolved\_at timestamp recorded. |  |  |  |
| Tenant Resolve: Tenant cannot resolve a ticket that is still Pending (not yet In Progress). | Display error: "Only tickets that are in progress can be resolved." |  |  |  |
| Tenant Resolve: Tenant cannot resolve a ticket belonging to another tenant. | HTTP 403 Forbidden returned. |  |  |  |
| Search: Admin searches tickets by issue description; matching tickets shown. | Tickets matching the description search are listed. |  |  |  |
| Search: Admin searches tickets by reporter name; matching tickets shown. | Tickets reported by matching tenant are listed. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Filter: Admin filters tickets by status (Pending/In Progress/Resolved). | Only tickets with the selected status are displayed. |  |  |  |
| Analytics: Recurring issues report shows rooms with 2+ tickets in the last 7 days. | Rooms with \>= 2 tickets in the past 7 days are listed in the recurring-by-room report. |  |  |  |
| Analytics: Recurring issues report shows tenants with 2+ tickets in the last 7 days. | Tenants with \>= 2 tickets in the past 7 days are listed in the recurring-by-tenant report. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Log Visitor: Cannot log visitor if visitor name is empty. | Display validation error: "The visitor name field is required." |  |  |  |
| Log Visitor: Cannot log visitor if tenant\_visited does not reference a valid Tenant user. | Display validation error: "The selected tenant visited is invalid." |  |  |  |
| Log Visitor: Cannot attach visitor photo if file type is not jpg/jpeg/png. | Display validation error: "Visitor photo must be a JPG or PNG image." |  |  |  |
| Log Visitor: Cannot attach visitor photo if file exceeds 10 MB. | Display validation error: "Visitor photo must not be greater than 10MB." |  |  |  |
| Log Visitor: Admin/Tenant logs visitor successfully with valid inputs. | Display: "Visitor logged successfully." Log entry created with current time\_in. |  |  |  |
| Checkout Visitor: Admin/Tenant records visitor departure (time\_out). | Display: "Visitor checked out." time\_out recorded with current timestamp. |  |  |  |
| Checkout Visitor: Cannot check out a visitor who is already checked out. | Display error: "Visitor is already checked out." |  |  |  |
| Checkout Visitor (Tenant): Tenant cannot check out a visitor belonging to another tenant. | Display error: "You cannot check out this visitor." |  |  |  |
| Incident Record: Admin cannot record security incident if title is empty. | Display validation error: "The title field is required." |  |  |  |
| Incident Record: Admin cannot record security incident if description is empty. | Display validation error: "The description field is required." |  |  |  |
| Incident Record: Admin cannot record security incident if severity is not Low/Medium/High. | Display validation error: "The selected severity is invalid." |  |  |  |
| Incident Record: Admin records incident successfully; status defaults to Open. | Display: "Security incident recorded." Incident saved with status \= Open. |  |  |  |
| Incident Update: Admin updates incident status to Resolved; resolved\_at timestamp is set. | Status updated to Resolved; resolved\_at recorded. |  |  |  |
| Incident Update: Admin updates incident status to Investigating; resolved\_at remains null. | Status updated to Investigating; resolved\_at \= null. |  |  |  |

| SCENARIOS  | EXPECTED RESULT  | ACTUAL RESULT  | TEST RESULT (Pass/Fail)  | ACTION TAKEN  |
| :---- | :---- | :---- | :---- | :---- |
| Incident Update: Admin cannot set incident status to an invalid value. | Display validation error: "The selected status is invalid." |  |  |  |
| Blacklist Add: Admin cannot add email to blacklist if email field is empty. | Display validation error: "The email field is required." |  |  |  |
| Blacklist Add: Admin cannot add email to blacklist if email format is invalid. | Display validation error: "The email must be a valid email address." |  |  |  |
| Blacklist Add: Admin cannot add email that is already on the blacklist. | Display validation error: "The email has already been taken." |  |  |  |
| Blacklist Add: Admin cannot add email without providing a reason. | Display validation error: "The reason field is required." |  |  |  |
| Blacklist Add: Admin adds email to blacklist successfully. | Display: "Email added to blacklist." Entry created with banned\_at timestamp. |  |  |  |
| Blacklist Remove: Admin removes email from blacklist. | Display: "Removed from blacklist." Entry deleted. |  |  |  |
| Search: Admin searches visitor logs by visitor name; matching results shown. | Visitor logs matching the name are listed. |  |  |  |
| Search: Admin searches visitor logs by host tenant name; matching results shown. | Visitor logs for matching tenant are listed. |  |  |  |
| Search: Admin searches incidents by title/severity/status; matching results shown. | Incidents matching the filter are listed. |  |  |  |

# **WHITE-BOX TESTING**

All white-box tests use Branch Coverage as the testing technique, targeting critical service methods and controller logic identified from the source code.

| TC ID  | Feature/Module  | Technique  | Preconditions  | Test Input  | Expected Output  | Actual Output  | Status  |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_TE N\_001** | TenantController: :destroy | Branch Coverage | Tenant ID=1 has 2 active leases | DELETE /admin/tenants/1 | Both leases terminated; both rooms set Available; success message with count=2 |  |  |
| **WBT\_TE N\_002** | TenantController: :destroy | Branch Coverage | Tenant ID=2 has 0 active leases | DELETE /admin/tenants/2 | Tenant deleted; success message with 0 active leases terminated |  |  |
| **WBT\_TE N\_003** | TenantController: :store | Branch Coverage | emergency\_contac t provided | full\_name, email, contact\_number, password, emergency\_contact="Jane Doe" | TenantProfile created with emergency\_contact='Jane Doe' |  |  |
| **WBT\_TE N\_004** | TenantController: :store | Branch Coverage | emergency\_contac t not provided (nullable) | full\_name, email, contact\_number, password (no emergency\_contact) | TenantProfile created with emergency\_contact=null |  |  |
| **WBT\_TE N\_005** | LeaseController:: store | Branch Coverage | Tenant already has active lease in room | tenant\_id=1, room\_id=1 (already active) | Error: "This tenant already has an active lease in this room." |  |  |
| **WBT\_TE N\_006** | LeaseController:: store | Branch Coverage | Room already has active lease (different tenant) | tenant\_id=2, room\_id=1 (room occupied by tenant 1\) | Error: "This room already has an active lease." |  |  |
| **WBT\_TE N\_007** | LeaseController:: store | Branch Coverage | Valid inputs; no conflicts | tenant\_id=3, room\_id=2, start\_date=2026-05-01, security\_deposit=5000 | Lease created; end\_date \= start\_date \+ 1 month; room status \= Occupied; auto\_renew \= true |  |  |
| **WBT\_TE N\_008** | LeaseController:: update | Branch Coverage | Old status=Active; new status=Terminated | contract\_status=Terminated | Room status set to Available; auto\_renew=false; next\_renewal\_date=null |  |  |
| **WBT\_TE N\_009** | LeaseController:: update | Branch Coverage | Old status=Terminated; new status=Active | contract\_status=Active | Room status set to Occupied; next\_renewal\_date recalculated if null |  |  |
| **WBT\_TE N\_010** | LeaseController:: update | Branch Coverage | Changing tenant; new tenant has existing active lease in same room | tenant\_id=99 (already has active lease in room\_id=1) | Error: "This tenant already has an active lease in this room." |  |  |
| **WBT\_TE N\_011** | RoomManageme ntController::appr oveMoveOut | Branch Coverage | Contract status \= Pending\_MoveOut | POST /admin/rooms/contracts/{id}/ approve-move-out | completeMoveOut() called; room set to Available; lease Terminated |  |  |

| TC ID  | Feature/Module  | Technique  | Preconditions  | Test Input  | Expected Output  | Actual Output  | Status  |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_TE N\_012** | RoomManageme ntController::appr oveMoveOut | Branch Coverage | Contract status \= Active (not Pending\_MoveOut) | POST /admin/rooms/contracts/{id}/ approve-move-out | Error: "Contract does not have a pending move-out request." |  |  |

| TC ID  | Feature/Module  | Technique  | Preconditions  | Test Input  | Expected Output  | Actual Output  | Status  |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_RO M\_001** | RoomRequestSe rvice::approveRe quest | Branch Coverage | Request status=Pending; room status=Available | approveRequest(roomRequest) | Lease created; request=Approved; room=Occupied; other pending requests Rejected |  |  |
| **WBT\_RO M\_002** | RoomRequestSe rvice::approveRe quest | Branch Coverage | Request already Approved (status \!= Pending) | approveRequest(roomRequest) | Exception: "Request not found or already processed." |  |  |
| **WBT\_RO M\_003** | RoomRequestSe rvice::approveRe quest | Branch Coverage | Room no longer Available (concurrent booking) | approveRequest(roomRequest) where room.status='Occupied' | Exception: "Room is no longer available." |  |  |
| **WBT\_RO M\_004** | RoomRequestSe rvice::approveRe quest | Branch Coverage | 5 pending requests for room; 1 approved | approveRequest(requestA) | requestA=Approved; remaining 4 requests set to Rejected |  |  |
| **WBT\_RO M\_005** | RoomManageme ntController::stor eRoom | Branch Coverage | Valid inputs; image provided | room\_number='R01', category='Single', price=5000, capacity=1, status='Available', image=valid.jpg | Room created; room\_image\_path stored; 'Room created successfully.' returned |  |  |
| **WBT\_RO M\_006** | RoomManageme ntController::stor eRoom | Branch Coverage | Valid inputs; no image | room\_number='R02', category='Double', price=8000, capacity=2, status='Available' (no image) | Room created; room\_image\_path=null |  |  |
| **WBT\_RO M\_007** | RoomManageme ntController::upd ateRoom | Branch Coverage | Update with new image; old image exists on disk | room\_image=new\_photo.jpg (old path exists) | Old image deleted from storage; new image stored; room updated |  |  |
| **WBT\_RO M\_008** | RoomManageme ntController::upd ateRoom | Branch Coverage | Update without new image | No room\_image in request | room\_image\_path unchanged; room details updated |  |  |
| **WBT\_RO M\_009** | RoomManageme ntController::dele teRoom | Branch Coverage | Room has active leases (count \> 0\) | DELETE /admin/rooms/{room\_id} | Error: "Cannot delete a room with active leases. Please terminate all leases first." |  |  |
| **WBT\_RO M\_010** | RoomManageme ntController::dele teRoom | Branch Coverage | Room has no active leases | DELETE /admin/rooms/{room\_id} | "Room deleted successfully." |  |  |
| **WBT\_RO M\_011** | RoomManageme ntController::rem oveTenant | Branch Coverage | Room has active lease (status=Active) | POST /admin/rooms/{room}/remov e-tenant | Lease status=Terminated; room status=Available |  |  |

| TC ID  | Feature/Module  | Technique  | Preconditions  | Test Input  | Expected Output  | Actual Output  | Status  |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_BIL \_001** | MonthlyBillingSer vice::run | Branch Coverage | 3 active contracts; no prior bills this month | billingDate \= 2026-05-01 | created=3, skipped=0, marked\_overdue=0, errors=\[\] |  |  |
| **WBT\_BIL \_002** | MonthlyBillingSer vice::run (idempotent) | Branch Coverage | Bills already exist for all active contracts this month | billingDate \= 2026-05-01 (run second time) | created=0, skipped=3, marked\_overdue=0 |  |  |
| **WBT\_BIL \_003** | MonthlyBillingSer vice::markOverdu eBills | Branch Coverage | 2 unpaid bills with due\_date in the past | today \= 2026-05-03 (bills due 2026-04-30) | Both bills set to Overdue; marked\_overdue=2 |  |  |
| **WBT\_BIL \_004** | MonthlyBillingSer vice::markOverdu eBills | Branch Coverage | 1 bill already Paid; 1 bill Unpaid and overdue | today \= 2026-05-03 | Only unpaid bill set to Overdue; Paid bill unchanged; marked\_overdue=1 |  |  |
| **WBT\_BIL \_005** | AdminBillingCont roller::discountBill | Branch Coverage | Bill is Paid | amount=100, reason='Test' | Error: "Paid bills cannot be adjusted." |  |  |
| **WBT\_BIL \_006** | AdminBillingCont roller::discountBill | Branch Coverage | Bill amount\_due=500; discount requested=600 | amount=600, reason='Test' | Error: "Adjustment amount exceeds the remaining bill balance." |  |  |
| **WBT\_BIL \_007** | AdminBillingCont roller::discountBill | Branch Coverage | Bill amount\_due=500; discount=100 (valid) | amount=100, reason='Early payment' | amount\_due=400; discount\_amount=100; version incremented by 1 |  |  |
| **WBT\_BIL \_008** | AdminBillingCont roller::waiveBill | Branch Coverage | Bill amount\_due=500; waive entire amount | amount=500, reason='Special waiver' | amount\_due=0; payment\_status=Waived; Waiver payment record created |  |  |
| **WBT\_BIL \_009** | AdminBillingCont roller::recordOffli nePayment | Branch Coverage | Bill is Unpaid; amount\_due=3000 | reference\_no='REF001', notes='Cash payment' | Payment created; amount\_paid=3000; provider\_status='paid'; receipt generated |  |  |
| **WBT\_BIL \_010** | AdminBillingCont roller::recordOffli nePayment | Branch Coverage | Bill is already Paid | reference\_no='REF002' | Error: "This bill is already settled." |  |  |
| **WBT\_BIL \_011** | AdminBillingCont roller::recordOffli nePayment | Branch Coverage | Bill amount\_due \= 0 (fully waived) | reference\_no='REF003' | Error: "Bill has no remaining balance." |  |  |

| TC ID  | Feature/Module  | Technique  | Preconditions  | Test Input  | Expected Output  | Actual Output  | Status  |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_BIL \_012** | AdminBillingCont roller::recordOffli nePayment | Branch Coverage | No reference\_no provided | reference\_no=null | Reference auto-generated with 'OFF-' prefix (8 random uppercase chars) |  |  |
| **WBT\_BIL \_013** | Bill::resolveUnset tledStatus | Branch Coverage | due\_date is in the future | due\_date=2026-12-31, payment\_status=Unpaid | Returns 'Unpaid' |  |  |
| **WBT\_BIL \_014** | Bill::resolveUnset tledStatus | Branch Coverage | due\_date is in the past | due\_date=2026-01-01, payment\_status=Unpaid | Returns 'Overdue' |  |  |

| TC ID | Feature/Module | Technique | Preconditions | Test Input | Expected Output | Actual Output | Status |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_MN T\_001** | MaintenanceCont roller (Tenant)::store | Branch Coverage | Valid inputs; issue\_photo provided | issue\_desc='Leaking pipe', priority='High', room\_id=1, issue\_photo=photo.jpg | Ticket created; status=Pending; issue\_photo\_path stored |  |  |
| **WBT\_MN T\_002** | MaintenanceCont roller (Tenant)::store | Branch Coverage | Valid inputs; no issue\_photo | issue\_desc='Broken lock', priority='Low' (no photo) | Ticket created; status=Pending; issue\_photo\_path=null |  |  |
| **WBT\_MN T\_003** | MaintenanceCont roller (Tenant)::store | Branch Coverage | room\_id not provided (nullable) | issue\_desc='Common area light broken', priority='Medium', room\_id=null | Ticket created with room\_id=null |  |  |
| **WBT\_MN T\_004** | MaintenanceCont roller (Tenant)::resolve | Branch Coverage | Ticket belongs to tenant; status=In Progress | POST /maintenance/{ticket}/resolve | status=Resolved; resolved\_at=now() |  |  |
| **WBT\_MN T\_005** | MaintenanceCont roller (Tenant)::resolve | Branch Coverage | Ticket belongs to tenant; status=Pending (not In Progress) | POST /maintenance/{ticket}/resolve | Error: "Only tickets that are in progress can be resolved." |  |  |
| **WBT\_MN T\_006** | MaintenanceCont roller (Tenant)::resolve | Branch Coverage | Ticket belongs to another tenant | POST /maintenance/{ticket}/resolve (auth user \!= reporter) | HTTP 403 Forbidden |  |  |
| **WBT\_MN T\_007** | AdminMaintenan ceController::upd ate | Branch Coverage | Ticket status=Pending; contractor\_notes provided (non-empty) | priority='Medium', contractor\_notes='Scheduled repair' | status=In Progress; contractor\_notes saved |  |  |
| **WBT\_MN T\_008** | AdminMaintenan ceController::upd ate | Branch Coverage | Ticket status=Pending; contractor\_notes is empty string | priority='Low', contractor\_notes='' | status remains Pending; notes saved as null |  |  |
| **WBT\_MN T\_009** | AdminMaintenan ceController::upd ate | Branch Coverage | Ticket status=Resolved; contractor\_notes provided | priority='High', contractor\_notes='Follow-up inspection done' | status remains Resolved; notes updated |  |  |
| **WBT\_MN T\_010** | AdminMaintenan ceController::inde x (recurring) | Branch Coverage | Room R01 has 3 tickets in last 7 days | GET /admin/maintenance (default filter) | recurringByRoom includes R01 with total\_tickets=3 |  |  |
| **WBT\_MN T\_011** | AdminMaintenan ceController::inde x (recurring) | Branch Coverage | Room R02 has only 1 ticket in last 7 days | GET /admin/maintenance | recurringByRoom does NOT include R02 (HAVING COUNT \>= 2\) |  |  |

| TC ID  | Feature/Module  | Technique  | Preconditions  | Test Input  | Expected Output  | Actual Output  | Status  |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_SE C\_001** | SecurityControlle r::storeVisitor | Branch Coverage | Valid inputs; visitor\_photo provided | tenant\_visited=1, visitor\_name='John', purpose='Visit', visitor\_photo=photo.jpg | VisitorLog created; time\_in=now(); visitor\_photo\_path stored |  |  |
| **WBT\_SE C\_002** | SecurityControlle r::storeVisitor | Branch Coverage | Valid inputs; no visitor\_photo | tenant\_visited=1, visitor\_name='Jane', purpose='Delivery' (no photo) | VisitorLog created; visitor\_photo\_path=null |  |  |
| **WBT\_SE C\_003** | SecurityControlle r::storeVisitor | Branch Coverage | Purpose not provided (nullable) | tenant\_visited=1, visitor\_name='Mike' (no purpose) | VisitorLog created; purpose=null |  |  |
| **WBT\_SE C\_004** | SecurityControlle r::checkOutVisito r | Branch Coverage | Visitor time\_out is null | POST /admin/security/visitors/{log} /checkout | time\_out=now(); Display: "Visitor checked out." |  |  |
| **WBT\_SE C\_005** | SecurityControlle r::checkOutVisito r | Branch Coverage | Visitor already checked out (time\_out \!= null) | POST /admin/security/visitors/{log} /checkout | Error: "Visitor is already checked out." |  |  |
| **WBT\_SE C\_006** | TenantVisitorCon troller::checkout | Branch Coverage | Visitor belongs to auth tenant; time\_out is null | POST /visitors/{log}/checkout | time\_out=now(); Display: "Visitor checked out successfully." |  |  |
| **WBT\_SE C\_007** | TenantVisitorCon troller::checkout | Branch Coverage | Visitor belongs to different tenant | POST /visitors/{log}/checkout (auth tenant \!= log.tenant\_visited) | Error: "You cannot check out this visitor." |  |  |
| **WBT\_SE C\_008** | TenantVisitorCon troller::checkout | Branch Coverage | Visitor already checked out | POST /visitors/{log}/checkout (time\_out \!= null) | Error: "Visitor is already checked out." |  |  |
| **WBT\_SE C\_009** | SecurityControlle r::storeIncident | Branch Coverage | Valid inputs | title='Unauthorized entry', description='...', severity='High' | SecurityIncident created; status=Open; reported\_by=auth user id |  |  |
| **WBT\_SE C\_010** | SecurityControlle r::updateIncident | Branch Coverage | status changed to Resolved | status='Resolved' | status=Resolved; resolved\_at=now() |  |  |
| **WBT\_SE C\_011** | SecurityControlle r::updateIncident | Branch Coverage | status changed to Investigating | status='Investigating' | status=Investigating; resolved\_at=null |  |  |
| **WBT\_SE C\_012** | SecurityControlle r::updateIncident | Branch Coverage | status changed from Resolved back to Open | status='Open' | status=Open; resolved\_at=null |  |  |
| **WBT\_SE C\_013** | SecurityControlle r::addToBlacklist | Branch Coverage | Email not yet in blacklist | [email='bad@actor.com'](mailto:email%3D%27bad@actor.com), reason='Trespassing' | Blacklist entry created; banned\_at=now() |  |  |
| **WBT\_SE C\_014** | SecurityControlle r::addToBlacklist | Branch Coverage | Email already in blacklist (unique constraint) | [email='bad@actor.com'](mailto:email%3D%27bad@actor.com) (already blacklisted) | Validation error: "The email has already been taken." |  |  |

| TC ID  | Feature/Module  | Technique  | Preconditions  | Test Input  | Expected Output  | Actual Output  | Status  |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| **WBT\_SE C\_015** | SecurityControlle r::removeFromBl acklist | Branch Coverage | Entry exists in blacklist | DELETE /admin/security/blacklist/{id} | Entry deleted; Display: "Removed from blacklist." |  |  |

