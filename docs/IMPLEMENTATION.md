# Job Listing Tracker: implementation plan

## What this document is for

This is the working map for building version 1. It turns the app outline and schema into an order of work, a repository shape, and a set of boundaries that should remain stable while the code changes.

Use it to answer three questions during development:

1. What should we build next?
2. Which part of the project should own it?
3. What must work before we move on?

This is not a replacement for the product documents. `APP-OUTLINE-v2.md` remains the authority for scope and `SCHEMA-v3.md` remains the authority for data. If implementation exposes a real problem in either document, stop and change the relevant document deliberately. Do not let the code quietly invent a different product.

## Current starting point

The pre-steps are complete. WordPress runs through `wp-env`, the required third-party plugins are installed, registration is enabled, and the repository has `main` and `dev` branches.

No application code, theme, ACF field groups, Bank page, test accounts, or sample records exist yet.

Development starts on `dev`. `main` should continue to represent the deployed, stable version.

## The shape of the application

Job Listing Tracker has two types of content and two types of behavior:

| Area             | Records                              | Managed through        | Owned by                                 |
| ---------------- | ------------------------------------ | ---------------------- | ---------------------------------------- |
| Shared directory | Companies and positions              | WordPress Admin        | Plugin for data rules, theme for display |
| Private tracker  | Company entries and position entries | Front-end Bank screens | Plugin                                   |

The browser does not talk directly to the database. Public pages run normal WordPress queries. Private forms submit to named WordPress handlers in the plugin. Those handlers authenticate the user, verify a nonce, validate the requested relationship, perform the write, and redirect back to a Bank view.

The application should remain server-rendered. A small JavaScript file may improve menus, confirmations, or other interactions, but version 1 should still work without JavaScript. There is no React application, REST client, AJAX data layer, custom block, or front-end build pipeline to create.

## Ownership boundaries

### WordPress core

WordPress owns users, passwords, sessions, roles, post storage, metadata storage, routing, media, and the administration interface. Use those systems rather than recreating them.

### The project plugin

The plugin owns everything that would still need to be true after changing themes:

- Custom post type registration and capabilities.
- Metadata registration and allowed values.
- ACF Local JSON paths and shared-field configuration.
- Lookups for public records and the current user's private entries.
- Entry creation, updates, and deletion.
- Authentication, ownership, nonce, validation, and sanitization checks.
- Relationship and uniqueness rules.
- Cleanup after permanent deletion of shared records.
- URLs and redirects used by Bank actions.

Templates must not contain business rules disguised as conditional markup. They may decide how to display a state, but they should not decide whether a user is allowed to create, update, read, or delete a record.

### The project theme

The theme owns HTML, layout, responsive behavior, typography, styles, navigation, and template composition. It may call public functions exposed by the plugin. It must not write private records or query private post types directly.

The theme is an application-specific classic PHP theme. A small `theme.json` may define editor and design defaults, but adopting full-site editing would add a second templating model without helping version 1.

### Third-party plugins

ACF supplies the administrator field UI for companies and positions. Theme My Login supplies front-end account screens backed by WordPress core. Query Monitor is for local diagnosis only.

Application code must handle a missing ACF dependency without causing a fatal error. A clear administrator notice is enough. Theme My Login is presentation around core authentication, so application authorization must never depend on it.

## Repository target

The exact filenames may shift when a module earns a clearer name. The main boundary should not.

```text
job-listing-tracker/
  .wp-env.json
  .gitignore
  AGENTS.md
  IMPLEMENTATION.md
  package.json
  docs/
    APP-OUTLINE-v2.md
    CONTEXT-PRIMER.md
    PRESTEPS-v2.md
    SCHEMA-v3.md
  wp-content/
    plugins/
      job-listing-tracker/
        job-listing-tracker.php
        includes/
          post-types.php
          meta.php
          acf.php
          queries.php
          entries.php
          actions.php
          lifecycle.php
          urls.php
        acf-json/
        tests/
          bootstrap.php
          test-entries.php
          test-lifecycle.php
        phpunit.xml.dist
    themes/
      job-listing-tracker/
        style.css
        functions.php
        theme.json
        index.php
        header.php
        footer.php
        archive-jlt_company.php
        single-jlt_company.php
        single-jlt_position.php
        page-bank.php
        template-parts/
          company-card.php
          position-card.php
          bank-overview.php
          bank-company.php
          bank-position.php
          notices.php
        assets/
          css/main.css
          js/main.js
```

`job-listing-tracker.php` is a small bootstrap file. The files in `includes/` group code by responsibility rather than introducing a class for every noun. Use one namespace or a consistent `jlt_` prefix throughout. Do not add Composer autoloading unless the code actually grows into a class-based structure that benefits from it.

The theme's `functions.php` should also stay small. It registers theme support, menus, and assets. Page logic belongs in templates or, when it governs application behavior, in the plugin.

The plugin and theme paths must be mounted in `.wp-env.json`. Keep third-party plugin source, WordPress core, uploads, and local database state out of Git.

## Stable implementation rules

These rules apply in every milestone.

### Keep one definition of each domain value

Post type names, metadata keys, company statuses, position statuses, and availability values should each be declared once in the plugin. Forms, validation, queries, and templates should read the same definitions. Do not scatter matching string arrays across files.

### Expose a small plugin API to the theme

The theme should use plugin functions with clear jobs, such as fetching the current user's company entries, resolving a Bank view, or rendering a form action URL. It should not call `get_posts()` against private entry types or reproduce ownership checks.

Shared ACF values should also pass through plugin accessors. That keeps templates independent of ACF's return-format details and gives missing values one consistent interpretation.

### Treat every private request as hostile

Every private read scopes the query to the current user. Every update and delete reloads the entry and checks `post_author`. Every write validates the referenced post type and relationship. Nonces protect requests from cross-site submission, but they do not replace authorization.

### Use POST, then redirect

Add, update, and remove operations use HTML forms that submit to `wp-admin/admin-post.php`. Each action has a specific `admin_post_jlt_*` handler. A successful or failed handler returns to a local URL with a short result code that the Bank page turns into a message.

Do not put private notes or raw validation messages in redirect query strings. Do not mutate state from a normal link or GET request.

### Keep WordPress hooks at the edge

Hook callbacks should collect WordPress input and call a function with a narrow job. For example, an action handler should not contain the full entry-creation algorithm. This separation makes the business rules readable and testable without simulating an entire browser request.

### Prefer visible correctness over abstraction

Version 1 has four record types and a small number of workflows. Repeated two-line WordPress calls are cheaper than a generic repository framework. Extract code when it represents a rule or removes meaningful duplication, not because another framework would usually have a service layer.

### Preserve data unless deletion is explicit

Plugin deactivation must not delete records. Trashing shared content must not delete private entries. Permanent cleanup belongs only to the deletion paths defined in the schema. Recursive deletion code needs automated coverage before it is considered finished.

## Build sequence

Each milestone ends with a usable state. Finish its checks before asking an agent to begin the next one.

### Milestone 1: application skeleton

Create the plugin and theme, mount them through `.wp-env.json`, and prove that WordPress can load both.

The plugin should:

- Have valid plugin metadata and a guarded bootstrap.
- Load its include files from one place.
- Register activation and deactivation hooks for rewrite flushing.
- Show an administrator notice when ACF is unavailable.
- Avoid deleting any data on activation or deactivation.

The theme should:

- Have the minimum required files and valid theme metadata.
- Register basic theme support and enqueue one stylesheet.
- Render a plain header, content area, and footer.
- Use semantic HTML without attempting the final design.

Update `.wp-env.json`, start the environment, activate the plugin and theme, and make sure the front end and Admin both load with `WP_DEBUG_LOG` clean.

Do not register fields or build screens in this milestone. Its purpose is to establish where code lives and make later failures local rather than structural.

### Milestone 2: shared content in WordPress Admin

Implement companies and positions as administrator-managed content.

Work in this order:

1. Register `jlt_company` and `jlt_position` in the plugin with the arguments in the schema.
2. Give shared-content management explicit capabilities and grant them to administrators on activation. Public reading should continue to use normal WordPress behavior.
3. Register shared metadata in code so its type and sanitization do not exist only inside ACF.
4. Configure the plugin's `acf-json` directory as an ACF save and load path.
5. Create the two ACF field groups in Admin, verify their names and return formats against the schema, and commit the generated JSON.
6. Add ACF validation for the required company relationship, allowed availability value, and URL fields.

At the end, an administrator must be able to create a company, create a position belonging to it, edit both, and see the values persist after reload. A fresh database must be able to discover and synchronize the committed field groups.

Use only a few sample records chosen to exercise the model: a company with no positions, a company with open and closed positions, and one draft company. This content is local test data and does not belong in Git.

### Milestone 3: public directory

Build the public read-only path before private tracking.

Implement:

- The company archive.
- The company detail page and its published positions.
- The position detail page and source link.
- The rule that a position is public only when both it and its company are published.
- Predictable position ordering, with open records ahead of closed and unknown records.
- Empty states for a directory with no companies and a company with no positions.

Keep data retrieval in plugin query or accessor functions. Theme templates should receive WordPress objects or small view-ready arrays and render them.

At the end, test public pages while logged out. Draft and trashed records must not appear. A published position attached to an unpublished company must not be reachable through the application templates.

Do a first responsive and keyboard pass here. It is easier to correct the basic document structure before forms and private views multiply it.

### Milestone 3.5: shared-directory Admin experience

Improve the administrator workflow for managing the Company-to-Position relationship. Keep Companies and Positions as separate post types linked by `jlt_company_id`. This milestone changes how administrators navigate and manage those records, not how the records are stored or displayed publicly.

Add one plugin module for Admin-specific behavior and load it from the plugin bootstrap. Keep these changes in the plugin and behind WordPress Admin hooks. The theme must not participate in Admin behavior.

Implement:

- Group Companies and Positions under one top-level **Job Listings** Admin menu while preserving their existing list, create, and edit screens.
- Move the ACF **Position Details** field group directly below the Position title so the required Company relationship is visible before the main content editor. Commit the resulting Local JSON change.
- Add Company and Availability columns to the Positions list. The Company name should link to that Company's edit screen. A missing or unavailable relationship should display a clear fallback rather than an empty cell or PHP warning.
- Add a Company filter to the Positions list. Apply it only to the main `jlt_position` Admin query, validate the selected ID, and leave unrelated Admin queries unchanged.
- Add a **Positions at this Company** panel to the Company edit screen. List each related Position's title, availability, publication status, and edit link. Include non-trashed statuses that an administrator may need to manage, not only published Positions. Show a useful empty state when the Company has none.
- Add an **Add Position for this Company** action to that panel. It should open the normal new-Position screen with the current Company preselected in the ACF Company field.
- Treat the Company ID passed to the new-Position screen only as a default value. Confirm that it identifies a `jlt_company` the current user may edit, do not overwrite an existing Position's saved relationship, and do not treat the query parameter as authorization or as saved data.

Use a Company-scoped Admin query for the related-Positions panel. Do not weaken or reuse the public query in a way that allows draft, private, or otherwise unpublished Positions onto the public site.

Do not build inline Position editing inside the Company form, a custom Admin application, AJAX behavior, or another stored relationship. Position creation and editing should continue through WordPress's standard Position editor.

At the end, an administrator should be able to create a Company, remain in that Company's editing context, start a Position with the Company already selected, and later see and open all non-trashed Positions related to that Company. The Positions list must support the reverse workflow by showing and filtering on Company. Existing Company-to-Position relationships, capabilities, public URLs, and public directory behavior must remain unchanged.

Verify the workflow with a Company that has no Positions, a Company with Positions in more than one publication and availability state, an invalid Company ID in the prefill URL, and an existing Position whose saved Company must not be overwritten. Check the debug log and the relevant Admin queries before moving to Milestone 4.

### Milestone 4: authentication and the Bank shell

Connect the existing account screens to the application and establish one private route.

Create a WordPress Page named `Bank` using `page-bank.php`. The page has three server-rendered modes:

- `/bank/` shows the overview.
- `/bank/?view=company&entry={id}` shows one owned company entry.
- `/bank/?view=position&entry={id}` shows one owned position entry.

The plugin resolves the requested mode, validates the ID, and returns either authorized view data or a not-found result. The theme chooses the matching template part. Do not use a browser-supplied company or position ID as proof that a private entry belongs to the viewer.

Logged-out visitors who request the Bank go to login and return afterward. Public company and position pages should show a login link with a return URL where a private action would otherwise appear.

Before moving on, verify registration, login, logout, lost password, return-after-login, and rejection of a Bank URL belonging to another user. The Bank overview can still be empty at this point.

### Milestone 5: company bank

Implement the first complete private vertical slice.

The plugin adds functions to:

- Find a company entry for one user and company.
- List the current user's company entries.
- Create an entry or return the existing one.
- Validate and update company status and notes.
- Remove an entry only when the user has no tracked positions at that company.

Add POST handlers for create, update, and remove. Then add the forms and views to the public company page, Bank overview, and Bank company view.

This milestone is complete when two users can independently add the same company, store different private values, update them, and remove their own entry without seeing or changing the other's. Repeated submissions must not create duplicates.

This is the first point where automated plugin tests earn their cost. Cover ownership, duplicate prevention, status validation, plain-text note sanitization, and company removal with and without dependent positions.

### Milestone 6: position tracking

Build position tracking on top of the company-bank rules rather than as a parallel feature.

The plugin adds functions to:

- Find a position entry for one user and position.
- List tracked positions for an owned company entry.
- Create an entry only when that user has already saved the position's company.
- Validate and update status, applied date, and notes.
- Permanently remove the user's position entry.

Add the corresponding handlers and forms. The Bank company view should now separate tracked positions from other published positions at that company. The position entry view edits the private position fields while still showing current shared listing information.

Test invalid dates as well as real calendar dates. Also test attempts to track a position under a different company, track without a company entry, update another user's entry, and submit the same create action twice.

At the end of this milestone, the primary user journey in the app outline works from registration through company and position tracking.

### Milestone 7: content lifecycle and unavailable records

Now implement the cases that are easy to miss when all sample content is published.

Add dependent cleanup for permanent shared-record deletion:

- Deleting a position permanently deletes its position entries.
- Deleting a company permanently deletes its positions, company entries, and related position entries.

Keep trash and restore behavior non-destructive. Bank views should retain entries whose shared record is trashed or otherwise unavailable, explain the state without exposing hidden public content, and allow the user to remove their tracking record.

Add automated tests for every trash, restore, and permanent-delete path. Pay particular attention to recursive hooks so one deletion cannot repeat indefinitely or clean up records outside the target relationship.

Use an administrator account plus two subscribers for the manual pass. Query Monitor and the debug log should show no warnings, accidental unbounded private query, or repeated query caused by a template loop.

### Milestone 8: completion pass and deployment preparation

Only after the full workflow works should the project receive its final design and deployment work.

Complete:

- Mobile layout and navigation.
- Visible focus states, form labels, error association, status messaging, and keyboard operation.
- Empty, invalid, unauthorized, unavailable, and success states.
- Consistent escaping in every template.
- A clean run with debugging enabled.
- Automated plugin tests and the manual workflow matrix.
- Production notes for installing dependencies, syncing ACF JSON, creating the Bank page, setting permalinks and registration, and disabling Query Monitor.
- A fresh-checkout rehearsal rather than trusting the existing local database.

Production configuration should discourage indexing and use HTTPS. Confirm outbound mail for registration and password recovery on the chosen host. SMTP or anti-spam belongs here only if the host and public registration make it necessary.

Do not deploy by copying the entire `wp-env` installation. Deploy the source-controlled plugin and theme into a separately configured WordPress installation, install the production third-party plugins, synchronize the ACF field groups, apply the documented settings, and enter the shared directory content there.
