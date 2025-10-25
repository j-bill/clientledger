# Freelance Helper - Project Showcase

A modern, full-stack time tracking and invoicing application built with Laravel 11 and Vue 3.

---

## 🎯 Overview

**Freelance Helper** is a comprehensive web application designed to help freelancers and small agencies manage their time, projects, and invoicing in one place. Built as a demonstration of modern web development practices, this open-source project showcases:

- Full-stack development with Laravel & Vue.js
- Modern UI/UX with Vuetify and glassmorphism design
- Enterprise-grade security features
- Real-time data visualization
- RESTful API architecture

---

## ⚡ Key Features

### 🕐 Time Tracking
- Real-time timer with start/stop functionality
- Manual time entry with automatic calculations
- Billable vs non-billable hour tracking
- Project and customer association for every work log

### 📊 Project Management
- Multi-project tracking with deadlines
- Customer relationship management
- Flexible hourly rate configuration (per project or per user)
- Deadline alerts and project status monitoring

### 💰 Invoicing
- One-click invoice generation from work logs
- Multi-project invoice consolidation
- Invoice status tracking (Paid, Pending, Overdue)
- Professional invoice numbering system

### 📈 Business Analytics
- Interactive dashboard with KPIs and charts
- Revenue/earnings trends visualization
- Customer revenue distribution analysis
- Billable vs non-billable hours breakdown
- Monthly and yearly comparisons with extrapolation

### 👥 Team Collaboration
- Multi-user support with role-based access (Admin/Freelancer)
- Project assignment and team management
- Individual hourly rates per team member
- Scoped data views based on user role

### 🔒 Security
- Two-Factor Authentication (2FA) with QR codes
- Email verification with 6-digit codes
- Trusted device management
- Laravel Sanctum API authentication
- Role-based access control (RBAC)

### ⚙️ Customization
- Company branding (logo upload)
- Currency and date format preferences
- Number format preferences (US, European, Indian, French styles)
- Timezone configuration
- Legal content management (Privacy Policy, Imprint)

---

## 🛠️ Technology Stack

### Backend
- **Framework:** Laravel 11 (PHP 8.2+)
- **Authentication:** Laravel Sanctum
- **2FA:** pragmarx/google2fa-laravel
- **Database:** MySQL/PostgreSQL/SQLite support

### Frontend
- **Framework:** Vue 3 (Composition API)
- **UI Library:** Vuetify 3
- **State Management:** Pinia
- **Routing:** Vue Router 4
- **Charts:** Vue Google Charts
- **Build Tool:** Vite

### Development
- **Testing:** Playwright (E2E), PHPUnit
- **Linting:** Laravel Pint, ESLint
- **Development:** Laravel Sail (Docker), Hot Module Replacement

---

## 📸 Suggested Screenshots

To showcase this application on your website, consider capturing these key views:

### Essential Screenshots (5-7 images)

1. **Dashboard Overview**
   - Shows KPI cards (revenue, hours, earnings)
   - Revenue trend line chart
   - Revenue by customer pie chart
   - Hours worked visualization
   - Upcoming deadlines widget

2. **Work Logs Management**
   - List view showing time entries with project/customer info
   - Active timer interface
   - Billable/non-billable indicators

3. **Project Management**
   - Projects list with deadlines and rates
   - Color-coded deadline urgency indicators

4. **Invoice Generation**
   - Unbilled work logs selection
   - Invoice creation with automatic totals

5. **Two-Factor Authentication**
   - 2FA setup screen with QR code
   - Security-focused UI design

6. **Mobile Responsive View** (Optional)
   - Dashboard on mobile device
   - Shows responsive design

7. **Login Screen** (Optional)
   - Clean authentication interface
   - Company branding display

### Screenshot Tips

- **Resolution:** 1920x1080 for desktop, device frames for mobile
- **Theme:** Use the dark glassmorphism theme (as designed)
- **Data:** Populate with realistic sample data (multiple customers, projects, and work logs)
- **Highlight:** Focus on the modern UI and data visualization
- **Context:** Show real-world usage scenarios

### Sample Data Suggestions

**Customers:** 4-6 sample clients (e.g., "Acme Corp", "Tech Solutions Inc", "Creative Agency")

**Projects:** 6-8 projects with varying deadlines and hourly rates

**Work Logs:** 15-20 entries spanning 2-3 months, mix of billable/non-billable

**Invoices:** 4-6 invoices with different statuses (Paid, Pending)

---

## 💡 Use Cases

### Solo Freelancer
Track time across multiple client projects, manage deadlines, and generate professional invoices automatically.

### Small Agency
Assign team members to projects, set individual rates, track team performance, and manage consolidated billing.

### Consultant
Distinguish between billable client work and non-billable administrative tasks, analyze time allocation, and maintain client records.

---

## 🎨 Design Highlights

- **Modern Glassmorphism:** Semi-transparent cards with backdrop blur effects
- **Dark Theme:** Professional dark color scheme with accent colors
- **Responsive:** Mobile-first design that works on all devices
- **Visual Feedback:** Smooth animations and transitions
- **Data Visualization:** Interactive charts using Google Charts library
- **Intuitive UX:** Clean layouts with logical information hierarchy

---

## 🚀 Technical Highlights

### Architecture
- **SPA (Single Page Application):** Fast, app-like experience with Vue Router
- **RESTful API:** Clean separation between backend and frontend
- **Middleware-Based Security:** Role checks, 2FA verification, email verification
- **Real-time Updates:** Live timer tracking and dynamic data updates

### Security Features
- Mandatory two-factor authentication for all users
- Email verification with periodic re-verification
- Trusted device management with "Remember me" option
- Secure password hashing with bcrypt
- API token-based authentication
- CSRF protection

### Code Quality
- **MVC Architecture:** Clean separation of concerns
- **Database Migrations:** Version-controlled schema changes
- **Validation:** Comprehensive server-side validation
- **Error Handling:** Graceful error handling and logging
- **Testing:** E2E tests with Playwright

---

## 📋 Feature Matrix

| Feature | Description | Role Access |
|---------|-------------|-------------|
| Time Tracking | Start/stop timer, manual entry | All Users |
| Work Log Management | View, create, edit, delete logs | All Users (own data) |
| Project Viewing | View assigned projects | All Users |
| Project Management | Create, edit, delete projects | Admin Only |
| Customer Management | Full CRUD operations | Admin Only |
| Invoice Generation | Create from work logs | Admin Only |
| Invoice Management | Track payment status | Admin Only |
| User Management | Add/remove team members | Admin Only |
| Dashboard Analytics | View KPIs and charts | All Users (scoped) |
| Settings | Configure application | Admin Only |
| Profile Management | Update profile, enable 2FA | All Users |

---

## 🌟 Development Showcase

This project demonstrates proficiency in:

- ✅ Full-stack web application development
- ✅ Modern PHP with Laravel framework
- ✅ Vue.js 3 with Composition API
- ✅ RESTful API design and implementation
- ✅ Database design and relationships
- ✅ Authentication and authorization systems
- ✅ Real-time data visualization
- ✅ Responsive UI/UX design
- ✅ Security best practices (2FA, RBAC, email verification)
- ✅ State management with Pinia
- ✅ Modern build tools (Vite)
- ✅ End-to-end testing
- ✅ Version control with Git

---

## 📦 Quick Stats

- **Backend:** 9 models, 13 controllers, 138+ API endpoints
- **Frontend:** 15+ Vue pages/components, complete SPA experience
- **Database:** 19+ migrations with relationships
- **Security:** 2FA, email verification, RBAC, trusted devices
- **Features:** Time tracking, projects, customers, invoices, team management, analytics
- **Charts:** 4+ interactive visualizations (line, pie, column charts)
- **Responsive:** Works on desktop, tablet, and mobile devices

---

## 🎓 Learning & Innovation

Key learning aspects demonstrated in this project:

1. **Modern PHP:** Leveraging Laravel 11's latest features
2. **Vue 3 Composition API:** Using the modern Vue.js paradigm
3. **Security First:** Implementing enterprise-grade authentication
4. **User Experience:** Creating an intuitive interface for complex workflows
5. **Data Visualization:** Making business data accessible and actionable
6. **Scalability:** Supporting both solo users and teams
7. **API Design:** Building a clean, RESTful API structure

---

## 📄 License

Open Source - Released under MIT License

---

## 🔗 Links

- **Repository:** [github.com/j-bill/freelance-helper](https://github.com/j-bill/freelance-helper)
- **Technology:** Laravel 11, Vue 3, Vuetify 3, Pinia
- **Type:** Full-Stack Web Application
- **Status:** Active Development

---

*This project showcases modern web development practices and full-stack engineering skills, built as an open-source portfolio piece demonstrating real-world application architecture.*
