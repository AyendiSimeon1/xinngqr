# QR Link Manager / Linktree Clone App

## 1. App Overview

The app is a simple Linktree-style platform that allows users to create a personal or business landing page, create short memorable links, and generate trackable QR codes.

The main idea is simple:

Pages, links, and QR codes are separate product objects.

Pages are page-builder surfaces.  
Links are standalone short links for teams, campaigns, and marketing.  
QR codes are standalone trackable assets.  
Links and QR codes can be added to pages, and new links or QR codes can be created while editing a page.

This makes the app useful for events, businesses, personal brands, sales teams, creators, restaurants, consultants, churches, schools, real estate agents, and anyone who needs one shareable link or QR code that can be updated later.

---

## 2. What the App Does

The app allows users to:

1. Create an account.
2. Log in securely.
3. Set up a public page with a unique username or slug.
4. Create short memorable links.
5. Add existing links to a page or create new links from the page builder.
6. Edit, activate, deactivate, reorder, or delete page link placements.
7. Generate standalone QR codes.
8. Add existing QR codes to a page or create new QR codes from the page builder.
9. Share short links and QR codes physically or digitally.
10. Update the page anytime without changing shared links or QR codes.
11. Track page visits, QR scans, and link clicks.
12. Customize the appearance of the page.
13. Optionally upgrade to paid plans for more features.

---

## 3. Core User Flow

```text
User signs up
↓
User logs in
↓
User chooses username/page slug
↓
System creates public page
↓
User adds profile details
↓
User adds links
↓
System generates live QR code
↓
User shares QR code or page URL
↓
Visitor scans QR code
↓
Visitor lands on user page
↓
Visitor clicks a link
↓
App tracks scan, visit, and click data
```

---

## 4. Main App Sections

### 4.1 Landing Page

The landing page explains the app and encourages users to sign up.

Possible sections:

- Hero section
- How it works
- Benefits
- Use cases
- Pricing
- Login button
- Register button

### 4.2 Registration Page

Allows a new user to create an account.

Required fields:

- Name
- Email
- Password
- Confirm password

Optional fields:

- Phone number
- Business name
- Page slug

### 4.3 Login Page

Allows existing users to log in.

Required fields:

- Email
- Password

### 4.4 User Dashboard

The dashboard is the control center for each user.

The dashboard should show:

- Page URL
- QR code preview
- Total page views
- Total QR scans
- Total link clicks
- Link manager
- Profile/page settings
- Theme settings
- Account settings

### 4.5 Page Setup

Users can customize their public page.

Editable fields:

- Page title
- Bio
- Profile image
- Cover image
- Page slug
- SEO title
- SEO description
- Publish/unpublish status

### 4.6 Link Manager

Users can add and manage links.

Each link should include:

- Link title
- Link URL
- Link type
- Description
- Position/order
- Active/inactive status
- Start date
- End date

Supported link types:

- Normal URL
- WhatsApp
- Email
- Phone
- Payment link
- Social media
- File
- Video
- Form
- Booking link

### 4.7 Public Page

This is the Linktree-style page visitors see.

It should display:

- Profile image
- Page title
- Bio
- Social icons
- Active links
- Branding or “powered by” text, depending on user plan

Example URL:

```text
https://yourdomain.com/u/micony
```

### 4.8 QR Code System

Each public page should have a QR code.

The QR code should point to a trackable QR route, not directly to the public page.

Recommended structure:

```text
/qr/{slug}
```

Example:

```text
https://yourdomain.com/qr/micony
```

When scanned:

```text
/qr/micony
↓
Record QR scan
↓
Redirect visitor to /u/micony
```

This allows the app to track QR scans separately from normal page views.

### 4.9 Analytics

The analytics system should track:

- Page views
- Link clicks
- QR scans
- Referrers
- Devices
- Browsers
- Countries
- Cities
- Time of activity

MVP analytics can be simple:

- Total page views
- Total QR scans
- Total link clicks
- Clicks per link

Advanced analytics can come later.

### 4.10 Themes and Customization

Users should be able to customize the look of their page.

Basic customization:

- Background color
- Button color
- Text color
- Font style
- Button style
- Profile image

Advanced customization:

- Background image
- Custom CSS
- Premium themes
- Logo on QR code
- Remove branding

### 4.11 Subscription and Monetization

The app can support free and paid plans.

#### Free Plan

- 1 page
- Limited links
- Basic QR code
- Basic analytics
- Platform branding visible

#### Pro Plan

- More links
- Advanced analytics
- Custom themes
- QR customization
- Remove branding

#### Business Plan

- Multiple pages
- Team members
- Custom domain
- Advanced analytics
- Priority support

---

## 5. Core Features

### MVP Features

The first version should include:

- User registration
- User login
- User logout
- User dashboard
- Create/edit public page
- Add/edit/delete links
- Reorder links
- Activate/deactivate links
- Public page display
- Live QR code generation
- QR redirect tracking
- Page view tracking
- Link click tracking
- Basic theme settings

### Version 2 Features

After the MVP, add:

- Image uploads
- Social media icons
- Link scheduling
- Link thumbnails
- QR code download
- QR color customization
- Basic subscription plans
- Paystack or Flutterwave payment
- Email verification
- Password reset

### Version 3 Features

Advanced features:

- Custom domains
- Team accounts
- Multiple pages per user
- Advanced analytics dashboard
- A/B testing
- Lead capture forms
- File downloads
- Booking integrations
- Webhooks
- API access
- Agency dashboard

---

## 6. Recommended URL Structure

```text
/                       Landing page
/register               User signup
/login                  User login
/logout                 User logout
/dashboard              User dashboard
/dashboard/page         Edit public page
/dashboard/links        Manage links
/dashboard/theme        Manage page theme
/dashboard/qr           View/download QR code
/dashboard/analytics    View analytics
/u/{slug}               Public user page
/qr/{slug}              QR tracking redirect
/l/{link_id}            Link click tracking redirect
```

---

## 7. Recommended Tech Stack

### Frontend

- HTML5
- CSS3
- JavaScript
- Bootstrap or Tailwind CSS

### Backend

- PHP 8+
- MySQL or MariaDB

### Storage

- MySQL database for user data, pages, links, analytics, and subscriptions
- File storage for images and uploads

### Hosting

- cPanel shared hosting
- VPS
- Cloud hosting

### Payment Integration

For Nigeria and Africa-focused use:

- Paystack
- Flutterwave

For international use:

- Stripe

---

## 8. Database Schema

The schema below is designed for a full Linktree-style clone.

## 8.1 `users`

Stores account owners.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url TEXT NULL,
    phone VARCHAR(50) NULL,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 8.2 `pages`

Each user can create one or more public pages.

```sql
CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    bio TEXT NULL,
    profile_image_url TEXT NULL,
    cover_image_url TEXT NULL,
    is_published BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    page_type ENUM('personal', 'business', 'creator', 'event') DEFAULT 'personal',
    seo_title VARCHAR(180) NULL,
    seo_description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 8.3 `links`

Stores standalone short links.

MVP note: the first implementation may keep `page_id` directly on `links` for speed. The intended product model is that a link belongs to a user/team and can be placed on one or more pages through a future page/link placement table.

```sql
CREATE TABLE links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    url TEXT NOT NULL,
    description TEXT NULL,
    icon VARCHAR(100) NULL,
    link_type ENUM('url','whatsapp','email','phone','payment','social','file','video','form','booking') DEFAULT 'url',
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    open_in_new_tab BOOLEAN DEFAULT TRUE,
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    click_count BIGINT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

## 8.4 `social_links`

Optional structured social media links.

```sql
CREATE TABLE social_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    platform ENUM('instagram','linkedin','x','facebook','youtube','tiktok','snapchat','telegram','github','website') NOT NULL,
    url TEXT NOT NULL,
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

## 8.5 `themes`

Stores reusable themes.

```sql
CREATE TABLE themes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    background_type ENUM('solid', 'gradient', 'image', 'video') DEFAULT 'solid',
    background_value TEXT NULL,
    primary_color VARCHAR(20) DEFAULT '#111111',
    secondary_color VARCHAR(20) DEFAULT '#ffffff',
    accent_color VARCHAR(20) DEFAULT '#2563eb',
    font_family VARCHAR(120) DEFAULT 'Inter',
    button_style ENUM('rounded', 'pill', 'square', 'glass', 'outline') DEFAULT 'rounded',
    button_background VARCHAR(20) DEFAULT '#111111',
    button_text_color VARCHAR(20) DEFAULT '#ffffff',
    is_premium BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 8.6 `page_theme_settings`

Stores the selected/custom theme for each page.

```sql
CREATE TABLE page_theme_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    theme_id BIGINT UNSIGNED NULL,
    custom_background TEXT NULL,
    custom_primary_color VARCHAR(20) NULL,
    custom_secondary_color VARCHAR(20) NULL,
    custom_accent_color VARCHAR(20) NULL,
    custom_font_family VARCHAR(120) NULL,
    custom_button_style VARCHAR(50) NULL,
    custom_css TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE SET NULL
);
```

## 8.7 `qr_codes`

Stores standalone QR code assets.

MVP note: the first implementation may keep `page_id` directly on `qr_codes` for speed. The intended product model is that a QR code belongs to a user/team, has its own destination, and can be placed on one or more pages through a future page/QR placement table.

```sql
CREATE TABLE qr_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) DEFAULT 'Main QR Code',
    destination_url TEXT NOT NULL,
    qr_image_url TEXT NULL,
    foreground_color VARCHAR(20) DEFAULT '#000000',
    background_color VARCHAR(20) DEFAULT '#ffffff',
    logo_url TEXT NULL,
    scan_count BIGINT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

## 8.8 `page_views`

Tracks page visits.

```sql
CREATE TABLE page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    visitor_id VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    referrer TEXT NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
    browser VARCHAR(100) NULL,
    os VARCHAR(100) NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

## 8.9 `link_clicks`

Tracks link clicks.

```sql
CREATE TABLE link_clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    link_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,
    visitor_id VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    referrer TEXT NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

## 8.10 `qr_scans`

Tracks QR scan activity.

```sql
CREATE TABLE qr_scans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qr_code_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,
    visitor_id VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

## 8.11 `plans`

Stores pricing plans.

```sql
CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    price_monthly DECIMAL(12,2) DEFAULT 0,
    price_yearly DECIMAL(12,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'NGN',
    max_pages INT DEFAULT 1,
    max_links_per_page INT DEFAULT 10,
    custom_domain_enabled BOOLEAN DEFAULT FALSE,
    analytics_enabled BOOLEAN DEFAULT FALSE,
    remove_branding BOOLEAN DEFAULT FALSE,
    qr_customization_enabled BOOLEAN DEFAULT FALSE,
    team_enabled BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 8.12 `subscriptions`

Tracks user subscriptions.

```sql
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'trialing', 'past_due', 'cancelled', 'expired') DEFAULT 'active',
    payment_provider ENUM('paystack', 'flutterwave', 'stripe', 'manual') DEFAULT 'paystack',
    provider_customer_id VARCHAR(190) NULL,
    provider_subscription_id VARCHAR(190) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    renews_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
);
```

## 8.13 `payments`

Tracks payments.

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'NGN',
    payment_provider ENUM('paystack', 'flutterwave', 'stripe', 'manual') DEFAULT 'paystack',
    provider_reference VARCHAR(190) UNIQUE,
    status ENUM('pending', 'successful', 'failed', 'refunded') DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
);
```

## 8.14 `custom_domains`

Allows users to connect their own domain.

```sql
CREATE TABLE custom_domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(190) NOT NULL UNIQUE,
    verification_token VARCHAR(190) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    ssl_status ENUM('pending', 'active', 'failed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

## 8.15 `team_members`

For agencies, companies, and multi-user accounts.

```sql
CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    member_user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (member_user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 8.16 `media_files`

Stores uploaded images, logos, PDFs, and downloadable files.

```sql
CREATE TABLE media_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_url TEXT NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED DEFAULT 0,
    media_type ENUM('image', 'video', 'document', 'audio', 'other') DEFAULT 'other',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 9. Simplified MVP Schema

For a quick first build, start with only these tables:

```text
users
pages
links
qr_codes
page_views
link_clicks
qr_scans
```

This gives the app enough structure for:

- User registration
- Login
- Dashboard
- Public page creation
- Link management
- QR code generation
- QR scan tracking
- Page view tracking
- Link click tracking

---

## 10. Recommended MVP Build Order

### Phase 1: Authentication

Build:

- Register
- Login
- Logout
- Password hashing
- Session management

### Phase 2: Page Setup

Build:

- Create page
- Edit page
- Generate public URL
- Display public page

### Phase 3: Short Link Manager

Build:

- Create short link
- Edit short link
- Delete short link
- Place links on a page
- Reorder page link placements
- Activate/deactivate page link placements

### Phase 4: QR Code

Build:

- Generate QR code
- Display QR code in dashboard
- QR tracking redirect route
- QR scan recording

### Phase 5: Analytics

Build:

- Record page views
- Record link clicks
- Record QR scans
- Show totals in dashboard

### Phase 6: Themes

Build:

- Basic colors
- Button styles
- Font selection
- Theme save/update

### Phase 7: Monetization

Build:

- Plans
- Payments
- Subscriptions
- Feature restrictions

---

## 11. MVP Acceptance Criteria

The MVP is ready when:

- A user can register.
- A user can log in.
- A user can create a public page.
- A user can add at least one link.
- The public page displays the active links.
- A QR code is generated for the page.
- Scanning the QR code opens the correct page.
- The user can update links without changing the QR code.
- Page visits are tracked.
- Link clicks are tracked.
- QR scans are tracked.
- The user can see basic analytics in the dashboard.

---

## 12. Product Positioning

This app can be positioned as:

```text
One live QR code for every link your business needs.
```

Alternative positioning:

```text
Create a smart QR-powered link page you can update anytime.
```

For events:

```text
Print one QR code. Update the links anytime.
```

For small businesses:

```text
Your business card, brochure, WhatsApp, catalog, and payment links in one QR code.
```

---

## 13. Best Initial Target Users

Good first users include:

- Small businesses
- Event vendors
- Churches
- Consultants
- Restaurants
- Real estate agents
- Sales teams
- Schools
- Creators
- Freelancers
- Oil and gas service companies attending events
- Professionals who need a smart digital profile

---

## 14. Key Technical Notes

1. Pages, links, and QR codes are separate product objects.
2. Pages are page-builder surfaces.
3. Links are standalone short links for short, memorable, trackable URLs.
4. QR codes are standalone trackable assets.
5. Links and QR codes can be added to pages, and can also be created while editing a page.
6. QR codes should not point directly to the final destination.
7. QR codes should point to a tracking route like `/qr/{slug}` or a future QR asset route.
8. The QR route should record the scan, then redirect to the selected destination.
9. Link clicks should also use a tracking route like `/l/{link_id}`.
10. Passwords must be hashed using PHP `password_hash()`.
11. Sessions should be protected using secure cookies.
12. Public page slugs must be unique.
13. User-uploaded files should be validated before saving.
14. Admin-level controls should be added later for moderation.
15. Analytics should avoid storing unnecessary sensitive data.

---

## 15. Future Product Ideas

Build later, after the core page builder, short link, QR, analytics, and billing foundations are stable.

- Shop
- Design
- Earn
- Audience
- Insights
- Tools
- ID
- Business cards
- Calendar
- Social planner
- Msg
- Instagram auto-reply
- URL link shortener
- AI post ideas

---

## 16. Admin Panel Features

An admin panel can be added later.

Admin should be able to:

- View all users
- Suspend users
- Delete abusive pages
- View total pages
- View total QR scans
- View total link clicks
- Manage subscription plans
- Manage themes
- Manage reported pages
- View revenue
- Manually upgrade users

---

## 17. Summary

The app is a QR-powered Linktree clone.

The core value is that users can print or share one permanent QR code, while changing the destination links anytime from their dashboard.

The MVP should focus on:

- Accounts
- Public pages
- Link management
- QR code generation
- QR scan tracking
- Link click tracking
- Simple dashboard

The full product can later expand into payments, teams, custom domains, analytics, and branded QR campaigns.
