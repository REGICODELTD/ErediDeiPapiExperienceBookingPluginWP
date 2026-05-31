# CLAUDE.md — Project Rules & Workflow

## ⚠️ MANDATORY GIT WORKFLOW — FULLY AUTOMATIC, NO EXCEPTIONS

Multiple Claude instances may run simultaneously on this machine.
Every instance MUST follow this workflow automatically without asking the user anything.
The user should not have to do anything manually — everything is handled by Claude.

---

## STEP 1: Create an Isolated Worktree (NEVER use git checkout)

**NEVER use `git checkout` to switch branches in the main repo folder.**
Always use `git worktree` so each instance works in its own isolated directory.

```bash
BRANCH="<type>/<scope>-<short-description>"
WORKTREE_DIR="../worktrees/$(echo $BRANCH | tr '/' '-')"

cd <main-repo-directory>
git fetch origin 2>/dev/null || true
git worktree add -b "$BRANCH" "$WORKTREE_DIR" main
cd "$WORKTREE_DIR"
```

Then **do ALL work inside `$WORKTREE_DIR`** — never touch the main repo folder directly.

Branch naming examples:
- `feat/admin-news-crud`
- `feat/basilica-homepage-hero`
- `fix/admin-sidebar-active-state`
- `style/admin-sidebar-rebrand`
- `chore/config-subdomain-routing`
- `migration/db-create-news-table`
- `test/admin-news-crud-tests`
- `refactor/shared-seo-component`

---

## STEP 2: Do All Work Inside the Worktree

- `cd` into the worktree directory.
- Make all edits, create files, modify code there.
- Commit frequently using the commit format below.
- Each commit must leave the project in a working state (no half-done migrations without models, etc.).
- If fixing a typo or small mistake from the immediately previous commit, use `git commit --amend` instead of a new commit.

---

## STEP 3: Merge Back into Main (WITH LOCKFILE)

When the work is done, merge back into main using a lockfile to prevent race conditions between instances.

```bash
REPO_DIR="<main-repo-directory>"
LOCKFILE="$REPO_DIR/.git/merge.lock"
LOCK_TIMEOUT=120  # seconds

# Wait for lock (another instance might be merging)
WAITED=0
while [ -f "$LOCKFILE" ]; do
    sleep 2
    WAITED=$((WAITED + 2))
    if [ "$WAITED" -ge "$LOCK_TIMEOUT" ]; then
        echo "ERROR: Merge lock held for over ${LOCK_TIMEOUT}s. Removing stale lock."
        rm -f "$LOCKFILE"
        break
    fi
done

# Acquire lock
echo "$$" > "$LOCKFILE"

# Merge
cd "$REPO_DIR"
git merge "$BRANCH" --no-ff -m "merge: $BRANCH into main"
git push origin main 2>/dev/null || true

# Release lock
rm -f "$LOCKFILE"

# Clean up worktree
git worktree remove "$WORKTREE_DIR" --force 2>/dev/null || true
git branch -d "$BRANCH" 2>/dev/null || true
```

If there are merge conflicts:
1. Resolve them automatically if possible.
2. If not possible, leave the branch unmerged, clean up the lock, and tell the user: *"Branch `<branch>` has conflicts with main and needs manual resolution."*
3. Always release the lockfile, even on failure.

If there is no remote configured, skip `git push` but still do the merge locally.

---

## ⚠️ MANDATORY COMMIT MESSAGE FORMAT

Every commit MUST follow this format:

```
<type>(<scope>): <description>
```

### Types:

| Type          | When to Use                                        |
|---------------|----------------------------------------------------|
| `feat`        | New feature or functionality                       |
| `fix`         | Bug fix                                            |
| `style`       | CSS, formatting, UI polish — no logic changes      |
| `refactor`    | Code restructuring — no new features or fixes      |
| `docs`        | Documentation only                                 |
| `chore`       | Build config, deps, tooling, maintenance           |
| `test`        | Adding or updating tests                           |
| `perf`        | Performance improvement                            |
| `ci`          | CI/CD pipeline changes                             |
| `migration`   | Database migrations                                |
| `revert`      | Reverting a previous commit                        |

### Scopes:

The scope is the module, area, or layer of the project:

| Scope         | What It Covers                                     |
|---------------|---------------------------------------------------|
| `admin`       | Admin panel — routes, controllers, views, logic    |
| `basilica`    | Basilica public site — pages, sections, layouts    |
| `shared`      | Shared components, middleware, helpers, traits      |
| `config`      | Configuration files, env, routing, services        |
| `db`          | Database — migrations, seeders, factories          |
| `api`         | API endpoints, resources, transformers             |
| `auth`        | Authentication & authorization                     |
| `deps`        | Dependencies & packages                           |

> Add new scopes as the project grows. Keep them short, lowercase, and consistent.

### Real Examples (follow this style exactly):

```
feat(admin): add news CRUD with category filter
feat(basilica): implement homepage hero section
feat(shared): create SEO meta Blade component
fix(admin): fix sidebar active state on nested routes
style(admin): rebrand sidebar to San Francesco palette
chore(config): add subdomain routing and site config
migration(db): create news table
test(admin): add news CRUD feature tests
test(shared): add DetectSite middleware tests
refactor(shared): extract image upload logic into trait
perf(basilica): lazy load gallery images on scroll
docs(readme): add local development setup instructions
fix(api): handle null response on empty news collection
style(basilica): adjust hero section spacing on mobile
chore(deps): upgrade Laravel to v11.x
migration(db): add slug and featured columns to news table
feat(admin): add drag-and-drop image reordering
feat(auth): implement role-based admin permissions
ci(deploy): add GitHub Actions workflow for staging
```

### Rules:

- Description is lowercase, imperative mood ("add" not "added" or "adds").
- No period at the end.
- Keep it under 72 characters.
- One logical change = one commit. Split if needed.

---

## FULL WORKFLOW EXAMPLE

User says: *"Build the news management section in admin with categories"*

Claude automatically executes everything — user does nothing:

```bash
# ──────────────────────────────────────────────
# STEP 1: Create isolated worktree
# ──────────────────────────────────────────────
BRANCH="feat/admin-news-management"
WORKTREE_DIR="../worktrees/feat-admin-news-management"
REPO_DIR="/path/to/main-repo"

cd "$REPO_DIR"
git fetch origin 2>/dev/null || true
git worktree add -b "$BRANCH" "$WORKTREE_DIR" main
cd "$WORKTREE_DIR"

# ──────────────────────────────────────────────
# STEP 2: Work — split into logical commits
# ──────────────────────────────────────────────
# ... create migration file ...
git add database/migrations/xxxx_create_news_table.php
git commit -m "migration(db): create news and categories tables"

# ... create models ...
git add app/Models/News.php app/Models/Category.php
git commit -m "feat(admin): add News and Category models with relationships"

# ... create controller ...
git add app/Http/Controllers/Admin/NewsController.php
git commit -m "feat(admin): add news CRUD controller with category filter"

# ... create views ...
git add resources/views/admin/news/
git commit -m "feat(admin): add news Blade views with form and listing"

# ... update routes ...
git add routes/admin.php
git commit -m "chore(config): register news resource routes in admin"

# ... create tests ...
git add tests/Feature/Admin/NewsTest.php
git commit -m "test(admin): add news CRUD feature tests"

# ──────────────────────────────────────────────
# STEP 3: Lockfile merge back into main
# ──────────────────────────────────────────────
LOCKFILE="$REPO_DIR/.git/merge.lock"

WAITED=0
while [ -f "$LOCKFILE" ]; do
    sleep 2
    WAITED=$((WAITED + 2))
    [ "$WAITED" -ge 120 ] && rm -f "$LOCKFILE" && break
done

echo "$$" > "$LOCKFILE"

cd "$REPO_DIR"
git merge "$BRANCH" --no-ff -m "merge: feat/admin-news-management into main"
git push origin main 2>/dev/null || true

rm -f "$LOCKFILE"
git worktree remove "$WORKTREE_DIR" --force 2>/dev/null || true
git branch -d "$BRANCH" 2>/dev/null || true
```

User does nothing. Claude reports: *"Done. News management with categories is now on main."*

---

## MULTI-INSTANCE SAFETY RULES

- **NEVER use `git checkout` in the main repo folder.** Always use `git worktree`.
- **NEVER work in the main repo folder.** Always `cd` into your worktree.
- **ALWAYS use the lockfile** when merging into main. Always release it, even on failure.
- **Each task gets its own worktree and its own branch.** No sharing.
- **If the user asks for two unrelated things in one message** (e.g., "add news CRUD and fix the sidebar"), create two separate branches with two separate worktrees, one after the other. Merge them one at a time using the lockfile.
- **If merge conflicts occur**, try to resolve automatically. If impossible, release the lock, leave the branch unmerged, and notify the user.

---

## ADDITIONAL RULES

- **NEVER commit directly to `main`.** Always branch first.
- **NEVER use generic messages** like "update files", "fix stuff", "wip", "changes".
- **NEVER bundle unrelated changes** in a single commit.
- **ALWAYS split commits by logical unit** — model, controller, views, routes, tests, migrations each get their own commit when they represent distinct changes.
- **Each commit must be functional** — don't commit a migration without its model. If they depend on each other, commit them together.
- **ALWAYS run this workflow automatically** — do not ask the user for permission. Just do it.
- If the repo is not initialized, run `git init` and create an initial commit first:
  ```bash
  git init
  git add -A
  git commit -m "chore(config): initial project setup"
  ```
- After everything is done, report to the user what was completed and which branch was merged.