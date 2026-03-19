# Introduction 


Laravel Project using Inertia.JS for interfacing with React Frontend. In accordance and to be submitted to CS17 for approval and testing. Testing will be done in PHP Unit with supervision from advisor. 


# Architecture Summary

## Models & Migrations:
User, TenantProfile, Room, LeaseContract, Bill, Payment, MaintenanceTicket, VisitorLog, Blacklist.
Migrations define foreign key constraints (e.g., TenantProfile belongs to User, Payment belongs to Bill).


## Controllers:
Divided into Admin and Tenant namespaces.
Admin: AdminDashboardController, RoomManagementController, TenantController, AdminBillingController.
Shared/Tenant: RoomController, BillingController, ProfileController.
Controllers handle CRUD operations, utilizing Laravel's Request classes for validation before interacting with Models.


## Routing (routes/web.php):
Routes are grouped by middleware (auth, admin).
Inertia handles the view rendering directly from the web routes (e.g., Inertia::render('admin/dashboard')).


## Authentication & Security:
Implemented using Laravel Fortify.
Includes standard login/registration, password resets, and Two-Factor Authentication (2FA) for admin security.

# Subsystems

Revised Module Breakdown per Subsystem
Based on the finalized database schema and features, the 5 systems are broken down into the following distinct implementation modules:

## Subsystem 1: Room Management System (Core)
Room Inventory Module (Room Model): Manages the master list of rooms, including attributes like capacity, base price, and current availability status.
Room Request Module (RoomRequest Model): Allows prospective or current tenants to submit requests for specific rooms or room transfers.
Allocation Module: Admin logic to assign a tenant to a room, which automatically decrements the available capacity and updates room status.

## Subsystem 2: Tenant Management System (Core)
Authentication & Access Module (User Model): Handles registration, login, two-factor authentication (via Fortify), and role determination (Admin vs. Tenant).
Tenant Profiling Module (TenantProfile Model): Stores comprehensive personal details, emergency contacts, and background information for approved boarders.
Leasing Module (LeaseContract Model): Generates and tracks active lease agreements, binding a TenantProfile to a specific Room for a set duration.


## Subsystem 3: Billing and Payment System (Core)
Invoice Generation Module (Bill Model): Automates the creation of monthly charges for rent and tracks associated due dates and penalty statuses.
Payment Processing Module (Payment Model): Logs individual transactions against specific bills, capturing the payment method, amount, and date.
Ledger/Statement Module: Aggregates a tenant's historical bills and payments to display their current outstanding balance.


## Subsystem 4: Maintenance Request System (Simple)
Ticketing Module (MaintenanceTicket Model): Provides an interface for tenants to submit repair requests (e.g., plumbing, electrical) specific to their room.
Status Tracking Module: Allows the admin to update the lifecycle of a ticket (Open, In Progress, Resolved) and keep the tenant informed of the timeline.


## Subsystem 5: Visitor & Security Log System (Simple)
Logbook Module (VisitorLog Model): A digital ledger for recording the details of non-tenants entering the premises, including the person they are visiting, time-in, and time-out.
Security/Banning Module (Blacklist Model): A database of individuals strictly prohibited from entering the boarding house, checked against whenever a new visitor log is created.


