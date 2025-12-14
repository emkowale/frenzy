Frenzy
======

WooCommerce helper plugin for user-uploaded artwork mockups.

What it does
------------
- Adds an upload block on Frenzy-enabled products so customers can place their art on the product image.
- Provides an “Adjust Boundaries” admin tool to define the printable region per product.
- Generates a composite mockup (product + overlay) and carries it through cart, checkout, emails, and order meta.
- Stores original art + transform for reference; order meta shows “View Mockup” and “View Original Art Front” buttons.
- Sets new orders to “On hold” so artwork can be reviewed before processing.

Install / update
----------------
1. Upload the `frenzy` plugin folder to `wp-content/plugins/`.
2. Activate in WordPress Admin → Plugins.
3. Enable Frenzy on a product via the “Use with Frenzy” checkbox in the product’s General settings.

Usage
-----
- On the product page: click “Upload your own image”, position/resize within the dotted boundary, then add to cart. The cart thumbnail and emails will show the generated mockup.
- To adjust the printable region: in the product gallery (when logged in with permissions), click “Adjust Boundaries”, drag/resize the box, and save.
- Orders: admins see “View Mockup” / “View Original Art Front” buttons in order item meta; orders start as On hold.

Developer notes
---------------
- JS is modular under `assets/js/` (core, grid, canvas, api, overlay, upload, submit, etc.).
- Server AJAX endpoints live in `includes/mockup/` (generate, canvas save) and cart/order handlers in `includes/cart/`.
- Mockup outputs are stored in `wp-content/uploads/frenzy-mockups/`.
- Release helper: `./release.sh <patch|minor|major>` (requires git repo).

Troubleshooting
---------------
- If mockups don’t appear, check that `frenzy_mockup_url` is set on the cart item and that `wp-content/uploads/frenzy-mockups/` is writable.
- Ensure GD is available on the server for server-side mockup generation.
