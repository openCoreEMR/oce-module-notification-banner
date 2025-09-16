# OpenCoreEMR Notification Banner Module

This module creates a notification banner to ensure all users of an OpenEMR instance are aware of an event. Examples might include upcoming site maintenance or other impactful announcements.

## Installation

### From Packagist (Recommended)

If published to Packagist, install via Composer from your OpenEMR root directory:

```bash
composer require opencoreemr/oce-module-notification-banner
```

## Module Activation

After installation:

1. Go to **Administration > Modules > Manage Modules** in OpenEMR
2. Find "OpenCoreEMR Notification Banner Module" in the **Unregistered** tab
3. Click **Register**, then **Install**, then **Enable**

## Configuration

Once enabled, configure the module in **Administration > Globals > OpenCoreEMR Notification Banner**:

### Activate Notification Banner

Toggle to enable/disable the banner display.

### Notification Banner Message

The message to display when the banner is active. If blank, no banner will appear.

## Usage

The banner will appear at the top of all OpenEMR pages when:
- The module is enabled
- "Activate Notification Banner" is checked
- "Notification Banner Message" contains text

## Requirements

- OpenEMR 7.0+
- PHP 8.1+

## License

GPL-3.0-or-later
