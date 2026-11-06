# Naming

The product is **Anywhere Jobs**, chosen by the owner on 6 November 2026 and renamed the same day. It was Sudo Jobs before (2 November); Openworld Jobs was picked on 4 November and dropped before any rename. The sections after "Changing the name" are kept as history.

## Anywhere Jobs

**Why:** it says at once that people anywhere can find work, it matches the listings people in any country can apply to (`applicant_scope` `anywhere`) and the Open worldwide filter, it fits students and professionals, internships and jobs, and French speakers read it easily.

- **Written form:** "Anywhere Jobs" in copy and titles; `anywherejobs` in domains, handles and the few ids that follow the name (the importer's `AnywhereJobsBot/1.0`, the reserved username `anywherejobs`).
- **Logo:** the name in a baseball-style script with a swoosh, and a script A icon (`public/brand/README.md`).
- **Sender:** `anywherejobs@alabaganne.com` until an Anywhere Jobs domain is verified in Resend.

**Domains** (registry lookups on 6 November 2026; a check is not a hold, so register the ones you want soon):

| Domain | Status | Note |
|---|---|---|
| anywherejobs.io | Unregistered | |
| anywherejobs.dev | Unregistered | Browsers always load .dev over HTTPS |
| anywherejobs.app | Unregistered | |
| anywherejobs.net | Unregistered | |
| anywherejobs.com | Registered | Live site of AnywhereJobs, a recruiting firm in India that trains workers for jobs abroad; held since 2014, renewed to 2029 |
| getanywherejobs.com, anywhere-jobs.com, anywherejobshq.com | Unregistered | .com options |
| anywhere.jobs, anywherejobs.co, .ai, .tech, .careers | No DNS records | Registry not checked |

**Watch out for:** the AnywhereJobs firm above uses the same name for hiring abroad (trades and healthcare, not IT). Run a trademark search in the EU, the US and India before spending on the brand. "Anywhere" is common in remote job board names, so search traffic has to come from "IT jobs" words, not the name alone.

## Rename to Anywhere Jobs (done)

Done on 6 November 2026:

- `config/app.php` holds the new name, so pages, titles, emails, the manifest, structured data, AI prompts, the bot name and the reserved username follow it.
- New logo files in `public/brand`. `BrandLogo.vue` has a `wordmark` option, and the header, footer, sidebar, sign-in pages, error page, unsubscribe page, email header and share image show the wordmark instead of the icon and the name as text. `brand:images` now also writes `wordmark.png` for emails and `public/favicon.ico`.
- Sender `anywherejobs@alabaganne.com` in `.env.example` and the docs.
- Docs: "Sudo Jobs" and `sudojobs` are replaced everywhere except the history parts of this file; the VPS guides use the `anywherejobs` service user, paths and Supervisor names.
- On a machine set up earlier, remove `APP_NAME` and `MAIL_FROM_NAME` from `.env`, because they override the name.

### Still to do (owner)

- Buy the domain (anywherejobs.io or .dev as the main one, the others as backups), point DNS at the server, set `APP_URL`, nginx `server_name` and the TLS certificate.
- Verify the domain in Resend and switch `MAIL_FROM_ADDRESS`.
- Google sign-in and Gmail: rename the app on the OAuth consent screen and add the new redirect URIs.
- Payments: set the store name and webhook URLs in Lemon Squeezy, Konnect and Flouci.
- Add the domain to Google Search Console and submit the sitemap.
- Claim `anywherejobs` handles where you plan to post (LinkedIn, X, GitHub).
- Run a trademark search in the EU, the US and India.
- Optional: rename the project folder and the GitHub repository.

## Openworld Jobs (dropped)

Picked on 4 November 2026 because it says global at once and echoes the "Open worldwide" filter; openworldjobs.com, .dev, .app, .io and .net were unregistered (.net checked on 6 November). No job board used the name. Dropped on 6 November for Anywhere Jobs, before any rename: "open world" is a common video game term and the name is long.

## Changing the name

1. Set the new name in `config/app.php` (`'name' => env('APP_NAME', '...')`).
2. Redraw `public/brand/wordmark.svg` and `wordmark-white.svg`, which spell the name, then run `php artisan brand:images`.
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
| Openworld Jobs | global, echoes the "Open worldwide" filter | openworldjobs.com, .dev, .app, .io | picked on 4 November, then dropped (see above) |
| Caret Jobs | the text cursor mark | caretjobs.com, .dev, .app, .io | crowded name, easy to misspell |
| Ping Jobs | `ping`, and "ping me" | pingjobs.dev, .app, .io | PingITJobs, a UK IT job board, exists |
| Init Jobs | `init`, the first process that starts a computer | initjobs.dev, .app, .io | INIT SE and ]init[ AG hire a lot under that name |
| Tera Jobs | terabyte, close to "terra" | terajobs.dev, .app, .io | sounds like TERRAJOBS, an HR brand |

Dropped: Grep Jobs (GrepJob.com is a job board for software engineers), Lynx Jobs (many recruiters use it), Metier (a French staffing agency), Async Jobs (remote-only job boards with that name exist), Batch Jobs (close to Batch, a Paris software company), Byte Jobs (generic), CLI Jobs (hard to say), and Cronjobs, Devboard, Stackjobs and Firstcommit (every domain taken).

### Round 4

Checked on 6 November 2026. .com, .net and .io come from registry lookups; "no DNS" means a .dev or .app with no DNS records, which is likely but not sure to be free.

| Name | Idea | Free | Notes |
|---|---|---|---|
| Packet Jobs | data packets reach any place on a network | packetjobs.com, .io, .net; .dev and .app no DNS | Packet was a cloud company Equinix bought |
| Hello World Jobs | every coder's first program, and "world" says global | helloworldjobs.io; .dev and .app no DNS | .com and .net taken; the phrase is too common to rank on |
| Next Hop | the next hop is where a packet goes next | nexthopjobs.com, .net; .dev no DNS | nexthop.dev, .app and .net taken; a networking startup is called Nexthop AI |
| Polyglot Jobs | a developer who knows many languages, on a site in two | polyglotjobs.com, .io, .net; .dev and .app no DNS | may sound like translator jobs |
| Exit Zero | `exit 0` means a command worked | exitzerojobs.com, .net | exitzero.dev, .app and .net taken; "exit" can sound like quitting |
| Root Jobs | root access, and roots that grow | rootjobs.net; .dev and .app no DNS | .com taken; "root" is rude slang in Australia |
| Fork Jobs | fork a repo to take your own path | forkjobs.net; .dev and .app no DNS | .com taken |
| Worldcode Jobs | code for the whole world | worldcodejobs.com, .net | worldcode.dev taken; flat |

Dropped: Prompt Jobs, Relocode, Devpassport, Hop Jobs and Boot Jobs (.com taken, or the wrong idea).

### Round 5

Checked the same way on 6 November 2026.

| Name | Idea | Free | Notes |
|---|---|---|---|
| Anywhere Jobs | people in any country can apply | see above | chosen |
| Jobyte | job + byte | jobyte.io, .net, getjobyte.com, joinjobyte.com; .dev and .app no DNS | jobyte.com is for sale on Afternic |
| Tern Jobs | the Arctic tern flies farther than any animal; "your turn" | ternjobs.com, .io, .net; .dev no DNS | tern.dev taken; few people know the bird; Tern is a bike brand |
| Next Stage Jobs | "stage" is an internship in French and a career step in English | nextstagejobs.com, .net; .dev no DNS | long |
| Alt Tab Jobs | Alt+Tab switches windows, like switching jobs | alttabjobs.com, .net; .dev no DNS | alttab.dev taken; AltTab is a known Mac app |
| Terminal Jobs | the terminal logo, and the airport terminal you leave from | terminaljobs.io, .net; .dev no DNS | .com taken; Terminal is a remote hiring company |
| Git Hired | "get hired" | githired.io | .com, .dev, .app and .net taken |
| Globyte | globe + byte | globyte.io, .net; .app no DNS | .com and .dev taken |
| Upstream Jobs | the main open source project, and moving up | upstreamjobs.io, .net; .dev no DNS | .com taken; common word |

Dropped: Pivot Jobs and Hexa Jobs (.com taken, weak idea).

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
