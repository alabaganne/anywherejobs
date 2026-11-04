# Naming

The owner picked **Sudo Jobs** on 2 November 2026 and the site was renamed the same day. Later that day the owner decided to choose another name, so the name and the logo were each moved to one place first. On 4 November 2026 the owner chose **Openworld Jobs**; the rename is planned below and not done yet, so the site still says Sudo Jobs.

## Plan: rename to Openworld Jobs

**Why:** it says global at once, echoes the site's "Open worldwide" filter, works for students and professionals, internships and jobs, and reads the same in French.

**Domains** (registry lookups on 2 November 2026; check again before buying): openworldjobs.com, openworldjobs.dev, openworldjobs.app and openworldjobs.io were all unregistered. Buy the .com first, and the .dev or .app if you want a backup.

**Similar names:** no job board called Openworld or Open World Jobs turned up. Others using the words: the Open World Program (a US congressional exchange programme), a small "open world" company listed on Indeed India, and Open World School. That search covered US results only; run a trademark search in the EU and the US before spending on the brand.

**Watch out for:** "open world" is a common video game term, and the full name is long, so plan a short form for tight spaces.

### Decide first

1. **Written form:** "Openworld Jobs" (one word, as chosen), "OpenWorld Jobs" or "Open World Jobs". The domain and handles are `openworldjobs` either way.
2. **Logo:** keep the `>_` terminal tile (it says IT, not "world"), or draw a new one that hints at the world, like a globe or a prompt over a globe.
3. **Sender address:** `openworldjobs@alabaganne.com` until the new domain is verified in Resend, then an address on the new domain.

### Steps in the code

1. Set `'name' => env('APP_NAME', 'Openworld Jobs')` in `config/app.php`. Pages, titles, emails, the manifest, structured data, AI prompts and blog posts follow it; the bot name and the reserved username follow it too.
2. If the logo changes, replace `public/brand/logo.svg`. Then run `php artisan brand:images` to rebuild the icons and the share image.
3. Change `MAIL_FROM_ADDRESS` in `.env.example`, your `.env` and the docs.
4. Replace "Sudo Jobs" in the docs: `AGENTS.md` (Name, Logo and Email rules), `README.md`, `design/`, `LANDING_PAGE_DESIGN.md`, `SEO.md`, `DEPLOY.md` and `VPS_DEPLOYMENT.md` (including the `sudojobs` service user, paths and Supervisor names), `FUTURE_PLANS.md`, `TODOS.md` and the business notes. Leave the history parts of this file as they are.
5. Run `php artisan test` and `npm run build`, then check the home page, a listing, sign-in, the workspace sidebar and one email.

Nothing else in the code spells the name out, and the session cookie and cache prefix don't follow it, so nobody is signed out.

### Outside the code (owner)

- Buy the domain, point DNS at the server, set `APP_URL`, nginx `server_name` and the TLS certificate.
- Verify the domain in Resend and switch `MAIL_FROM_ADDRESS`.
- Google sign-in and Gmail: rename the app on the OAuth consent screen and add the new redirect URIs.
- Payments: set the store name and webhook URLs in Lemon Squeezy, Konnect and Flouci.
- Add the domain to Google Search Console and submit the sitemap.
- Claim `openworldjobs` handles where you plan to post (LinkedIn, X, GitHub).
- Optional: rename the project folder and the GitHub repository.

## Changing the name

1. Set the new name in `config/app.php` (`'name' => env('APP_NAME', '...')`).
2. Run `php artisan brand:images` so the share image shows it.
3. Update the docs that name the product (`AGENTS.md`, `README.md` and the others in the docs table).
4. Outside the code: the domain and `APP_URL`, the sender address (`MAIL_FROM_ADDRESS`), the Google sign-in app name, the payment store names, and social handles.

Code and copy never spell the name out, so nothing else changes. See `public/brand/README.md` for the logo.

## Why change

- "Nextward" doesn't say tech or jobs, and "ward" makes people think of hospitals.
- People don't hear it clearly: the logo packs already spell it "Nextwand".
- nextward.com is parked for sale and nextward.app is taken.

## Sudo Jobs

Anyone in IT knows `sudo`: developers, sysadmins, IT support, DevOps and security people. The name says "IT jobs" at once, it's short, and it reads the same in French.

- **Written form:** "Sudo Jobs" in copy and page titles; `sudojobs` in domains, handles and code.
- **Taglines to try:** "Root access to IT jobs", "sudo apply".
- **Weak spots:** design students may not get the joke; "Jobs" doesn't say internships (normal for job boards); the playful name means the rest of the site should look serious so employers trust it.
- **Similar names:** no site called Sudo Jobs was found. The closest is Sudo Inc, a small tech recruiter in Taipei with a student job board, running since 2014 ([Dealroom](https://app.dealroom.co/companies/sudo_inc)). Run a trademark search in the EU and the US before spending on the brand.

## Domains for Sudo Jobs

Checked on 2 November 2026 with each registry's lookup. A check is not a hold: register the ones you want soon.

| Domain | Status | Note |
|---|---|---|
| sudojobs.dev | Unregistered | First choice. Browsers always load .dev over HTTPS. |
| sudojobs.app | Unregistered | Backup, worth buying with .dev |
| sudojobs.careers | Unregistered | |
| getsudojobs.com | Unregistered | |
| sudo-jobs.com | Unregistered | |
| sudojobshq.com | Unregistered | |
| sudojobs.co | Not confirmed | No DNS records, registry not checked |
| sudojobs.com | Registered | Held since July 2019, renewed to July 2027, no website answers. You could ask the owner. |
| sudojobs.io | Registered | |
| sudojob.com | Registered | |
| sudo.dev | Registered | |

## Other names considered

"Unregistered" means the registry lookup found no registration on 2 November 2026.

### Round 1

| Name | Idea | Unregistered | Notes |
|---|---|---|---|
| Roleport | role + port: roles across borders | roleport.dev, roleport.app, roleport.io | roleport.com is parked |
| Devbound | "bound for" a dev career | devbound.dev, devbound.io | "dev" sounds like coders only |
| Techbound | same idea, wider | techbound.dev | more generic |
| Deployee | deploy + employee | deployee.dev, deployee.io | could sound like a staffing agency |
| Rolevia | role + via (a route) | rolevia.dev, rolevia.io | the least techy |

Dropped: Hirebound (a company already uses it), Itinera ("IT" hidden in "itinerary", but its .com, .dev, .io, .co and .app are taken), Hirestack and Careerstack (.com taken). Every short .com tried was taken.

### Round 2

| Name | Idea | Unregistered | Notes |
|---|---|---|---|
| Fennec | the desert fox: quick, big ears, travels far; a ready-made mascot | fennec.careers, fennecjobs.com, joinfennec.com, fennecjobs.dev | fennec.dev and getfennec.com are taken; once the code name for Firefox on Android |
| Cairn | a stack of stones marking a trail, same word in French | cairnjobs.com | Cairn.info is a well-known French academic site |
| Envol | French for "taking flight" | envoljobs.com, envol.dev | means nothing in English; envol.app is taken |
| Sudo Jobs | a developer in-joke | see above | picked |

Dropped because their good domains are taken: Deployed (deployed.dev), Landed, Escale (escale.dev, escale.app), Uplink (uplink.app), Waymark (waymark.io), Elan.

### Round 3

After Sudo Jobs was set aside. Clashes come from a web search of US results; no trademark databases were checked.

| Name | Idea | Unregistered | Notes |
|---|---|---|---|
| Octet Jobs | "octet" is byte in French and 8 bits in networking, spelled the same in both languages | octetjobs.com, .dev, .app, .io; joinoctet.com | English speakers may think of a music group; Octet Design Studio is a UI/UX agency in India |
| Newline Jobs | Enter in a terminal gives a new line; a new line on your CV | newlinejobs.com, .dev, .app, .io | close to newline.co, a developer course company |
| Await Jobs | `async/await`, and "your next role awaits" | awaitjobs.com, .dev, .app, .io | only developers get the joke; an AWAIT.AI trademark exists for AI software |
| Tilde | `~`, the home folder in every terminal prompt | tildehq.com, .dev, .app, .io; tildejobs.dev | many companies already use "Tilde"; says nothing about jobs |
| Trova | from "find" in Italian, close to French "trouver" | trova.dev; trovajobs.dev | no IT feel; trova.com is taken |
| Openworld Jobs | global, echoes the "Open worldwide" filter | openworldjobs.com, .dev, .app, .io | chosen, see the plan above |
| Caret Jobs | the text cursor mark | caretjobs.com, .dev, .app, .io | crowded name, easy to misspell |
| Ping Jobs | `ping`, and "ping me" | pingjobs.dev, .app, .io | PingITJobs, a UK IT job board, exists |
| Init Jobs | `init`, the first process that starts a computer | initjobs.dev, .app, .io | INIT SE and ]init[ AG hire a lot under that name |
| Tera Jobs | terabyte, close to "terra" | terajobs.dev, .app, .io | sounds like TERRAJOBS, an HR brand |

Dropped: Grep Jobs (GrepJob.com is a job board for software engineers), Lynx Jobs (many recruiters use it), Metier (a French staffing agency), Async Jobs (remote-only job boards with that name exist), Batch Jobs (close to Batch, a Paris software company), Byte Jobs (generic), CLI Jobs (hard to say), and Cronjobs, Devboard, Stackjobs and Firstcommit (every domain taken).

## Rename to Sudo Jobs (done)

Done on 2 November 2026. "Nextward" appeared 697 times in 214 files. It now stays only in migrations, git history, the task briefs kept as history (`LANDING_PAGE_IMPLEMENTATION_PROMPT.md`, `OPPORTUNITY_FINDER_IMPLEMENTATION_PLAN.md`, `PUBLIC_PAGES_REDESIGN_PROMPT.md`), `output/`, the GitHub repository link, and where it keeps old things working (see below).

### Settled

- **Written form:** "Sudo Jobs" in copy and titles, `sudojobs` for domains, handles and ids that need the name.
- **Logo:** a terminal window with a `>_` prompt in white on a cobalt tile (`public/brand/logo.svg`), drawn after the owner's own artwork was tried and dropped. The old colourful symbol, the blue N mark, the Nextward wordmark and the misspelled `assets/nextwand-logo/` pack were removed. `php artisan brand:images` makes the PNG icons and the share image from it.
- **Sender:** `sudojobs@alabaganne.com` until a Sudo Jobs domain is verified in Resend.

### Neutral names in code

This is the third name, so code ids that carried the brand became neutral and a later rename only touches the copy:

| Before | Now |
|---|---|
| `config/nextward_public.php`, `config('nextward_public…')` | `config/site.php`, `config('site…')` |
| `NEXTWARD_SOCIAL_LINKS` | `SITE_SOCIAL_LINKS` |
| mail theme `nextward` | `site` (`resources/views/vendor/mail/html/themes/site.css`) |
| `public/brand/nextward/`, `Nextward-colorful-symbol.png` | `public/brand/`, `symbol.png` |
| `public/nextward-assets/` | `public/site-assets/` |
| `nextward:make-admin` | `app:make-admin` |
| `nextward.activity`, `nextward.cover-letter.*`, `nextward.kit-cv.*` (browser storage) | `site.activity`, `site.cover-letter.*`, `site.kit-cv.*` |
| `nextward:indexing:*` cache keys | `indexing:*` |
| `.cursor/rules/nextward.mdc` | `.cursor/rules/project.mdc` |

The importer's user agent is now `SudoJobsBot/1.0`, `package.json` is named `sudojobs`, and `sudojobs` replaced `nextward` among the reserved usernames.

### Kept on purpose

- `/brand/nextward/*` and `/nextward-assets/*` redirect to the new paths, so old emails and shared links still show their images.
- The old `nextward.activity` key is read once and moved, so guests keep their opened and saved roles.
- Sites' robots.txt rules for `nextwardbot` are still honoured.

### Servers set up before the rename

No server has run the site yet (the VPS guides were never applied), so nothing needs moving. On a machine set up earlier, rename `NEXTWARD_SOCIAL_LINKS` to `SITE_SOCIAL_LINKS` in `.env` and remove `APP_NAME` and `MAIL_FROM_NAME` from it. The session cookie (`site-session`) and cache prefix (`site-cache-`) no longer follow the name.

### Still to do (owner)

- Buy the domain (sudojobs.dev, and sudojobs.app as a backup), point DNS at the server, set `APP_URL`, nginx `server_name` and the TLS certificate.
- Verify the domain in Resend and switch `MAIL_FROM_ADDRESS`.
- Google sign-in and Gmail: rename the app on the OAuth consent screen and add the new redirect URIs.
- Payments: update the store name and webhook URLs in Lemon Squeezy, Konnect and Flouci.
- Add the new domain to Google Search Console and submit the sitemap.
- Claim `sudojobs` handles where you plan to post (LinkedIn, X, GitHub).
- Run a trademark search in the EU and the US.
- Optional: rename the project folder and the GitHub repository.
