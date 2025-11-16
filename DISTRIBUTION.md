# Mailiam WordPress Plugin - Distribution Guide

## Overview

This plugin is distributed via **GitHub Releases** with automatic update delivery to WordPress installations.

**Update System:** Plugin Update Checker v5.4 by YahnisElsts
**Repository:** https://github.com/mailiam/wordpress-plugin
**License:** MIT

---

## For Users: How to Install

### Initial Installation

1. **Download** the latest release:
   - Visit: https://github.com/mailiam/wordpress-plugin/releases/latest
   - Download `mailiam-wordpress.zip`

2. **Install** in WordPress:
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose the downloaded ZIP file
   - Click "Install Now"
   - Click "Activate"

3. **Configure**:
   - Go to Settings → Mailiam
   - Enter your Mailiam API key
   - Save settings

### Automatic Updates

Once installed, the plugin will:
- ✅ Check for updates daily (automatically)
- ✅ Show update notifications in WordPress admin
- ✅ Allow one-click updates (just like WordPress.org plugins)

**No manual downloads needed for updates!**

---

## For Developers: How to Release

### Prerequisites

- Plugin code is ready and tested
- Version number is incremented (in `mailiam.php` and `readme.txt`)
- CHANGELOG.md is updated
- All changes are committed to `main` branch

### Release Process

#### 1. Prepare the Release

```bash
# Ensure you're on main branch with latest code
git checkout main
git pull origin main

# Verify version numbers match
grep "Version:" mailiam.php
grep "Stable tag:" readme.txt

# Should both show the same version (e.g., 1.1.0)
```

#### 2. Create ZIP File

```bash
# From the mailiam-wordpress directory
cd /path/to/mailiam-wordpress

# Create distribution ZIP (includes vendor/ directory)
zip -r mailiam-wordpress.zip . \
  -x "*.git*" \
  -x "*.DS_Store" \
  -x "node_modules/*" \
  -x "*.md" \
  -x "STRUCTURE.txt"

# Verify ZIP contents
unzip -l mailiam-wordpress.zip | head -20
```

**Important:** The ZIP must include `vendor/plugin-update-checker/` for automatic updates to work!

#### 3. Create GitHub Release

**Via GitHub Web Interface:**

1. Go to: https://github.com/mailiam/wordpress-plugin/releases/new

2. Fill in the form:
   - **Tag version:** `v1.1.0` (match plugin version)
   - **Release title:** `Mailiam WordPress Plugin v1.1.0`
   - **Description:** Copy from CHANGELOG.md for this version

3. **Attach binary:**
   - Drag and drop `mailiam-wordpress.zip`

4. **Publish release**

**Via GitHub CLI:**

```bash
# Tag the release
git tag -a v1.1.0 -m "Version 1.1.0 - Transactional email support"
git push origin v1.1.0

# Create release with ZIP
gh release create v1.1.0 \
  mailiam-wordpress.zip \
  --title "Mailiam WordPress Plugin v1.1.0" \
  --notes-file CHANGELOG.md
```

#### 4. Verify Update Delivery

**Test on a WordPress installation:**

1. Install previous version (e.g., v1.0.0)
2. Wait 1-2 minutes (update checker runs)
3. Go to Dashboard → Updates
4. Verify new version appears
5. Click "Update Now"
6. Verify successful update

**Update check frequency:** Once every 12 hours (automatic)

---

## Version Numbering (Semantic Versioning)

Follow [SemVer](https://semver.org/):

- **MAJOR.MINOR.PATCH** (e.g., 1.1.0)

### When to increment:

- **MAJOR (1.x.x → 2.x.x):** Breaking changes
  - Example: Remove a feature, change API
  - **Rare** - avoid if possible

- **MINOR (1.0.x → 1.1.x):** New features (backward compatible)
  - Example: Add transactional email support
  - **Common** - most releases

- **PATCH (1.1.0 → 1.1.1):** Bug fixes (backward compatible)
  - Example: Fix form submission error
  - **Frequent** - bug fix releases

### Update Checklist:

Before releasing, update version in **3 places**:

1. ✅ `mailiam.php` (line 6): `Version: 1.1.0`
2. ✅ `mailiam.php` (line 21): `define('MAILIAM_VERSION', '1.1.0');`
3. ✅ `readme.txt` (Stable tag): `Stable tag: 1.1.0`
4. ✅ `CHANGELOG.md` - Add entry for new version

---

## Update Mechanism Details

### How It Works

1. **Plugin Update Checker library** (vendor/plugin-update-checker/)
   - Checks GitHub repository for new releases
   - Runs automatically every 12 hours
   - Can be triggered manually: Dashboard → Updates → Check Again

2. **GitHub API**
   - Library queries: `https://api.github.com/repos/mailiam/wordpress-plugin/releases/latest`
   - Compares latest release version with installed version
   - If newer version available, triggers WordPress update notification

3. **WordPress Update UI**
   - Shows update notification in admin bar
   - Appears in Dashboard → Updates
   - One-click update button (just like WordPress.org plugins)

### Configuration

Located in `mailiam.php` (lines 36-48):

```php
$mailiam_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/mailiam/wordpress-plugin/',  // GitHub repo URL
    __FILE__,                                         // Main plugin file
    'mailiam'                                         // Plugin slug
);

$mailiam_update_checker->setBranch('main');          // Branch to check
```

### Troubleshooting Updates

**Users not seeing updates:**

1. Check GitHub release is published (not draft)
2. Verify ZIP file is attached to release
3. Ensure version number in plugin matches tag
4. Wait 12 hours or click "Check Again" in WordPress
5. Check for PHP errors (wp-content/debug.log)

**Update fails:**

1. Verify ZIP file contains all necessary files
2. Check file permissions (uploads directory)
3. Ensure PHP has write access to plugins directory
4. Try manual update (download ZIP, upload manually)

---

## Distribution Channels

### Primary: GitHub Releases

- **URL:** https://github.com/mailiam/wordpress-plugin/releases
- **Automatic updates:** ✅ Yes
- **Cost:** $0
- **Maintenance:** Low

### Secondary: Direct Download (Optional)

Can also distribute from your website:

1. Host ZIP on: https://mailiam.io/downloads/wordpress-plugin/
2. Update documentation to include download link
3. Update checker still works (as long as GitHub releases are published)

### Future: WordPress.org (Planned)

**Timeline:** Month 2-3 (after GitHub release is stable)

**Benefits:**
- One-click installation from WordPress admin
- Maximum discoverability
- SEO benefits
- Credibility

**Trade-offs:**
- 2-8 week approval wait (as of 2025)
- SVN workflow (instead of Git)
- Must follow WordPress.org guidelines strictly

---

## Support & Documentation

### User Documentation

- **Quick Start:** QUICKSTART.md
- **Examples:** EXAMPLES.md
- **README:** README.md
- **FAQ:** readme.txt (sections at bottom)

### Developer Documentation

- **Code Structure:** STRUCTURE.txt
- **API Reference:** README.md
- **Changelog:** CHANGELOG.md

### Support Channels

1. **GitHub Issues:** Bug reports and feature requests
2. **Email:** support@mailiam.io (general questions)
3. **Docs:** https://docs.mailiam.io (knowledge base)

---

## Security

### Reporting Security Issues

**DO NOT** open public GitHub issues for security vulnerabilities.

**Instead:**
- Email: security@mailiam.io
- Provide detailed description
- Expected response: 24-48 hours
- Fix timeline: 7 days for critical issues

### Security Updates

- **Priority:** Critical bugs get immediate patch releases
- **Versioning:** Increment PATCH version (e.g., 1.1.0 → 1.1.1)
- **Notification:** GitHub release + email to known users
- **Disclosure:** After fix is released and users have time to update

---

## License

MIT License - See LICENSE file

**What this means for distribution:**
- ✅ Free to use, modify, distribute
- ✅ Commercial use allowed
- ✅ Can be included in other projects
- ✅ Must include original license and copyright notice

---

## Changelog

See CHANGELOG.md for full version history.

### Recent Releases

- **v1.1.0** (2024-11-16) - Transactional email support
- **v1.0.0** (2024-11-16) - Initial release

---

## FAQ

### How do updates work?

The plugin checks GitHub for new releases every 12 hours. When a new version is available, WordPress shows an update notification. Users click "Update Now" and the plugin updates automatically.

### Do users need a GitHub account?

No! Users just download the ZIP file from the releases page. No GitHub account or login required.

### Can I install from WordPress.org?

Not yet. We plan to submit to WordPress.org in Month 2-3. For now, use GitHub releases.

### What if GitHub is down?

Updates won't be available temporarily, but the plugin continues working normally. Once GitHub is back online, updates resume.

### Can I host the ZIP on my own server?

Yes, but you still need GitHub releases for automatic updates to work (or you'd need to modify the update checker configuration to point to your server).

---

## Questions?

- **Email:** support@mailiam.io
- **GitHub:** https://github.com/mailiam/wordpress-plugin/issues
- **Docs:** https://docs.mailiam.io
