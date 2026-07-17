![Banner](./public/images/wind4life_banner.png)

# Candidate Exercise — Wind4Life (Laravel)

## Table of Contents
1. [Overview](#overview)
2. [Exercise](#exercise)
3. [Follow-up interview](#follow-up-interview)
4. [Expectations](#expectations)
5. [Deliverables](#deliverables)
6. [The application](#the-application)
7. [Getting started](#getting-started)
   - [Prerequisites by OS](#1-prerequisites-by-os)
   - [Install project dependencies](#2-install-project-dependencies)
   - [Boot the app](#3-boot-the-app)
   - [Tasks](#tasks)

# Overview

Wind4Life is a non-profit organization providing real-time wind data around the world.

For this exercise, we would like you to review and work from this existing codebase. The purpose of the exercise is to understand how you approach an unfamiliar application, how you make a scoped change in an existing system, how thoroughly you uncover problems hidden in code you didn't write, and how you reason about the technical future of a product.

This particular codebase is a **Laravel 11** port of the Wind4Life backend. It uses Sanctum for API authentication, MySQL 8 for storage, Redis for caching, and PHPUnit for the test suite — orchestrated locally with Laravel Sail. Code style is PSR-12 with tabs, enforced by PHP_CodeSniffer (`phpcs.xml.dist`).

# Exercise

This exercise has two parts.

## Part 1 — Implement a small feature

We would like you to add an **export capability** to the application.

The export should support:

- JSON
- CSV

We are intentionally leaving this request somewhat open-ended. Part of the exercise is to see how you interpret the requirement, how you navigate the existing Laravel application, and how you make pragmatic implementation choices within the current structure of the project.

We are not looking for a perfect or exhaustive solution. We are more interested in:

- how you understand the codebase,
- where you decide to introduce the change,
- how you scope the work,
- how you justify your choices,
- and how you validate the implementation.

Please include:

- your code changes,
- any tests you consider appropriate,
- and any brief notes you feel are useful to explain your approach.

## How to submit

Fork this repo, work on a branch in your fork, and open the PR **inside your own fork** (your branch into your fork's `main`) — not against this repo. Share the PR link as your deliverable.

## Part 2 — Find the hidden issues, and report on them

Heads up: this codebase contains a number of **deliberately introduced, non-obvious issues** — spanning correctness, security, performance, data integrity, architecture, and developer experience. Some are subtle and easy to miss on a quick read.

Review the repository as if you had just inherited this application and were responsible for its future, and **find as many of these issues as you can — the more, the better.**

Then write a **report** of what you found. For each issue, please cover:

- **What it is** — and where (file, endpoint, or query).
- **Why it matters** — the real-world impact or risk.
- **The fix** — what you did to fix it, or how you would fix it. A code fix is welcome but not required for every finding; a clear explanation is what counts.

We are interested in how deeply you read the code and how clearly you reason about impact and trade-offs. There is no fixed format for the report — clarity matters more than length. We will use it to anchor the follow-up discussion.

# Follow-up interview

After the exercise, you will have a follow-up discussion with engineers from our team.

This conversation may cover:

- your implementation choices,
- your understanding of the repository,
- your technical assessment of the codebase,
- security and architectural considerations,
- tradeoffs you identified,
- how you would evolve the application over time,
- how you would think about scaling and supporting future features.

The objective is not only to review the code you produced, but also to understand how you analyze an existing system, make engineering decisions, and communicate your reasoning.

# Expectations

For **Part 1**, we are not looking for a large redesign or an overengineered solution. Keep the implementation focused, make reasonable assumptions, work with the existing structure of the project, and be ready to explain your decisions and tradeoffs. A simple, well-judged solution is generally stronger than a broad or overly ambitious one.

For **Part 2**, the opposite mindset applies — be thorough. The more genuine issues you surface, and the more clearly you explain their impact and fix, the better. Don't be discouraged if you suspect you haven't found them all; show us how you hunt.

# Deliverables

Open a Pull Request in your fork (see [How to submit](#how-to-submit)) containing:

- your **Part 1 implementation** (the export) and any tests,
- a **written report** (e.g. a `REPORT.md` in the repo) covering every issue you found in Part 2 — the what, why, and fix for each,
- and any notes you would like to bring into the follow-up discussion.

Share the PR link as your submission.

# The application

The currently implemented features:

- Users can manage anemometers (CRUD).
- Users can submit wind speed readings (in knots) for a given anemometer at a given time.
- A paginated endpoint listing anemometers with their 5 latest readings and weekly / daily average speeds.
- Users can see the daily / weekly mean wind speed for each anemometer.
- Users can list all paginated readings for a given anemometer.
- Users can filter anemometer readings by a given set of tags.
- Authentication (via Sanctum personal access tokens) is required to call the API.

# Getting started

You're going to need **Docker** to run this project. The stack is orchestrated with [Laravel Sail](https://laravel.com/docs/11.x/sail) (PHP, MySQL 8, Redis). You do **not** need PHP or Composer installed on your host — `install-deps` runs those inside a one-shot container.

We recommend [go-task](https://taskfile.dev/docs/installation) to run the project's tasks. If you'd rather not install it, open `taskfile.yml` and run the underlying commands yourself.

> **Shell note:** `taskfile.yml` and Laravel Sail expect a Unix-like shell (`bash` / `sh`). That is the default on macOS and Linux. On Windows, use **WSL2** (recommended) or Git Bash — not plain PowerShell — for the commands below.

## 1. Prerequisites by OS

### macOS

1. Install [Docker Desktop for Mac](https://docs.docker.com/desktop/setup/install/mac-install/) and start it.
2. Install [go-task](https://taskfile.dev/docs/installation):

```bash
brew install go-task
```

### Linux

1. Install Docker Engine ([docs](https://docs.docker.com/engine/install/)) **or** [Docker Desktop for Linux](https://docs.docker.com/desktop/setup/install/linux/). Ensure your user can run `docker` without `sudo` (typically by joining the `docker` group), then start the daemon.
2. Install [go-task](https://taskfile.dev/docs/installation) — easiest with the install script:

```bash
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

Package-manager options (`apt`/`dnf`/`pacman`/`snap`, etc.) are listed on the [Task installation page](https://taskfile.dev/docs/installation).

### Windows

Laravel Sail on Windows is designed to run under **WSL2**. Native PowerShell alone will fight the shell scripts in this repo.

1. Install WSL2 and a Linux distro (Ubuntu is fine). In an elevated PowerShell:

```powershell
wsl --install -d Ubuntu
```

Restart if prompted, then finish the Ubuntu first-run user setup.

2. Install [Docker Desktop for Windows](https://docs.docker.com/desktop/setup/install/windows-install/). In Docker Desktop → **Settings**:
   - **General**: enable **Use the WSL 2 based engine**
   - **Resources → WSL Integration**: enable integration for your Ubuntu (or other) distro

3. Open your **WSL** terminal (Ubuntu), not PowerShell, for the rest of setup.

4. Install go-task **inside WSL**:

```bash
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

5. Clone or copy the project into the **Linux filesystem** (e.g. `~/projects/...`), not under `/mnt/c/...`. Bind mounts from Windows drives are much slower and often cause Sail/Docker permission pain.

```bash
cd ~
mkdir -p projects && cd projects
git clone <your-fork-url> wind4life
cd wind4life
```

If you already cloned on a Windows drive (`H:\`, `C:\`, etc.), re-clone inside WSL or move the tree into `~/projects`.

## 2. Install project dependencies

From the project root (macOS/Linux terminal, or WSL on Windows):

```bash
task install-deps
```

This will:

- install Composer dependencies in a one-shot `laravelsail/php83-composer` container (no local PHP required),
- create `.env` from `.env.example` if `.env` is missing,
- generate `APP_KEY` if it is not already set.

## 3. Boot the app

```bash
task init-app
```

That builds the Sail image, starts the stack, migrates, and seeds data. See [Initialize the app](#initialize-the-app) below for details and credentials.

## Tasks

These tasks are useful for running the project and dev environment utilities.

### List all "important" tasks:

```bash
task
```

**Note**: This won't list **all** tasks, just the ones with a description that we deem interesting for an external dev. Take a look at `taskfile.yml` for the full set.

-----------------------------------------------------------

### Initialize the app

Idempotent task that builds the image, brings the stack up, runs migrations, and seeds data:

```bash
task init-app
```

**Note**: Resets all data and tables. You'll end up with one seeded user and 50 anemometers, each with 100 readings. If you want to keep existing data, use `task up` instead.

Open [http://localhost:8000](http://localhost:8000/) in your browser to land on the frontpage.

This runs the stack in detached mode (needed for the data initialization step). For logs use `task up` or `task logs` afterwards.

Login with the seeded admin account:

- Username: `admin`
- Password: `admin`

-----------------------------------------------------------

### Reset DB

Wipe all tables and re-run migrations, useful for a clean DB state:

```bash
task reset-db
```

-----------------------------------------------------------

### Generate anemometers

Create anemometers with a set of readings:

```bash
task create_anemometers -- num_of_anemometers num_of_readings_per_anemometer num_tags_per_reading
# ex: task create_anemometers -- 50 100 2
```

-----------------------------------------------------------

### Running tests

```bash
task tests
```

The suite runs on a dedicated `wind4life_testing` MySQL database (created automatically on first `task init-app`) and uses `DatabaseTransactions`, so it never touches your local dev data.

-----------------------------------------------------------

### Code style

PSR-12 with tabs, defined in `phpcs.xml.dist`:

```bash
task phpcs    # check
task phpcbf   # auto-fix
```

-----------------------------------------------------------

### Any artisan management command

```bash
task manage -- <command>
# ex: task manage -- route:list
```

-----------------------------------------------------------

### Start / stop the stack

```bash
task up     # Start the Sail stack (attach to logs)
task down   # Stop and remove the Sail containers
task logs   # Tail the Sail logs
task bash   # Open a shell inside the app container
```
