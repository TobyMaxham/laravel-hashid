---
name: laravel-hashid-upgrade
description: Upgrade an app to a new major version of tobymaxham/laravel-hashid (e.g. 1.x to 2.0). Use when the user asks to upgrade, update or migrate laravel-hashid, or when composer.json requires an older major version of tobymaxham/laravel-hashid than the latest one.
---

# Upgrade tobymaxham/laravel-hashid

Step by step instructions for AI agents to upgrade a Laravel app that uses `tobymaxham/laravel-hashid`.
The human readable guide is [UPGRADING.md](https://github.com/TobyMaxham/laravel-hashid/blob/main/UPGRADING.md).

## 1. Find the right guide

1. Read the installed version: `composer show tobymaxham/laravel-hashid` (or `composer.lock`).
2. Pick the guide for the upgrade. Run them one after another, if the app skips a major version.

| From | To  | Guide                                                |
|------|-----|------------------------------------------------------|
| 1.x  | 2.0 | [references/1.x-to-2.0.md](references/1.x-to-2.0.md) |

If you read this file from GitHub, the guides are in
`https://raw.githubusercontent.com/TobyMaxham/laravel-hashid/main/resources/boost/skills/laravel-hashid-upgrade/references/`.

## 2. Rules for every upgrade

- **Ask the user before decisions that change behavior in production**, e.g. whether old hashes in links must keep working. Never guess them. Each guide marks these steps with **ASK**.
- Do not change `HASH_SALT`, `HASH_ALPHABET`, `HASH_LENGTH` or `HASH_PREFIX` (also per model) on your own. Changing them changes all hashes. If a guide recommends a change, **ASK** first.
- Never print the value of `HASH_SALT` or other secrets. Only check whether it is set.
- Change `.env.example` and the config, but only tell the user which values to set in `.env` of each environment. Do not edit `.env` files of other environments.
- Only change code that uses this package. Do not refactor unrelated code.
- Do not commit. Leave the changes for the user to review.
- Run the test suite (and static analysis, if the app uses it) after the upgrade and fix failures caused by the upgrade.

## 3. Report

Finish with a short summary for the user:

1. Version before and after.
2. Decisions the user made (or still has to make).
3. Files you changed.
4. Values the user must set in `.env` of each environment (staging, production, ...).
5. Things you could not check automatically, e.g. hashes stored in other systems or links in sent emails.
6. Test results.
