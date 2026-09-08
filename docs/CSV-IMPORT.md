# CSV import

How to get companies and positions from Google Sheets into this site. Shared directory only. Bank entries are never imported.

## Sheets

Use two tabs. Export each tab as its own CSV: File → Download → Comma Separated Values (.csv). UTF-8. The first row must be the header names below, exactly.

Keep each description in one cell. Sheets will quote it on export.

Put the two files in the repo `data/` folder as `companies.csv` and `positions.csv`. Those files are gitignored.

### Slugs

You invent them. Lowercase letters, numbers, hyphens. Unique across companies and positions.

`positions.company_slug` must match a `companies.slug`. The importer does not match on company name.

Do not change a slug after the first successful import unless you want a new post. You can rename `name` / `title` on a later import.

### Blank cells

On create, omitted `status` means `publish`. Omitted `availability` means `unknown`.

On update, a blank optional cell leaves the stored value alone. It does not clear something you edited in Admin.

### companies.csv

| Column | Required | Notes |
| --- | --- | --- |
| `slug` | Yes | Match key |
| `name` | Yes | Display title |
| `description` | No | Company page body |
| `type` | No | Company type |
| `website` | No | `http://` or `https://` only |
| `location` | No | |
| `address` | No | |
| `status` | No | `publish` or `draft` |

```csv
slug,name,description,type,website,location,address,status
acme,Acme Corp,Makes widgets,Software,https://acme.example,Austin,1 Main St,publish
plain-goods,Plain Goods,,,https://plain.example,Denver,,
closed-shop,Closed Shop,No longer hiring,Agency,,Remote,,draft
```

### positions.csv

| Column | Required | Notes |
| --- | --- | --- |
| `slug` | Yes | Match key |
| `company_slug` | Yes | Must exist in companies.csv or already on the site |
| `title` | Yes | |
| `description` | No | Position page body |
| `source_url` | No | `http://` or `https://` only |
| `location` | No | |
| `tech_stack` | No | |
| `availability` | No | Exactly `open`, `closed`, or `unknown` |
| `status` | No | `publish` or `draft` |

```csv
slug,company_slug,title,description,source_url,location,tech_stack,availability,status
acme-engineer,acme,Engineer,Build the thing,https://acme.example/jobs/1,Remote,PHP,open,publish
acme-designer,acme,Designer,,,Austin,,unknown,
plain-ops,plain-goods,Operations,,,,closed,publish
ghost-role,missing-co,Ghost role,,,,,,
```

The last example row is skipped. `missing-co` is not a company.

Invalid `availability` or a non-http URL fails that row. The rest of the file still runs.

## Command

Put `companies.csv` and `positions.csv` in the repo `data/` folder. That folder is what you touch. `wp-content/jlt-data` is only the name Docker uses for the same files.

If you just added that mapping, run `npm run env:update` once so Docker can see `data/`.

Dry run:

```text
npm run import:dry
```

Real import:

```text
npm run import
```

You can still pass only `--companies` or only `--positions` with `npm run wp -- jlt import ...`. Companies must already exist (in the file or on the site) before their positions import.

Logos are not imported. Add those in Admin after.
