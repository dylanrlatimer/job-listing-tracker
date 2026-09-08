# Job Listing Tracker: App Outline v3

## Document purpose

This document defines the scope, architecture, and delivery plan for version 1 of Job Listing Tracker. It supersedes [APP-OUTLINE-v2.md](APP-OUTLINE-v2.md) and is the authority for what the application does and which parts of the WordPress stack own each responsibility.

Field definitions, storage details, relationships, and deletion rules belong in [SCHEMA.md](SCHEMA.md).

## Purpose

Job Listing Tracker is a small, public-facing WordPress application for organizing a personal job search around companies and their relevant positions.

An administrator curates the shared company and position directory. Guests can browse the full directory. Registered users can save companies to a private bank, choose which positions to track, and maintain private notes and statuses for both.

The deployed site is a portfolio demonstration and a practical personal tool. It is not intended to become a general-purpose job board or membership product.

## Version 1 scope

Version 1 must support:

- Public company and position browsing.
- Companies with zero, one, or many positions.
- Administrator-managed shared content.
- Public registration, login, logout, and password recovery.
- A private company bank for each registered user.
- Selective position tracking within banked companies.
- One editable notes field and one status per company entry.
- One editable notes field and one status per position entry.
- Complete separation between users' private data.
- Desktop and mobile layouts.

## Product model

The application has four records:

| Record         | Visibility            | Owner | Purpose                                        |
| -------------- | --------------------- | ----- | ---------------------------------------------- |
| Company        | Public when published | Site  | Shared organization profile                    |
| Position       | Public when published | Site  | Relevant opening belonging to one company      |
| Company entry  | Private               | User  | The user's company-level status and notes      |
| Position entry | Private               | User  | The user's application status, date, and notes |

Saving a company creates a company entry; it does not copy the company. Saving a position creates a position entry; it does not copy the position. A user may track a position only after adding its company to their bank.

Company-level and position-level tracking remain separate. A result for one position does not change the user's relationship with the company.

## Roles and access

### Guest

A guest can browse all published company and position information. A guest cannot use bank or tracking features.

### Registered user

A registered user can browse the directory and create, view, update, or remove only their own company and position entries. Registration grants no access to shared-content administration.

### Administrator

An administrator manages companies and positions in WordPress Admin. Administrators may also use the front-end tracking features for their own account.

WordPress core owns accounts, passwords, sessions, roles, and authentication. Public registration is always enabled, with new accounts assigned the Subscriber role.

## Core workflows

### Browse companies

1. A visitor opens the company directory.
2. They select a company.
3. The company page shows all shared company details and its published positions.
4. They may open a position page for its full details and source link.

### Add a company to the bank

1. A signed-in user selects **Add to bank** on a company.
2. The application creates one private company entry for that user and company.
3. The company appears in the user's bank.

Guests who select a private action are sent to the login page and returned afterward.

### Track a position

1. A user opens a company already in their bank.
2. They review that company's positions.
3. They select **Track position** on one relevant position.
4. The application creates one private position entry.
5. The user can edit its status, application date, and notes.

The application does not automatically track every position at a saved company.

### Manage a banked company

The private company view combines:

- The current shared company information.
- The user's company status and notes.
- The positions the user tracks at that company.
- Other published positions at that company, each with a tracking action.

### Remove tracked records

A user may stop tracking a position at any time. A company cannot be removed from the bank while the user still tracks one of its positions; the interface explains which positions must be removed first.

## Screens and routes

Version 1 requires these front-end screens:

| Screen                                 | Access         | Responsibility                                                 |
| -------------------------------------- | -------------- | -------------------------------------------------------------- |
| Company directory                      | Public         | Browse published companies                                     |
| Company detail                         | Public         | Show one company and its published positions                   |
| Position detail                        | Public         | Show one position and link to its original source              |
| Login, registration, password recovery | Public         | Front-end account access                                       |
| Bank overview                          | Signed-in user | List the user's saved companies and tracked-position summaries |
| Banked company detail                  | Entry owner    | Edit company tracking and manage positions at that company     |
| Tracked position detail                | Entry owner    | Edit position status, application date, and notes              |
| Settings                               | Signed-in user | Show the account email and links to reset the password or log out |

The bank uses one WordPress Page with server-rendered views selected by validated query parameters. Clean custom rewrite routes are optional polish, not a version 1 dependency.

WordPress Admin supplies the company and position editing screens. Private entries are not exposed as editable admin screens.

## Technical architecture

### WordPress core

WordPress provides the runtime, database abstraction, users, roles, authentication, media library, content administration, routing foundation, and security APIs.

### Job Listing Tracker plugin

One project-specific plugin owns application behavior:

- Registers the four custom post types.
- Registers and validates private tracking metadata.
- Enforces relationships, uniqueness, ownership, and deletion rules.
- Handles add, edit, and remove actions.
- Supplies the bank page controller and reusable view helpers.
- Loads the source-controlled ACF field definitions.
- Provides a WP-CLI command that loads shared companies and positions from CSV.

Business rules stay in the plugin so they survive a theme change.

### Custom theme

One project-specific theme owns presentation:

- Company archive and single templates.
- Position single template.
- Bank and settings templates and shared view components.
- Responsive layout, typography, and styles.
- Small progressive enhancements written in vanilla JavaScript.

Version 1 is server-rendered PHP. It does not require React, a front-end framework, or custom Gutenberg blocks.

### Third-party plugins

| Plugin                      | Environment                | Use                                                                                   |
| --------------------------- | -------------------------- | ------------------------------------------------------------------------------------- |
| Advanced Custom Fields Free | Development and production | Administrator field UI for shared companies and positions                             |
| Theme My Login              | Development and production | Front-end registration, login, logout, and password recovery using WordPress accounts |
| Query Monitor               | Development only           | Inspect queries, hooks, errors, and template behavior                                 |

ACF field groups are stored as Local JSON with the custom plugin so field configuration is version-controlled. ACF is not used to build the users' private tracking forms.

No membership suite, custom-post-type generator, form builder, SEO plugin, or database-management plugin is required for version 1. SMTP or registration anti-spam may be added during deployment only if the host requires it.

## Local development and repository

The project uses `wp-env` for a reproducible local WordPress installation. Docker is the only local runtime prerequisite beyond Node.js and the project tooling.

The repository contains:

- The Job Listing Tracker plugin.
- The custom theme.
- ACF Local JSON field definitions.
- `wp-env` configuration.
- Project documentation and tests.

The repository does not contain WordPress core, a local database dump, generated uploads, secrets, or third-party plugin source. `wp-env` installs Advanced Custom Fields, Theme My Login, and Query Monitor from WordPress.org.

A WP-CLI command can load the shared directory from CSV. That is an administrator content tool. It does not define the schema, does not import bank entries, and is not a public or scheduled import product. See [CSV-IMPORT.md](CSV-IMPORT.md).

## Security and privacy

- The server checks authentication, record ownership, and permissions on every private read and write.
- A record ID supplied by a browser never proves ownership.
- State-changing requests require WordPress nonces.
- Inputs are validated and sanitized before storage; output is escaped for its display context.
- Private records are excluded from public queries, search results, feeds, REST responses, sitemaps, and WordPress Admin screens.
- Shared company and position records never contain user-owned notes or statuses.
- The deployment should discourage indexing because it is a portfolio demonstration, not a search-acquisition project.

## Delivery sequence

This was the original build order. The application described above is now implemented.

1. Create the `wp-env` project, custom plugin, and theme skeletons.
2. Register the four post types and their fields.
3. Configure ACF Local JSON for company and position administration.
4. Build the public company and position templates.
5. Add Theme My Login and enable public registration.
6. Build company-bank actions and views.
7. Build position-tracking actions and views.
8. Add ownership, validation, deletion, and security checks.
9. Test the full workflow with two user accounts and an administrator.
10. Complete responsive and accessibility checks, then deploy.

## Version 1 completion criteria

Version 1 is complete when:

- An administrator can create, edit, publish, trash, and restore companies and positions, and mark positions as closed.
- Guests can see all published company and position details.
- A visitor can register, log in, recover a password, and log out from front-end screens.
- A user can add each company to their bank once.
- A user can track each position once after saving its company.
- A user can edit and remove their own company and position tracking records.
- Closed or trashed shared records do not erase existing private tracking unexpectedly.
- Attempts to access another user's private records fail on the server.
- The primary workflows are usable with a keyboard and at mobile widths.
- A fresh checkout can start locally using the documented `wp-env` commands.

## Explicit non-goals

Version 1 does not include:

- Automatic job scraping or synchronization.
- Applications submitted to employers.
- User-created shared companies or positions.
- Public user profiles, member directories, social features, or custom member roles.
- Paid memberships or content restriction.
- Multiple contacts, interview records, reminders, or notification scheduling.
- Note history, audit logs, or revision workflows for private tracking.
- Structured technology, company-type, employment-type, or location taxonomies.
- Search-engine marketing or public growth features.
- A public import UI, scheduled import, or job-site feed.
