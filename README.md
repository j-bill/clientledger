# ClientLedger

A full-stack time tracking and invoicing application built to demonstrate modern web development practices. Designed for freelancers and small teams who need to track time, manage projects, and generate professional invoices.

Built with Laravel and Vue.js, supporting PostgreSQL, MySQL, and SQLite.

---

## Features

**Time Tracking** - Real-time timer or manual entry. Log billable and non-billable hours, assign to projects.

**Project Management** - Organize work by project and customer. Configure hourly rates per project or team member. Track deadlines.

**Expense Tracking** - Log general business expenses for tax deduction purposes. Upload receipts, categorize expenses, and export reports. Admin-only feature for business-level expense management.

**Invoicing** - Generate invoices from logged hours. Consolidate multiple projects into single invoices. Track payment status (paid, pending, overdue).

**Analytics Dashboard** - View revenue, billable hours, and earnings trends. Month-to-month and year-to-year comparisons with forecasting.

**Multi-user Support** - Admin and freelancer roles with proper data scoping. Admins see everything, team members see only their work.

**Security** - Two-factor authentication with QR codes, email verification, role-based access control, API token authentication with Laravel Sanctum.

**Customization** - Logo upload, currency formats, timezone selection, language preferences (English, German, Spanish, French, Italian).

---

## Tech Stack

**Backend:** Laravel 12 (PHP 8.2+), PostgreSQL/MySQL/SQLite, Laravel Sanctum for API authentication

**Frontend:** Vue 3 with Composition API, Vuetify 3, Vite, Vue Router, Pinia for state management

**Additional Tools:** DOMPDF for PDF generation, Playwright for E2E testing, PHPUnit for unit tests

---

## Setup

**Requirements:** PHP 8.2+, Node.js 18+, Composer, and PostgreSQL/MySQL or SQLite

**Installation:**

```bash
git clone https://github.com/j-bill/clientledger.git
cd clientledger
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`, then run:

```bash
php artisan migrate
php artisan db:seed  # optional - adds sample data
```

Start the development servers:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Visit `http://localhost:8000` to access the application.

---

## Available Commands

```bash
# Backend
php artisan serve              # Start development server
php artisan migrate            # Run database migrations
php artisan db:seed            # Seed sample data
php artisan tinker             # Interactive shell
php artisan test               # Run unit and feature tests

# Frontend
npm run dev                     # Start dev server with hot reload
npm run build                   # Production build
npm run test:e2e               # Run E2E tests with Playwright
npm run test:e2e:ui            # Run tests with interactive UI
```

---

## Project Structure

```
app/
├── Http/Controllers/      API endpoint handlers
├── Http/Middleware/       Request middleware and guards
├── Models/                Eloquent models
├── Services/              Business logic layer
└── Helpers/               Utility functions

resources/
├── js/                    Vue components and pages
└── css/                   Application styles

routes/
├── api.php                RESTful API routes
└── web.php                Web application routes

database/
├── migrations/            Database schema definitions
├── factories/             Model factories for testing
└── seeders/               Sample data seeders

tests/
├── Feature/               Integration tests
├── Unit/                  Unit tests
└── e2e/                   Playwright E2E tests
```

---

## Database Models

- **User** - Handles authentication, 2FA, email verification, and role assignment
- **Customer** - Stores client information and contact details
- **Project** - Tracks projects with deadlines and rate configuration
- **WorkLog** - Records individual time entries with billable status
- **Invoice** - Generated invoices with payment tracking
- **Expense** - Business expenses with optional customer/project linking for tax deduction tracking
- **Setting** - User and application-level configuration

---

## API Endpoints

Authentication and user management:
- `POST /api/auth/login` - User login
- `POST /api/auth/2fa/verify` - Verify two-factor authentication

Time tracking and projects:
- `GET|POST /api/work-logs` - List and create work logs
- `GET|POST /api/projects` - List and create projects
- `GET|POST /api/customers` - List and create customers

Expense tracking (admin only):
- `GET|POST /api/expenses` - List and create expenses
- `PUT /api/expenses/{id}` - Update expense
- `DELETE /api/expenses/{id}` - Delete expense
- `GET /api/expenses/export` - Export expenses as CSV

Invoicing and analytics:
- `GET|POST /api/invoices` - List and create invoices
- `GET /api/invoices/{id}/pdf` - Download invoice as PDF
- `GET /api/dashboard` - Dashboard data (revenue, hours, trends)

All endpoints are protected with Laravel Sanctum API tokens and role-based access control.

---

## User Interface

Built with Vuetify 3 and Material Design principles. Features a dark theme with responsive layouts for desktop, tablet, and mobile devices. Real-time updates on timers and data, smooth animations, and interactive charts for financial reporting.

---

## Security

- **Two-Factor Authentication** - TOTP-based 2FA with QR code setup
- **Email Verification** - Required before account activation
- **Password Security** - Bcrypt hashing with salt
- **API Authentication** - Laravel Sanctum token-based authentication
- **CSRF Protection** - Automatic CSRF token validation on all requests
- **Input Validation** - Server-side validation on all endpoints
- **Role-Based Access Control** - Admin and freelancer permission levels with proper data scoping

---

## Browser Support

Chrome, Firefox, Safari, and modern mobile browsers. Requires current or recent versions.

---

## Testing

Comprehensive test suite covering user flows and API functionality:

```bash
php artisan test               # Run unit and feature tests
npm run test:e2e              # Run E2E tests with Playwright
npm run test:e2e:ui           # Interactive test UI for debugging
```

Tests cover authentication, time tracking, invoicing, project management, permission restrictions, and data scoping.

---

## Internationalization

Supports English, German, Spanish, French, and Italian. Users can configure language preference in settings.

---

## Project Metrics

- 13+ API controllers
- 50+ RESTful endpoints
- 20+ Vue components
- 20 database migrations
- 54 passing tests
- 5 supported languages

---

## Contributing

Contributions are welcome. Please fork the repository, create a feature branch, maintain consistency with Laravel Pint code style, include tests for new features, and open a pull request with a clear description of changes.

---

## License

MIT License - see LICENSE file for details.

---

## Questions or Feedback?

Open an issue on GitHub for bugs, feature requests, or general feedback.

---

Built to showcase full-stack development practices and modern web application architecture.
