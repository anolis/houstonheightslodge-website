# Houston Heights Lodge Website

Public Laravel site for Houston Heights Lodge #225.

- Live URL: https://houstonheightslodge225.com (and `www.`; `website.` 301-redirects here)
- Server path: `/var/www/website`
- Web root: `/var/www/website/public`
- Production server: `lodge`
- Repository: `git@github.com:anolis/houstonheightslodge-website.git`

## Overview

This app is the Laravel port of the former static/PHP site in `/var/www/html`. The old page fragments were copied into Blade views and are rendered by Laravel routes instead of the old AJAX shell.

Key files:

- `routes/web.php`: public page routes, metadata map, and downloads routes.
- `resources/views/layouts/public.blade.php`: shared layout, navigation, footer, analytics, and the five-tap downloads shortcut on the site title.
- `resources/views/pages/*.blade.php`: page content migrated from `res/pages/*.html`.
- `resources/views/downloads/index.blade.php`: downloads listing page.
- `public/res`: static images, CSS, JavaScript, and Lightbox assets copied from the old site.
- `downloads`: server-local downloadable files. Contents are ignored by git.

## Downloads

APK files live in the project-level `downloads/` directory, not under `public/`.

The `/downloads` route lists `*.apk` files from that directory, and `/downloads/{filename}` streams matching APK files through Laravel.

Git behavior:

- `downloads/.gitkeep` is tracked so the directory exists after clone.
- `downloads/*` is ignored so APK files are not committed.

## Legacy API

The members page still calls legacy endpoints under `public/api`:

- `check-auth.php`
- `send-otp.php`
- `verify-otp.php`
- `logout.php`

The private config file is intentionally ignored:

- `public/api/_config.php`

Keep that file on the server, but do not commit it.

## Local development setup

Get a working local instance from a fresh clone with the installer. It is
**self-contained** — no VPN/Tailscale, production access, or shared secrets
required; everything runs locally against a fresh SQLite database:

```bash
./install.sh
```

It checks prerequisites (PHP 8.3+, Composer, Node 20+), installs PHP + JS
dependencies, creates `.env` and an app key, sets up a fresh SQLite database,
runs migrations, links storage, and builds assets. Then start it:

```bash
php artisan serve     # http://127.0.0.1:8000
```

Mail uses the `log` driver locally, so any outbound mail is written to
`storage/logs/laravel.log` instead of being sent.

## Apache

The production vhost points `website.houstonheightslodge225.com` at Laravel's public directory.

Relevant Apache site files on `lodge`:

- `/etc/apache2/sites-available/website.houstonheightslodge225.com.conf`
- `/etc/apache2/sites-available/website.houstonheightslodge225.com-le-ssl.conf`

After changing Apache config:

```bash
sudo /usr/sbin/apache2ctl configtest
sudo systemctl reload apache2
```

Certbot manages the HTTPS certificate and renewal.

## Local Server Check

From `/var/www/website` on `lodge`:

```bash
php artisan serve --host=127.0.0.1 --port=8099
```

Then test sample routes:

```bash
curl -I http://127.0.0.1:8099/
curl -I http://127.0.0.1:8099/about
curl -I http://127.0.0.1:8099/downloads
```

## Tests

Run the Laravel test suite:

```bash
php artisan test
```

## Docker checks and deployments

The website no longer needs the `portal-staging` VirtualBox VM. Docker runs a
fresh Apache/PHP staging site for every check, with disposable SQLite data and
local mail logging. Production remains the existing Laravel/Apache installation
at `/var/www/website` on `lodge`.

On Linux, macOS, or Windows with WSL2, install Docker (Engine or Desktop), Git,
and Bash. Clone this repository, then run:

```bash
tools/deploy.sh --check
```

This builds PHP/Composer and Node dependencies in Docker, runs PHPUnit and Pint,
builds the frontend, starts Apache, and smoke-checks the public pages. Temporary
containers and tagged images are removed afterward; Docker build cache remains
for subsequent runs. No host PHP, Composer, or Node installation is required.

### Deploy from a laptop or another computer

Connect that computer to the lodge's Tailscale network and have an authorized
production SSH private key available locally. Start from a clean checkout of the
latest `origin/main`:

```bash
git fetch origin main
git switch --detach origin/main
tools/deploy.sh --production --identity ~/.ssh/houstonheightslodge225.pem
```

The default target is `admin@100.97.10.38`, the production server's Tailscale
address. Override with `--host HOST --user USER` or `PROD_HOST` / `PROD_USER`.
The private key is copied only into the temporary deployment client, never into
an image or the repository. The production SSH host key is pinned in
`docker/deploy/known_hosts`; a server-key change requires deliberate verification
and updating that file.

The command repeats the Docker checks before deploying. It ships the exact
frontend bundle that the temporary staging site served. Production takes an
exclusive file lock, rejects a dirty checkout or a commit that is no longer the
latest `origin/main`, then installs dependencies, assets, and migrations and
refreshes caches. It brings the application out of maintenance mode on failure,
but does not automatically roll back code or database migrations. The command
returns failure if deployment or the final HTTPS smoke checks fail.

`--check` is the default and never contacts production. `--artifact DIR` uses an
existing Vite bundle (with `manifest.json`) instead of rebuilding it; GitHub
Actions uses this to deploy its tested frontend artifact.

### Automatic deployments on bb

`bb` is the machine named `CrystalineShadow`. The repository-scoped GitHub
Actions runner `bb-website-docker` runs in a Docker container with
`--restart unless-stopped` and the `website-docker` label. Docker must start at
boot and bb must be awake and online for automatic deployments. Runner state
and automatic runner updates persist in the `bb-website-docker-state` volume.

The runner uses the host Docker socket and is reserved for trusted `main`
deployments. Pull-request tests run on GitHub-hosted machines, including the
Docker staging checks. Do not assign untrusted PR jobs to this runner.

To install the runner on a Linux host with Docker and an authenticated GitHub
CLI account that can administer this repository:

```bash
tools/install-runner.sh                  # default name: bb-website-docker
# A different permanent runner needs a unique name:
# tools/install-runner.sh laptop-website-docker
```

A laptop does **not** need to register a runner to use `tools/deploy.sh` manually.
The installer verifies GitHub's published runner archive digest, registers it,
and starts the container. Re-running it preserves existing registration and
leaves an existing container running.

```bash
docker logs --tail 50 bb-website-docker
docker inspect --format '{{.HostConfig.RestartPolicy.Name}}' bb-website-docker
gh api repos/anolis/houstonheightslodge-website/actions/runners
```

Merge a PR into `main` to deploy automatically. The Deploy workflow runs CI on
GitHub, then invokes the same portable command on the Docker runner with the
existing `PROD_SSH_KEY`, `PROD_HOST`, and `PROD_USER` repository secrets. It can
also be started with **Run workflow** on `main`. A server-side lock serializes
manual deployments against automatic ones.

The old `vbox-website` runner is no longer selected by these workflows. The
shared VM also serves the portal repository; retiring the website runner does
not migrate or shut down the portal's services.

### Development checks for deployment tooling

```bash
shellcheck tools/*.sh docker/deploy/*.sh docker/runner/entrypoint.sh docker/staging-entrypoint.sh
python3 tools/test-deploy.py
tools/deploy.sh --check
```

The deployment safety tests simulate dirty and outdated production checkouts,
a dependency-install failure, and concurrent deploys without touching production.

## Facebook event calendar

The native calendar uses FullCalendar (month/list views) with a server-side feed
from the lodge's Facebook Page. Event titles, dates, descriptions, locations,
images, and Facebook RSVP links come from Facebook. Editing events stays on
Facebook; there is no second calendar to maintain.

Configure these in the production `.env`, never in Git:

- `FACEBOOK_APP_ID=965766727408385`
- `FACEBOOK_PAGE_ID=160769860639621`: Odd Fellows Lodge #225.
- `FACEBOOK_PAGE_ACCESS_TOKEN`: a Page token issued through the lodge's Meta app,
  with access to read that Page's events. The app currently has standard access
  to `pages_read_engagement` and `pages_show_list`. Both scopes and the v24.0
  event fields were verified against the lodge Page. Use a long-lived Page token;
  check its expiry in Meta’s Access Token Debugger before activation.
- `FACEBOOK_GRAPH_VERSION=v24.0`

Run `php artisan facebook:sync-events` and verify `/events/feed` after enabling
`FACEBOOK_CALENDAR_ENABLED=true`. Run `php artisan config:clear` after editing
configuration. The existing widget remains selected while the flag is false,
so deployment alone cannot replace working events with an unconfigured feed.

Laravel schedules a refresh every 15 minutes. Production must run its scheduler:

```cron
* * * * * cd /var/www/website && php artisan schedule:run >> /var/www/website/storage/logs/scheduler.log 2>&1
```

A complete successful sync atomically replaces
`storage/app/private/facebook-events.json`. This file survives deployments and
is not publicly downloadable. Failed or partial syncs retain the last complete
snapshot, and the calendar marks data older than a day as potentially outdated.
A successful empty feed removes events that Facebook has deleted or cancelled.
The public JSON feed contains only normalized event data and a refresh timestamp;
no access tokens or app secrets are exposed.

## Git Ignore Policy

Do not commit:

- `.env`
- `vendor/`
- `database/database.sqlite`
- runtime files under `storage/` and `bootstrap/cache/`
- APK files under `downloads/`
- `public/api/_config.php`
