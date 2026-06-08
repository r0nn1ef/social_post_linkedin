# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`social_post_linkedin` is a Drupal 8.x/9.x/10.x contrib module enabling automatic LinkedIn posting via OAuth2. It integrates with the `social_post` and `social_auth` frameworks (Social API suite) and uses the League OAuth2 LinkedIn client (`league/oauth2-linkedin ^5.1.1`).

## Development Commands

This is a Drupal module — it has no standalone build system. Development happens inside a Drupal installation with this module in `modules/contrib/social_post_linkedin/`.

**Run the functional test:**
```bash
# From Drupal root
php vendor/bin/phpunit modules/contrib/social_post_linkedin/tests/src/Functional/SocialPostLinkedInSettingsFormTest.php
```

**Check coding standards:**
```bash
php vendor/bin/phpcs --standard=Drupal,DrupalPractice src/
```

**Fix coding standards automatically:**
```bash
php vendor/bin/phpcbf --standard=Drupal,DrupalPractice src/
```

## Architecture

The module follows the Social API plugin pattern. Every "social network integration" is a **Network plugin** (`Plugin/Network/`). The OAuth2 flow, session state, and user-record management are handled by base classes in `social_post`; this module only provides LinkedIn-specific logic.

### Drupal 11 Architecture

A current Drupal 11 site installed locally for reference is located in /home/ronald/PhpstormProjects/drupalodyssey. This
is for reference only for Core APIs and contrib code referenced in the module.

### Request Flow

1. User visits the OAuth start route → `LinkedInPostController::redirectToProvider()` → redirects to LinkedIn
2. LinkedIn redirects back → `LinkedInPostController::callback()` → stores `social_post_user` entity
3. Rules module (or cron) calls `LinkedInPostManager::post()` → sends to LinkedIn `/v2/ugcPosts`

### Key Classes

| Class | Role |
|---|---|
| `Plugin/Network/LinkedInPost.php` | Network plugin (plugin ID `social_post_linkedin`); initializes the League OAuth2 client from stored config |
| `LinkedInPostManager.php` | Extends `OAuth2Manager` (social_post); wraps the OAuth token exchange, `getUserInfo()`, and `doPost()` |
| `Post.php` | Value object: holds `text`, `author` URN, `visibility`; `getPostBody()` returns the LinkedIn UGC Posts JSON payload |
| `Controller/LinkedInPostController.php` | Extends `OAuth2ControllerBase` (social_post); handles the two OAuth routes |
| `Form/LinkedInPostSettingsForm.php` | Admin config form at `/admin/config/social-api/social-post/linkedin` storing `client_id` / `client_secret` |
| `Plugin/RulesAction/Post.php` | Rules action plugin; iterates all of a user's linked accounts and calls `doPost()` on each |
| `Settings/LinkedInPostSettings.php` | Thin wrapper over `social_post_linkedin.settings` config object |

### Configuration

All credentials live in `social_post_linkedin.settings` (schema in `config/schema/`). The settings form auto-generates the OAuth redirect URI that must be registered in the LinkedIn Developer Portal.

### LinkedIn API

- **UGC Posts endpoint**: `https://api.linkedin.com/v2/ugcPosts`
- **OAuth scopes**: `r_liteprofile`, `r_emailaddress`, `w_member_social`
- **Post visibility options**: `PUBLIC` or `CONNECTIONS` (set in `Post.php`)

### Permissions

- `perform linkedin autoposting tasks` — assigned to users who connect their LinkedIn accounts
- `administer social api autoposting` — admin settings access
- `view social post user entities` — see the list of connected accounts

### Extending / Modifying Posts

To change what gets posted, modify `Post::getPostBody()`. The author URN is a LinkedIn person URN (`urn:li:person:{id}`) populated by `LinkedInPostManager::doPost()`.

