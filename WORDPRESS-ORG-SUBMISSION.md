# WordPress.org Submission Notes

## Plugin Directory Name

For WordPress.org submission, the plugin directory must be renamed from `woo-reorder-reminder` to `easy-reorder-reminder` to comply with trademark guidelines.

The current directory name starts with "woo" which is a restricted term. WordPress.org requires plugin slugs to not begin with trademarked terms.

## Steps for WordPress.org Submission

1. **Rename the plugin directory:**
   ```bash
   mv woo-reorder-reminder easy-reorder-reminder
   ```

2. **Update the main plugin file name (optional but recommended):**
   ```bash
   cd easy-reorder-reminder
   mv woo-reorder-reminder.php easy-reorder-reminder.php
   ```

3. **Update phpcs.xml to reference the new file name:**
   - Change `<file>woo-reorder-reminder.php</file>` to `<file>easy-reorder-reminder.php</file>`

4. **Package the plugin:**
   - Create a ZIP file with the `easy-reorder-reminder` directory
   - The plugin slug will be `easy-reorder-reminder` (from the directory name)

## Current Status

- ✅ Plugin Name: "Easy Re-Order Reminder for WooCommerce" (compliant)
- ✅ Text Domain: "easy-reorder-reminder" (compliant)
- ⚠️ Directory Name: "woo-reorder-reminder" (needs renaming for WordPress.org)

## Note

The current directory name is fine for development and GitHub. Only the WordPress.org submission package needs the renamed directory.

