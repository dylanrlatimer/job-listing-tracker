# Job Listing Tracker

A WordPress app for organizing a job search around companies and their positions. I use it myself. The public site is also a portfolio piece. It is not a job board and it is not trying to become one.

An administrator curates the shared directory. Anyone can browse it. A signed-in user can save companies to a private bank, track specific positions, and keep notes and statuses that nobody else can see.

## Run it locally

You need Node.js LTS, Docker Desktop, and Git.

```powershell
npm install
npm run env:start
```

The site is at [http://localhost:8888](http://localhost:8888). Log in as `admin` / `password`.

`wp-env` installs Advanced Custom Fields, Theme My Login, and Query Monitor. Those plugins are not in this git repo.

After the first start, in Admin:

1. Set permalinks to Post name.
2. Create a page titled Bank with the slug `bank`.
3. Create a page titled Settings with the slug `settings`.
4. Allow anyone to register if that is not already on.

```powershell
npm run env:stop
```

## Load directory content

Companies and positions can be imported from CSV. Bank entries are never imported. See [docs/CSV-IMPORT.md](docs/CSV-IMPORT.md).

```powershell
npm run import:dry
npm run import
```

## Tests

```powershell
npm test
```

## Docs

[docs/APP-OUTLINE.md](docs/APP-OUTLINE.md) is what the app is. [docs/SCHEMA.md](docs/SCHEMA.md) is the data model. [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md) is how the plugin and theme split the work.

## License

[GPL-2.0-or-later](LICENSE). Same terms as WordPress.
