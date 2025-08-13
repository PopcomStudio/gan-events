# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Symfony 5.4 event management application called "gan.events" that handles event creation, guest management, and email communications. The application supports multiple event types (standard events, golf cup, cinema projections, workshops, collections) and provides a comprehensive admin interface for managing events, guests, and communications.

## Development Commands

### Setup and Installation
```bash
composer install
yarn install
yarn build
php bin/console doctrine:migrations:migrate
```

### Asset Building
```bash
yarn dev              # Development build
yarn watch            # Watch mode for development
yarn build            # Production build
yarn dev-server       # Development server with hot reload
```

### Database Operations
```bash
php bin/console doctrine:migrations:migrate
php bin/console make:migration
```

### Background Processing
```bash
php bin/console messenger:consume [-vv]
supervisorctl start messenger-consume:*
```

### Custom Commands
```bash
php bin/console app:schedules:prepare    # Prepare email schedules
php bin/console app:event:send          # Send event emails
php bin/console app:reset-password-token # Clean up password tokens
php bin/console app:test-email          # Test email functionality
```

### Testing
```bash
php bin/phpunit                         # Run all tests
```

## Architecture Overview

### Entity Relationships
The core domain revolves around **Events** that can have multiple **Guests**. Events support different types:
- Standard events (`evenement`)
- Golf Cup events (`golfcup`) 
- Cinema projections (`projection`)
- Workshop events (`ateliers`)
- Collections (`collection`)
- Standard events with moments (`standard_plus_moments`)

**Key entities:**
- `Event`: Main event entity with dates, location, guest management
- `Guest`: Event attendees with registration status and preferences
- `Sender`: Email sender configurations for events
- `User`: Admin users managing events
- `EmailTemplate` & `EmailSchedule`: Email communication system
- `TimeSlot` & `Workshop`: Workshop scheduling for workshop-type events
- `Attachment`: File uploads (images, documents)

### Controller Structure
- **Admin Controllers** (`/src/Controller/Admin/`): Backend management interface
  - `EventController`: Event CRUD operations
  - `GuestController`: Guest management and imports
  - `EmailController`: Email template and schedule management
  - `QrScanController`: QR code scanning for event check-ins
- **Front Controllers** (`/src/Controller/Front/`): Public-facing pages
  - `EventController`: Event registration and guest responses
  - `SecurityController`: Authentication
  - `SubscribeController`: Guest subscription management

### Services
- `Mailer`: Handles all email communications with templating
- `EventHelper`: Business logic for event operations
- `GenPdf`: PDF generation for invitations and tickets

### Asset Structure
Multiple Webpack entries for different functionalities:
- `app`: Main application assets
- `event`: Event-specific functionality
- `datatables`: Data table management
- `tinymce`: Rich text editor
- `qr-scanner`: QR code scanning capabilities

### Security & Permissions
Uses Symfony Voters for authorization:
- `AppVoter`: General application permissions
- `EventVoter`: Event-specific permissions
- `EmailVoter` & `GuestVoter`: Feature-specific access control

### File Uploads
Configured with VichUploader for handling:
- Event headers and logos
- Movie posters (for cinema events)
- Document attachments
- User profile images

### Background Processing
Uses Symfony Messenger for:
- Email sending (`MailNotification`)
- Email cleanup (`MailPurge`)

## Key Features
- Multi-type event management with specialized workflows
- Guest import via Excel spreadsheets
- Email template system with scheduling
- QR code generation and scanning for event check-ins
- PDF generation for invitations and tickets
- Responsive admin interface with Bootstrap 5
- Workshop scheduling system for workshop events