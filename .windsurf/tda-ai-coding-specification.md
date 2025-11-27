# Travel Deals Aggregator Plugin – Comprehensive AI Coding Specification  
## With UX/UI Design & Visual Architecture

---

## TABLE OF CONTENTS

1. [General Overview](#1-general-overview)
2. [Directory & File Structure](#2-directory--file-structure)
3. [Core Functional Requirements](#3-core-functional-requirements)
4. [Data Model (Database Schema)](#4-data-model-database-schema)
5. [Backend (Admin) UX/UI Specification](#5-backend-admin-uxui-specification)
6. [Frontend UX/UI Specification](#6-frontend-uxui-specification)
7. [Security, Testing, Performance](#7-security-testing-performance)
8. [Configuration](#8-configuration)
9. [Constraints](#9-constraints)
10. [Instructions for AI Agent](#10-instructions-for-ai-agent)

---

## 1. GENERAL OVERVIEW

- **Project Name:** Travel Deals Aggregator
- **Platform:** WordPress (Self-hosted, minimum v5.9)
- **Programming Language:** PHP (>=7.4), JavaScript (ES6+ for frontend blocks)
- **Architecture:** Modular, PSR-4 autoloaded, extensible, scalable, performance-optimized
- **Deployment:** Use Composer for autoloading, npm/yarn for frontend assets
- **Target User:** Travel bloggers, deal aggregators, tourism marketers

---

## 2. DIRECTORY & FILE STRUCTURE

Ensure all files and folders match this structure exactly:

```plaintext
travel-deals-aggregator/
├── travel-deals-aggregator.php
├── composer.json
├── vendor/
├── README.md
├── LICENSE
├── .gitignore
├── .env.example
├── src/
│   ├── Core/
│   │   ├── Loader.php
│   │   ├── Config.php
│   │   ├── Database.php
│   │   ├── Scheduler.php
│   │   └── Cache.php
│   ├── Integrations/
│   │   ├── DealSourceInterface.php
│   │   ├── AffiliateProgramInterface.php
│   │   ├── Sources/
│   │   └── AffiliatePrograms/
│   ├── Processors/
│   │   ├── DealDiscoveryProcessor.php
│   │   ├── EvaluationProcessor.php
│   │   ├── QueueProcessor.php
│   │   ├── ImageFinderProcessor.php
│   │   ├── ContentGeneratorProcessor.php
│   │   ├── AffiliateLinkerProcessor.php
│   │   └── PublisherProcessor.php
│   ├── Services/
│   │   ├── AIService.php
│   │   ├── ImageService.php
│   │   ├── LoggerService.php
│   │   ├── CacheService.php
│   │   ├── WebhookService.php
│   │   └── AnalyticsService.php
│   ├── Admin/
│   │   ├── AdminMenu.php
│   │   ├── SettingsPage.php
│   │   ├── DealSourceManager.php
│   │   ├── AffiliateManager.php
│   │   ├── PublishingSettings.php
│   │   ├── LogsViewer.php
│   │   └── ScheduleManager.php
│   ├── REST/
│   │   ├── DealsController.php
│   │   ├── SourcesController.php
│   │   ├── AffiliatesController.php
│   │   ├── ConfigController.php
│   │   └── StatsController.php
│   ├── Utils/
│       ├── Sanitizer.php
│       ├── Validator.php
│       ├── HttpClient.php
│       └── DateHelper.php
├── includes/
│   ├── hooks.php
│   ├── functions.php
│   └── deprecated.php
├── templates/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   ├── js/
│   │   ├── admin.js
│   │   ├── frontend.js
│   │   └── blocks.js
│   └── images/
│       └── icons/
├── blocks/
│   ├── deals-block/
│   │   ├── index.js
│   │   ├── block.json
│   │   ├── edit.js
│   │   └── save.js
│   └── deal-filter-block/
│       ├── index.js
│       ├── block.json
│       ├── edit.js
│       └── save.js
├── tests/
│   ├── Unit/
│   └── Integration/
├── phpstan.neon
```

- All code must reside in the directories referenced, using namespacing that matches folder structure.
- No code outside this structure except WordPress root requirements.

---

## 3. CORE FUNCTIONAL REQUIREMENTS

### 3.1 Deal Sources

- Support multiple travel deal sources (APIs, RSS, manual configuration)
- Each source must implement `DealSourceInterface`
- Sources have admin-managed credentials, status, sync intervals
- Admins must be able to add, edit, remove, enable, disable sources via dashboard

### 3.2 AI Deal Evaluation

- Each deal is scored with AI using these criteria:
    - Discount percentage (30%)
    - Value for money (30%)
    - Destination attractiveness (20%)
    - Timing/seasonality (20%)
    - Flexibility and rarity (15%)
- All deals in a batch are ranked by score descending
- Only the top X deals (max configurable) are published per cycle

### 3.3 Article Generation

- For every deal selected, generate:
    - SEO-optimized title (60–70 chars)
    - Meta description (155–160 chars)
    - Body content: Introduction, highlights, destination guide, booking tips, internal related links, CTA
    - Featured image (from ImageFinderProcessor)
    - Use only copyright-free images (Pexels, Pixabay, Unsplash)
    - Alt text generated for accessibility

### 3.4 Publishing Control

- Settings for minimum and maximum number of articles per batch
- Publishing modes: automatic (no review, publishes instantly) or manual (admin approval queue)
- Duplicates (by destination + date) must be avoided
- Expired deals are marked (not deleted)
- Cron job can be set hourly, daily, weekly

### 3.5 Affiliate Integration

- Link transformer must support all major affiliate networks
- Admin panel for managing different affiliate programs, credentials, link templates
- All links must use proper affiliate formats and tracking parameters

### 3.6 Admin Dashboard

- Overview: KPIs, sources, affiliate status, queue, recent logs, revenue
- Sectioned navigation (Dashboard, Sources, Affiliates, Publishing, Queue, Logs, Analytics)
- Filters, sorters, search bars as appropriate
- Responsive for tablets/desktop

### 3.7 REST API

- Use secure custom REST endpoints for:
    - Deals (get/list)
    - Sources (CRUD)
    - Affiliates (CRUD)
    - Publishing config (get/update)
    - Processing queue (get/approve/reject)
    - Analytics (stats on KPIs)
- All endpoints secured with nonce and capability checks

### 3.8 Frontend Display

- Deals block (Gutenberg): supports grid, filter, sort, responsive, theme adaptation
- Shortcode: configurable attributes
- Must be compatible with major builders (Elementor, Breakdance)
- All images lazy loaded, .webp, with alt text

---

## 4. DATA MODEL (DATABASE SCHEMA)

Every table must have the structure below, with proper indices for performance:

```sql
-- Deal Sources Configuration
CREATE TABLE wp_tda_deal_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_type VARCHAR(50) NOT NULL,
    source_name VARCHAR(255) NOT NULL,
    api_endpoint VARCHAR(500),
    credentials_encrypted LONGTEXT,
    is_active BOOLEAN DEFAULT 1,
    last_sync DATETIME,
    sync_interval_minutes INT DEFAULT 60,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY unique_source (source_type, source_name)
);

-- Affiliate Programs Configuration
CREATE TABLE wp_tda_affiliate_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('api', 'manual_url', 'cookie_based') DEFAULT 'manual_url',
    api_endpoint VARCHAR(500),
    credentials_encrypted LONGTEXT,
    url_pattern VARCHAR(1000),
    commission_rate DECIMAL(5,2),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY unique_program (program_name)
);

-- Raw Deals (before processing)
CREATE TABLE wp_tda_raw_deals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_id INT NOT NULL,
    external_id VARCHAR(255),
    deal_data LONGTEXT,
    title VARCHAR(500),
    destination VARCHAR(255),
    original_price DECIMAL(10,2),
    discounted_price DECIMAL(10,2),
    currency VARCHAR(3),
    travel_dates_start DATE,
    travel_dates_end DATE,
    raw_link VARCHAR(1000),
    evaluation_score INT,
    evaluation_reason LONGTEXT,
    is_processed BOOLEAN DEFAULT 0,
    post_id INT,
    created_at DATETIME,
    expires_at DATETIME,
    UNIQUE KEY unique_deal (source_id, external_id),
    INDEX idx_score (evaluation_score DESC),
    INDEX idx_destination (destination),
    INDEX idx_created (created_at)
);

-- Processing Queue
CREATE TABLE wp_tda_processing_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raw_deal_id INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    error_message LONGTEXT,
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    FOREIGN KEY (raw_deal_id) REFERENCES wp_tda_raw_deals(id)
);

-- Publishing Configuration
CREATE TABLE wp_tda_publishing_config (
    id INT PRIMARY KEY DEFAULT 1,
    publishing_mode ENUM('automatic', 'manual') DEFAULT 'automatic',
    min_articles_per_batch INT DEFAULT 1,
    max_articles_per_batch INT DEFAULT 10,
    batch_schedule VARCHAR(50) DEFAULT 'daily',
    auto_publish_time TIME,
    email_notifications BOOLEAN DEFAULT 1,
    updated_at DATETIME
);

-- Processing Logs
CREATE TABLE wp_tda_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_level VARCHAR(50),
    component VARCHAR(100),
    message LONGTEXT,
    context JSON,
    created_at DATETIME,
    INDEX idx_level (log_level),
    INDEX idx_component (component),
    INDEX idx_created (created_at)
);
```

---

## 5. BACKEND (ADMIN) UX/UI SPECIFICATION

### 5.1 Design System

#### Color Palette
- **Primary:** #2180B7 (Teal blue - main CTA, highlights)
- **Secondary:** #5E5240 (Brown - accents, subtle elements)
- **Success:** #208C8D (Green - positive feedback)
- **Warning:** #E06161 (Red - alerts, errors)
- **Neutral:** #F5F5F5 (Light gray - backgrounds)
- **Text:** #1F2121 (Dark gray - body text)
- **Border:** #AEAAAA (Medium gray - dividers)

#### Typography
- **Headings:** "Inter", "Segoe UI", sans-serif (600 weight, 24px for H1)
- **Body:** "Inter", "Segoe UI", sans-serif (400 weight, 14px)
- **Monospace:** "Monaco", "Courier New", monospace (code/logs)

#### Spacing
- Base unit: 8px
- Padding: 8px, 16px, 24px, 32px
- Margins: 8px, 16px, 24px, 32px
- Gap (flex): 8px, 12px, 16px

---

### 5.2 Main Dashboard Layout

```
╔════════════════════════════════════════════════════════════════════╗
║  Travel Deals Aggregator                              [Dark Mode]  ║
╠════════════════════════════════════════════════════════════════════╣
║                                                                    ║
║  SIDEBAR (240px fixed)          │  MAIN CONTENT                   ║
║  ├─ Dashboard (selected)        │  ┌──────────────────────────┐   ║
║  ├─ Sources                     │  │  DASHBOARD OVERVIEW      │   ║
║  ├─ Affiliates                  │  ├──────────────────────────┤   ║
║  ├─ Publishing                  │  │ KPI Cards (4 cols)       │   ║
║  ├─ Queue (⭐ Manual mode)       │  │ ┌─────┐ ┌─────┐ ┌─────┐│   ║
║  ├─ Logs                        │  │ │ 42  │ │$1.2K│ │ 12  ││   ║
║  ├─ Analytics                   │  │ │Deals│ │Rev. │ │Pend.││   ║
║  └─ Settings                    │  │ └─────┘ └─────┘ └─────┘│   ║
║                                │  ├──────────────────────────┤   ║
║                                │  │ Chart: Deals This Month  │   ║
║                                │  │ ▁ ▂ ▃ ▄ ▅ ▆ ▇           │   ║
║                                │  ├──────────────────────────┤   ║
║                                │  │ Recent Actions           │   ║
║                                │  │ • Published 3 deals      │   ║
║                                │  │ • Synced from Booking.com│   ║
║                                │  └──────────────────────────┘   ║
║                                                                    ║
╚════════════════════════════════════════════════════════════════════╝
```

#### 5.2.1 KPI Card Component
```
┌──────────────────────────┐
│ 🎯 Deals Processed       │  (Icon)
│ ━━━━━━━━━━━━━━━━━━━━━━   │
│ 156                      │  (Large number - accent color)
│ This month: +23 ↑        │  (Secondary text, green)
└──────────────────────────┘
```

**Variations:**
- Deals Processed (blue)
- Revenue Estimated (green)
- Pending Review (orange/yellow)
- Error Rate (red)

#### 5.2.2 Top Action Bar
```
┌──────────────────────────────────────────────────────────────┐
│  [🔄 Manual Sync]  [⚙️ Test Connection]  [📊 Analytics]      │
│                          ← Quick action buttons with icons     │
└──────────────────────────────────────────────────────────────┘
```

---

### 5.3 Publishing Settings Page

```
╔════════════════════════════════════════════════════════════════╗
║  Publishing Settings                          [Save] [Reset]   ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  PUBLISHING MODE                                              ║
║  ◉ Automatic    ○ Manual                                      ║
║  Automatically publish best deals without approval           ║
║                                                                ║
║  ─────────────────────────────────────────────────────────────║
║                                                                ║
║  ARTICLE LIMITS                                               ║
║  Minimum articles per batch:  [___6____]  deals               ║
║  ├─────────●──────────┤  (slider: 1-50)                      ║
║                                                                ║
║  Maximum articles per batch:  [___15___]  deals               ║
║  ├──────────────────●┤  (slider: min-100)                    ║
║  ℹ️  Only the top X by score will be published               ║
║                                                                ║
║  ─────────────────────────────────────────────────────────────║
║                                                                ║
║  PUBLISHING SCHEDULE                                          ║
║  Frequency: [▼ Daily            ]                             ║
║  Time: [HH:MM ▼]  (08:30)                                    ║
║  ☑ Send me notifications on publish                          ║
║                                                                ║
║  ─────────────────────────────────────────────────────────────║
║                                                                ║
║  [🧪 Test Publish]  [💾 Save Settings]  [↺ Reset to Default] ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

**Design Notes:**
- Use toggle buttons for mode selection (visual clarity)
- Sliders for numeric ranges (intuitive, visual feedback)
- Dropdown for schedule (pre-defined options)
- Contextual help icons (?) with tooltips on hover
- Visual hierarchy: sections with dividers

---

### 5.4 Deal Sources Manager

```
╔════════════════════════════════════════════════════════════════╗
║  Deal Sources                                   [+ Add Source]  ║
╠════════════════════════════════════════════════════════════════╣
║  Search: [_____________]  Filter: [All ▼]                    ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  ┌─ Skyscanner (Flights) ────────────────────────────────────┐║
║  │ ✓ Active                                                   ││
║  │ Last synced: 2 hours ago                                  ││
║  │ [🧪 Test]  [✏️ Edit]  [🗑️ Remove]                         ││
║  │ Success rate: 98%  | Last error: None                    ││
║  └─────────────────────────────────────────────────────────────┘║
║                                                                ║
║  ┌─ Booking.com (Hotels) ────────────────────────────────────┐║
║  │ ✓ Active                                                   ││
║  │ Last synced: 30 minutes ago                               ││
║  │ [🧪 Test]  [✏️ Edit]  [🗑️ Remove]                         ││
║  │ Success rate: 95%  | Last error: API rate limit          ││
║  └─────────────────────────────────────────────────────────────┘║
║                                                                ║
║  ┌─ Kayak (Multi-type) ──────────────────────────────────────┐║
║  │ ✗ Inactive                                                 ││
║  │ Last synced: 5 days ago                                   ││
║  │ [🧪 Test]  [✏️ Edit]  [🗑️ Remove]  [▶ Enable]            ││
║  │ Success rate: 92%                                         ││
║  └─────────────────────────────────────────────────────────────┘║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

#### 5.4.1 Add/Edit Source Modal

```
╔════════════════════════════════════════════════════╗
║  Add New Deal Source                      [X]      ║
╠════════════════════════════════════════════════════╣
║                                                    ║
║  Source Type:  [▼ Skyscanner                   ]  ║
║  Description: Aggregates flight deals globally    ║
║                                                    ║
║  Source Name:  [________________________]         ║
║                                                    ║
║  API Endpoint: [________________________]         ║
║                                                    ║
║  Required Credentials:                            ║
║  ┌─────────────────────────────────────────────┐  ║
║  │ API Key:       [__________________]         │  ║
║  │ API Secret:    [__________________]         │  ║
║  │ Account ID:    [__________________]         │  ║
║  └─────────────────────────────────────────────┘  ║
║                                                    ║
║  Sync Interval:  [__60__] minutes                 ║
║                                                    ║
║  ☑ Activate on save                              ║
║                                                    ║
║  [🧪 Test Connection]  [Cancel]  [Save]          ║
║                                                    ║
╚════════════════════════════════════════════════════╝
```

---

### 5.5 Processing Queue (Manual Mode)

```
╔════════════════════════════════════════════════════════════════╗
║  Approval Queue (3 pending)           [Approve All] [Reject All]║
╠════════════════════════════════════════════════════════════════╣
║  ☑ All  | Filter: [All ▼]  | Sort: [Newest ▼]                ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║ ☑  [HERO IMG]  Paris City Break              Score: 92/100   ║
║    $449 (was $899) • 4 nights • Booking.com                   ║
║    [Preview]  [✓ Approve]  [✗ Reject]                        ║
║                                                                ║
║ ☐  [HERO IMG]  Thailand Island Escape        Score: 88/100   ║
║    $599 (was $950) • 7 nights • Kayak                         ║
║    [Preview]  [✓ Approve]  [✗ Reject]                        ║
║                                                                ║
║ ☐  [HERO IMG]  Iceland Adventure             Score: 85/100   ║
║    $799 (was $1200) • 10 nights • Airbnb                      ║
║    [Preview]  [✓ Approve]  [✗ Reject]                        ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

#### 5.5.1 Deal Preview Modal

```
╔════════════════════════════════════════════════════╗
║  Preview: Paris City Break                  [X]   ║
╠════════════════════════════════════════════════════╣
║  ARTICLE PREVIEW                                  ║
║  ┌──────────────────────────────────────────────┐ ║
║  │ [Large Hero Image]                           │ ║
║  │                                              │ ║
║  │ 🎯 Discover Paris This Spring: 50% Off!     │ ║
║  │                                              │ ║
║  │ Why It's Great:                              │ ║
║  │ Get 50% off luxury 4-night stays in the...  │ ║
║  │                                              │ ║
║  │ [Read more...]                               │ ║
║  └──────────────────────────────────────────────┘ ║
║                                                  ║
║  DEAL INFO                                       ║
║  Price: $449 (was $899)  |  Score: 92/100      ║
║  Dates: Mar 15 - Dec 31  |  Duration: 4 nights ║
║  Source: booking.com                            ║
║                                                  ║
║  [Book Now] (affiliate link)  [Cancel] [Approve]║
║                                                  ║
╚════════════════════════════════════════════════════╝
```

---

### 5.6 Analytics Dashboard

```
╔════════════════════════════════════════════════════════════════╗
║  Analytics                           [Date Range ▼] [Export]   ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  PERFORMANCE METRICS                                           ║
║  Deals Published: 156  │  Success Rate: 96%  │  Errors: 6    ║
║  ─────────────────────────────────────────────────────────────║
║                                                                ║
║  REVENUE ESTIMATES (This Month)                               ║
║  ╔════════════════════════════════════════════════╗           ║
║  ║  $3,247  Total Estimated Revenue               ║           ║
║  ║  (Based on clicks and tracking links)          ║           ║
║  ║  • Booking.com: $1,850 (57%)  ▮▮▮▮▮           ║           ║
║  ║  • Skyscanner: $980 (30%)     ▮▮▮             ║           ║
║  ║  • Kayak: $417 (13%)          ▮               ║           ║
║  ╚════════════════════════════════════════════════╝           ║
║                                                                ║
║  PUBLICATION TRENDS                                           ║
║  ┌─────────────────────────────────────────────┐              ║
║  │  Deals Published (Daily)                    │              ║
║  │     ▁ ▂ ▃ ▄ ▅ ▆ ▇ █ █ ▇ ▆ ▅ ▄ ▃ ▂ ▁ ▂ ▃   │              ║
║  │  0 ├─────────────────────────────────────→  │              ║
║  │  5 │ (Chart: last 30 days)                  │              ║
║  │ 10 │                                        │              ║
║  │ 15 │                                        │              ║
║  └─────────────────────────────────────────────┘              ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

---

### 5.7 Logs Viewer

```
╔════════════════════════════════════════════════════════════════╗
║  System Logs                              [🔍] [Export] [Clear] ║
╠════════════════════════════════════════════════════════════════╣
║  Filter: [Level ▼]  [Component ▼]  [Date ▼]  [Search ____]   ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  [INFO]   2025-11-26 10:45  DealDiscoveryProcessor           ║
║           Synced 42 deals from Booking.com                    ║
║                                                                ║
║  [INFO]   2025-11-26 10:46  EvaluationProcessor              ║
║           Evaluated 42 deals. Top 10 scored 90+               ║
║                                                                ║
║  [✓ OK]   2025-11-26 10:47  PublisherProcessor               ║
║           Published 5 articles. Queue depth: 2               ║
║                                                                ║
║  [⚠️ WARN] 2025-11-26 10:50  ImageService                    ║
║           Pexels quota 85% used. 150 req. remaining.         ║
║                                                                ║
║  [✗ ERR]  2025-11-26 10:55  DealDiscoveryProcessor           ║
║           Kayak API error. Retrying... (Attempt 2/3)        ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

---

## 6. FRONTEND UX/UI SPECIFICATION

### 6.1 Frontend Design System

#### Color Palette (User-facing)
- **Primary CTA:** #2180B7 (Teal - Book Now button)
- **Discount Badge:** #E06161 (Red - "50% OFF")
- **Deal Available:** #208C8D (Green - "Available")
- **Deal Expiring:** #FF9800 (Orange - "Expires Soon")
- **Background:** #FCFCF9 (Off-white)
- **Card Shadow:** 0 2px 8px rgba(0,0,0,0.1)

#### Typography
- **Destination Title:** 28px bold, navy
- **Price:** 32px bold, teal
- **Original Price (strikethrough):** 18px, gray
- **Body Text:** 14px, charcoal

#### Spacing & Breakpoints
- Mobile: 320px - 767px
- Tablet: 768px - 1024px
- Desktop: 1025px+

---

### 6.2 Deal Cards (Grid View)

```
╔══════════════════════════════╗
║   [HERO IMAGE - 16:9]        ║  Desktop: 3 cols
║   ▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔     ║  Tablet: 2 cols
║   🏙️ Paris City Break        ║  Mobile: 1 col
║   ━━━━━━━━━━━━━━━━━━━━━    ║
║                              ║
║   ⭐ 92/100 (Best Pick)      ║  Score badge
║   $449 | was $899 (50% OFF) ║  Price highlight
║   📅 Mar 15 - Dec 31        ║  Dates
║   4 nights | Booking.com    ║  Duration, source
║   ━━━━━━━━━━━━━━━━━━━━━    ║
║   ┌──────────────────────┐   ║
║   │ [🔗 Book Now] [❤️ Save] │ ║
║   └──────────────────────┘   ║
║                              ║
╚══════════════════════════════╝
```

**Hover Effect (Desktop):**
- Card elevation increases (shadow grows)
- Slight scale up (1.02x)
- CTA button color changes to darker teal

---

### 6.3 Deal Details / Single Page

```
┌────────────────────────────────────────────────────────────┐
│                   [Back Button]                            │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  ┌─────────────────────────────────────────────────────┐  │
│  │  [LARGE HERO IMAGE - 16:9]                          │  │
│  │  ⭐ 92/100 Score Badge                              │  │
│  │  "Best Pick of the Month"                           │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                            │
│  ╔═══════════════════════════════════════════════════╗    │
│  ║  🎯 Discover Paris This Spring: 50% Off!         ║    │
│  ║  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  ║    │
│  ║  $449 • 4 nights • Booking.com                    ║    │
│  ║  Mar 15 - Dec 31, 2025                            ║    │
│  ║                                                   ║    │
│  ║  WHY IT'S A GREAT DEAL:                           ║    │
│  ║  Get 50% off luxury 4-night stays at the heart   ║    │
│  ║  of Paris. Perfect for spring break or a romantic ║    │
│  ║  getaway. Early bird pricing available!           ║    │
│  ║                                                   ║    │
│  ║  DESTINATION GUIDE:                               ║    │
│  ║  Explore iconic landmarks, cozy cafés, and world- ║    │
│  ║  class museums. Visit the Eiffel Tower, Louvre,  ║    │
│  ║  and Notre-Dame...                                ║    │
│  ║                                                   ║    │
│  ║  BOOKING TIPS:                                    ║    │
│  ║  • Book by March 1 for best rates                ║    │
│  ║  • No cancellation fees up to 3 days before       ║    │
│  ║  • Free breakfast included                        ║    │
│  ║  • Travel insurance recommended                   ║    │
│  ║                                                   ║    │
│  ║  ┌──────────────────────────────────────────┐    ║    │
│  ║  │ [🔗 Book This Deal]                       │    ║    │
│  ║  │ (Click below to claim this offer)         │    ║    │
│  ║  └──────────────────────────────────────────┘    ║    │
│  ║                                                   ║    │
│  ║  ⓘ Commission Disclosure:                         ║    │
│  ║  We earn a commission on bookings at no extra    ║    │
│  ║  cost to you. This helps us find more great deals.║    │
│  ║                                                   ║    │
│  ╚═══════════════════════════════════════════════════╝    │
│                                                            │
│  SIMILAR DEALS                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ [Carousel]   │  │ [Carousel]   │  │ [Carousel]   │     │
│  │ Greece Trip  │  │ Italy Tours  │  │ Spain Break  │     │
│  │ $599         │  │ $749         │  │ $499         │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

---

### 6.4 Main Deals Grid with Filters (Gutenberg Block)

```
┌──────────────────────────────────────────────────────────┐
│  TRAVEL DEALS THIS WEEK                                  │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  FILTER BAR (Desktop: Top, Mobile: Drawer)               │
│  ┌──────────────────────────────────────────────────┐   │
│  │ 🔍 [Search destination...  ]                     │   │
│  │                                                  │   │
│  │ [🎫 Category ▼]  [💰 Price ▼]  [📅 Dates ▼]    │   │
│  │                                                  │   │
│  │ [🔄 Latest]  [⭐ Top Rated]  [💰 Cheapest]     │   │
│  │                                                  │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  DEALS GRID (3 columns - responsive)                    │
│  ┌─────────────┬─────────────┬─────────────┐            │
│  │  Paris      │  Thailand   │  Iceland    │            │
│  │  $449       │  $599       │  $799       │            │
│  │  92/100 ⭐  │  88/100 ⭐  │  85/100 ⭐  │            │
│  └─────────────┴─────────────┴─────────────┘            │
│  ┌─────────────┬─────────────┬─────────────┐            │
│  │  Tokyo      │  Barcelona  │  Bali       │            │
│  │  $649       │  $399       │  $199       │            │
│  │  83/100 ⭐  │  82/100 ⭐  │  81/100 ⭐  │            │
│  └─────────────┴─────────────┴─────────────┘            │
│                                                          │
│  [← Previous]  Showing 6 of 156 deals  [Next →]         │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

#### 6.4.1 Mobile Filter Drawer (Slide-in)

```
┌──────────────────────────┐
│ [✕]  FILTERS            │  Side drawer on mobile
├──────────────────────────┤
│                          │
│ CATEGORY                 │
│ ☑ Flights   ☐ Hotels    │
│ ☑ Tours     ☐ Resorts   │
│                          │
│ PRICE RANGE             │
│ $0  ├─────●─────┤ $5000 │
│                          │
│ TRAVEL DATES            │
│ ┌──────────────────────┐ │
│ │ From: Mar 15 [▼]     │ │
│ │ To:   Dec 31 [▼]     │ │
│ └──────────────────────┘ │
│                          │
│ RATING                  │
│ ☑ 90+  ☑ 80+  ☐ 70+    │
│                          │
│ [Apply Filters]  [Reset] │
│                          │
└──────────────────────────┘
```

---

### 6.5 Gutenberg Block Editor UI

```
BLOCK SETTINGS (Right Sidebar)
┌────────────────────────────┐
│  TRAVEL DEALS              │
├────────────────────────────┤
│ Deals Per Page             │
│ [12 ▼]                     │
│                            │
│ Columns (Desktop)          │
│ [3 ▼]                      │
│                            │
│ Sort By                    │
│ [Newest ▼]                 │
│                            │
│ Enable Filters             │
│ ☑ Show filter options      │
│                            │
│ Featured Deals Only        │
│ ☐ Show only top-rated      │
│                            │
└────────────────────────────┘
```

---

### 6.6 Responsive Behavior

**Desktop (1025px+):**
- 3-column deal grid
- Top filter bar (horizontal)
- Full featured sidebar
- Large hero images

**Tablet (768px - 1024px):**
- 2-column deal grid
- Top filter bar (horizontal, smaller)
- Collapsible sidebar
- Medium hero images

**Mobile (320px - 767px):**
- 1-column deal grid
- Drawer/slide-in filter menu
- Full-width cards
- Smaller images, stacked layout
- Bottom navigation

---

### 6.7 Accessibility & Interactive Elements

#### Focus States (Keyboard Navigation)
```
[Book Now] button when focused:
┌─────────────────────────────┐
│ 🔗 Book Now                 │  (Blue outline: 2px solid #2180B7)
│ (Blue focus outline)        │
└─────────────────────────────┘
```

#### Color Contrast
- Text on background: Minimum 4.5:1 (WCAG AA)
- UI elements: Minimum 3:1 contrast ratio
- Icons paired with text labels

#### Alt Text Examples
```
Image alt text for deal cards:
"Paris City Break deal card showing Eiffel Tower, $449 price, 92/100 score, 4-night stay"

Logo alt text:
"Travel Deals Aggregator logo"
```

#### ARIA Labels
```
<button aria-label="Approve deal: Paris City Break">
  ✓ Approve
</button>

<div role="region" aria-live="polite" aria-label="Processing queue">
  3 pending deals
</div>
```

---

### 6.8 Animations & Interactions

**Deal Card Hover (Desktop):**
- Scale: 1.0 → 1.02
- Shadow: light → medium
- Duration: 200ms cubic-bezier(0.16, 1, 0.3, 1)

**Filter Toggle:**
- Drawer slides in from left (300ms ease-out)
- Overlay fade in simultaneously

**CTA Button Click:**
- Button pulse animation (50ms scale: 0.95 → 1.0)
- Color shift to darker shade
- Ripple effect (if JS enabled)

**Load Animation:**
- Deal cards fade in + slide up (staggered, 100ms each)
- Skeleton loaders while fetching data

---

## 7. SECURITY, TESTING, PERFORMANCE

### 7.1 Security Requirements

**Input Validation:**
- All admin forms: sanitize with `sanitize_text_field()`, `sanitize_url()`, `sanitize_email()`
- Database queries: Always use `$wpdb->prepare()` with placeholders
- Output escaping: `esc_html()`, `esc_attr()`, `wp_kses_post()` for user content

**API Key Storage:**
- Encrypt all credentials before storing in DB: `openssl_encrypt()`
- Use environment variables for production
- Never log or display plaintext keys
- Rotate keys every 6 months

**Access Control:**
- All admin endpoints: Check `current_user_can('manage_options')`
- REST endpoints: Nonce verification with `wp_verify_nonce()`
- Rate limiting: Max 100 requests/minute per user
- CORS: Restrict to same-origin or specific trusted domains

---

### 7.2 Testing Specifications

**Unit Tests:**
- Test each Processor class in isolation
- Mock external API calls
- Cover success and error paths
- Target >80% code coverage

**Integration Tests:**
- Test full deal publishing workflow (discovery → publish)
- Test queue processing and retry logic
- Test affiliate link transformation
- Test database operations with real (test) tables

**Manual Testing Checklist:**
- [ ] Admin dashboard loads without errors
- [ ] Can add/edit/delete sources and affiliates
- [ ] Publishing settings save correctly
- [ ] Manual approval workflow functions
- [ ] Frontend deals display properly
- [ ] Filters and sorting work on all breakpoints
- [ ] Responsive layout on mobile/tablet/desktop
- [ ] Keyboard navigation works throughout admin
- [ ] No console JavaScript errors

---

### 7.3 Performance Requirements

**Backend:**
- Deal discovery: <5s per source API call
- AI evaluation: <2s per deal (batch)
- Image download/resize: <3s per image
- Article generation: <8s per deal via AI API
- Queue processing: <10s per deal (full workflow)

**Frontend:**
- Page load (fully interactive): <2s on 4G
- Deal grid render: <1s after data load
- Filter/sort response: <300ms
- Image lazy loading: Visible after <500ms scroll

**Database:**
- Query optimization: All queries use indices
- Archive old deals monthly (keep DB <500MB)
- Cache deal listings for 1 hour via Transients

---

## 8. CONFIGURATION

All settings must be modifiable via admin UI. No magic values in code.

**Default Settings:**
- Publishing Mode: Automatic
- Min articles: 1
- Max articles: 10
- Schedule: Daily @ 08:00
- Email notifications: Enabled
- Image format: WebP
- Image quality: 80%

**Stored in Database:**
- `wp_tda_publishing_config` table
- WordPress options: `tda_ai_provider`, `tda_image_api_key`, etc.

---

## 9. CONSTRAINTS

- No features outside this specification
- No reliance on paid dependencies (use free tiers)
- No custom tables except those described in Section 4
- All code must follow PSR-4 and WordPress coding standards
- No inline CSS/JS; use enqueued files only
- All user-facing strings must be wrapped in `__()` or `_e()` for i18n

---

## 10. INSTRUCTIONS FOR AI AGENT

### DO:
- Follow this specification exactly
- Reference file paths and structure precisely
- Implement all database tables and indices
- Include proper error handling and logging
- Write secure code with validation/sanitization
- Test code before submitting
- Document complex logic inline

### DON'T:
- Invent new APIs or endpoints not in spec
- Create tables beyond Section 4
- Skip security checks or validations
- Use inline styles or scripts
- Add features not specified
- Hallucinate UI components or interactions
- Ignore database performance considerations

### CRITICAL:
- For each code generation task, reference ONLY the directory and requirements here
- If any step is ambiguous, HALT and request clarification
- All business logic must follow ranking, publishing, and affiliate rules
- Verify code doesn't violate database integrity or WordPress standards
- Prioritize maintainability, extensibility, and adherence to this document

---

**This document is the source of truth. Any deviation must be raised for review and not executed by the AI agent.**

---

*End of Comprehensive AI Coding Specification with UX/UI Design*
