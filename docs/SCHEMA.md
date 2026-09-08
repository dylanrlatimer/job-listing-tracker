# Job Listing Tracker: Schema v4

## Document purpose

This document defines the version 1 data model for Job Listing Tracker. It is the authority for record storage, fields, relationships, ownership, validation, and deletion behavior.

The product scope, screens, tools, and delivery plan are defined in [APP-OUTLINE.md](APP-OUTLINE.md).

## Storage model

Version 1 uses WordPress posts, post metadata, and users. It does not add custom database tables.

| Record         | WordPress storage              | Visibility            | Owner |
| -------------- | ------------------------------ | --------------------- | ----- |
| User           | Core user                      | Account-dependent     | User  |
| Company        | `jlt_company` post type        | Public when published | Site  |
| Position       | `jlt_position` post type       | Public when published | Site  |
| Company entry  | `jlt_company_entry` post type  | Private               | User  |
| Position entry | `jlt_position_entry` post type | Private               | User  |

The expected data volume is small. Private entries contain only an owner, one shared-record reference, a status, notes, and an optional application date. Custom tables would add migrations and query code without solving a version 1 requirement.

## Users

WordPress core stores users, passwords, roles, sessions, registration, and account-recovery data. No custom user fields or user table are required.

Public registration assigns the Subscriber role. Subscribers can use front-end tracking features but cannot administer shared companies or positions.

## Company

Companies use the `jlt_company` custom post type.

### Registration

| Setting        | Value                          |
| -------------- | ------------------------------ |
| `public`       | `true`                         |
| `has_archive`  | `true`                         |
| `show_in_rest` | `true`                         |
| `rewrite`      | `companies`                    |
| `supports`     | `title`, `editor`, `thumbnail` |

### Fields

| Field        | Storage            | Type        | Required  | Rule                                                          |
| ------------ | ------------------ | ----------- | --------- | ------------------------------------------------------------- |
| ID           | `ID`               | Integer     | Automatic | WordPress post ID                                             |
| Name         | `post_title`       | String      | Yes       | Non-empty after trimming                                      |
| Description  | `post_content`     | Rich text   | No        | Administrator-authored content                                |
| Slug         | `post_name`        | String      | Automatic | WordPress-generated unless edited                             |
| Visibility   | `post_status`      | Core status | Yes       | Draft, published, or trashed through normal editorial actions |
| Logo         | Featured image     | Attachment  | No        | Image attachment                                              |
| Company type | `jlt_company_type` | String      | No        | Free-form text                                                |
| Website      | `jlt_website_url`  | URL         | No        | HTTP or HTTPS URL                                             |
| Location     | `jlt_location`     | String      | No        | Free-form place name                                          |
| Address      | `jlt_address`      | String      | No        | Free-form address                                             |

A company may exist without any positions.

## Position

Positions use the `jlt_position` custom post type.

### Registration

| Setting        | Value             |
| -------------- | ----------------- |
| `public`       | `true`            |
| `has_archive`  | `false`           |
| `show_in_rest` | `true`            |
| `rewrite`      | `positions`       |
| `supports`     | `title`, `editor` |

Positions are discovered through company pages rather than a separate version 1 archive.

### Fields

| Field            | Storage            | Type        | Required  | Rule                                                          |
| ---------------- | ------------------ | ----------- | --------- | ------------------------------------------------------------- |
| ID               | `ID`               | Integer     | Automatic | WordPress post ID                                             |
| Title            | `post_title`       | String      | Yes       | Non-empty after trimming                                      |
| Description      | `post_content`     | Rich text   | No        | Full public position details                                  |
| Slug             | `post_name`        | String      | Automatic | WordPress-generated unless edited                             |
| Visibility       | `post_status`      | Core status | Yes       | Draft, published, or trashed through normal editorial actions |
| Company          | `jlt_company_id`   | Integer     | Yes       | ID of one existing `jlt_company` post                         |
| Source URL       | `jlt_source_url`   | URL         | No        | HTTP or HTTPS link to the original listing                    |
| Location         | `jlt_location`     | String      | No        | Free-form position location                                   |
| Technology stack | `jlt_tech_stack`   | Text        | No        | Free-form text                                                |
| Availability     | `jlt_availability` | String      | Yes       | `open`, `closed`, or `unknown`; default `unknown`             |

The company field is an ACF Post Object limited to companies, single-select, and configured to return the post ID.

Each position belongs to exactly one company. A company can have any number of positions.

Availability describes the shared listing. It is independent of every user's position status.

## Company entry

Adding a company to a user's bank creates one `jlt_company_entry` post. This record stores only user-owned tracking data and a reference to the company.

### Registration

| Setting               | Value   |
| --------------------- | ------- |
| `public`              | `false` |
| `publicly_queryable`  | `false` |
| `exclude_from_search` | `true`  |
| `show_ui`             | `false` |
| `show_in_rest`        | `false` |
| `rewrite`             | `false` |
| `query_var`           | `false` |
| `delete_with_user`    | `true`  |
| `supports`            | None    |

Entries are created and edited only through the plugin's front-end handlers. They have no public permalink or administrator editing screen.

### Fields

| Field          | Storage             | Type       | Required  | Rule                                                               |
| -------------- | ------------------- | ---------- | --------- | ------------------------------------------------------------------ |
| Entry ID       | `ID`                | Integer    | Automatic | WordPress post ID                                                  |
| Owner          | `post_author`       | Integer    | Yes       | Authenticated WordPress user ID                                    |
| Internal title | `post_title`        | String     | Automatic | Generated for diagnostics; never shown to users                    |
| Notes          | `post_content`      | Plain text | No        | One editable notes field                                           |
| Created        | `post_date_gmt`     | Datetime   | Automatic | WordPress creation time                                            |
| Updated        | `post_modified_gmt` | Datetime   | Automatic | WordPress modification time                                        |
| Company        | `jlt_company_id`    | Integer    | Yes       | ID of one existing `jlt_company` post                              |
| Status         | `jlt_status`        | String     | Yes       | `interested`, `contacted`, or `not_pursuing`; default `interested` |

The post uses the `publish` status. This does not make it public because the post type is non-public, non-queryable, excluded from search, and absent from REST.

## Position entry

Tracking a position creates one `jlt_position_entry` post. This record stores only user-owned tracking data and a reference to the position.

### Registration

| Setting               | Value   |
| --------------------- | ------- |
| `public`              | `false` |
| `publicly_queryable`  | `false` |
| `exclude_from_search` | `true`  |
| `show_ui`             | `false` |
| `show_in_rest`        | `false` |
| `rewrite`             | `false` |
| `query_var`           | `false` |
| `delete_with_user`    | `true`  |
| `supports`            | None    |

### Fields

| Field          | Storage             | Type        | Required  | Rule                                            |
| -------------- | ------------------- | ----------- | --------- | ----------------------------------------------- |
| Entry ID       | `ID`                | Integer     | Automatic | WordPress post ID                               |
| Owner          | `post_author`       | Integer     | Yes       | Authenticated WordPress user ID                 |
| Internal title | `post_title`        | String      | Automatic | Generated for diagnostics; never shown to users |
| Notes          | `post_content`      | Plain text  | No        | One editable notes field                        |
| Created        | `post_date_gmt`     | Datetime    | Automatic | WordPress creation time                         |
| Updated        | `post_modified_gmt` | Datetime    | Automatic | WordPress modification time                     |
| Position       | `jlt_position_id`   | Integer     | Yes       | ID of one existing `jlt_position` post          |
| Status         | `jlt_status`        | String      | Yes       | Allowed position status; default `interested`   |
| Applied date   | `jlt_applied_on`    | Date string | No        | Strict `YYYY-MM-DD` value or empty              |

Allowed position statuses are:

- `interested`
- `applied`
- `interviewing`
- `rejected`
- `offer`
- `accepted`
- `withdrawn`

The post uses the `publish` status under the same private post-type protections as a company entry.

## Relationships and uniqueness

| Relationship                    | Cardinality                     | Enforcement                                                            |
| ------------------------------- | ------------------------------- | ---------------------------------------------------------------------- |
| Company to position             | One to zero-or-many             | Each position stores one valid `jlt_company_id`                        |
| User to saved company           | One entry per user and company  | Plugin checks `post_author` and `jlt_company_id` before insert         |
| User to tracked position        | One entry per user and position | Plugin checks `post_author` and `jlt_position_id` before insert        |
| Company entry to position entry | Indirect parent requirement     | The position's company must already have a company entry for that user |

Uniqueness is enforced in application code. A database-level unique constraint is not required for the expected version 1 traffic. Add and track handlers must check for an existing entry immediately before insertion and return that entry rather than creating a duplicate.

Private records reference shared posts by ID; they do not duplicate company names, position titles, URLs, or descriptions.

## Metadata registration

The custom plugin registers private fields with `register_post_meta()` using the declared type, `single => true`, sanitization callbacks, and authorization callbacks. Private metadata is not exposed in REST.

ACF manages only the shared company and position fields. ACF value keys use the names shown in this document, without a leading underscore. ACF automatically creates companion keys such as `_jlt_location` to store its internal field reference; those companion keys are not application fields.

ACF field groups are saved and loaded as Local JSON inside the custom plugin. Private entry fields are implemented by the plugin's front-end forms and do not use ACF.

## Validation and sanitization

| Input                   | Rule                                                                |
| ----------------------- | ------------------------------------------------------------------- |
| Shared-record IDs       | Convert with `absint()` and confirm the referenced post type exists |
| URLs                    | Sanitize with `esc_url_raw()` and accept only HTTP or HTTPS         |
| Status and availability | Accept only the values listed in this document                      |
| Applied date            | Accept empty or a real calendar date matching `YYYY-MM-DD` exactly  |
| Private notes           | Sanitize as multiline plain text; HTML is not stored                |
| Short free-form fields  | Trim and sanitize as plain text                                     |

All displayed data is escaped for its output context. Validation occurs on the server even when the browser also validates a field.

## Ownership and access rules

- Every private query specifies both the private post type and `post_author` equal to the current user ID.
- Every update and deletion loads the entry and confirms that its `post_author` is the current user.
- A request-supplied entry ID or shared post ID never establishes ownership.
- Guests cannot read or mutate private entries.
- Registered users cannot query another user's private entries through templates, actions, REST, search, feeds, or sitemaps.
- State-changing requests require a valid WordPress nonce.
- Shared company and position templates never query unscoped private records.
- Administrators manage shared records but do not receive a front-end mechanism to browse users' private notes.

## Lifecycle and deletion

### User actions

- Stopping position tracking permanently deletes that user's position entry.
- Removing a company permanently deletes that user's company entry only when no position entries at that company remain.
- Deleting a user permanently deletes that user's company and position entries through `delete_with_user`.
- Private notes do not use WordPress revisions or a soft-delete archive in version 1.

### Shared position actions

- Setting availability to `closed` preserves the position and all private position entries.
- Trashing a position removes it from public views while preserving private entries. Tracking views show that the shared position is unavailable.
- Permanently deleting a position permanently deletes all position entries that reference it.

### Shared company actions

- Trashing a company removes it and its positions from public views while preserving the records and private entries.
- Restoring the company restores normal public eligibility for its published positions.
- Permanently deleting a company is an explicit destructive administrator action. It permanently deletes that company's positions, company entries, and related position entries.

The plugin performs dependent cleanup for permanent shared-record deletion. Normal editorial changes never erase user tracking.

## Query rules

- Public company queries include only published `jlt_company` posts.
- A public position is shown only when both the position and its referenced company are published.
- Company pages fetch positions by `jlt_company_id` and sort them predictably, with open positions before closed or unknown positions.
- Bank queries fetch private entries by current user, then resolve their shared posts.
- Private entry post types are never included in generic public searches or archives.

## Deliberately excluded from version 1

- Custom database tables and schema migrations.
- Taxonomies for technology, company type, location, or position type.
- Structured addresses or contact records.
- Multiple notes, note history, or activity logs.
- Reminder, follow-up, interview, and notification records.
- Priority and archive fields.
- Position source, work-arrangement, or employment-type enums.
- Automatic imports or synchronization with job sites.
- Custom user-profile data.
- Database foreign keys.
