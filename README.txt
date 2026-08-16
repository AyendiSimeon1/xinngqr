QR LINK MANAGER - PHP/HTML5

WHAT THIS DOES
- Creates a Linktree-style landing page.
- Generates a live QR code pointing to that page.
- Lets you edit links from a password-protected admin page.
- Uses PHP + HTML5 + CSS.
- Stores data in data/links.json. No database required.

UPLOAD
1. Upload the qrlink-manager folder to your hosting public directory.
   Example: public_html/links/
2. Open: https://yourdomain.com/links/
3. Admin: https://yourdomain.com/links/admin.php

DEFAULT LOGIN
Password: changeMe123!

CHANGE PASSWORD
1. Open: https://yourdomain.com/links/admin.php?make_hash=YOUR_NEW_PASSWORD
2. Copy the generated hash.
3. Open config.php.
4. Replace $ADMIN_PASSWORD_HASH with the new hash.
5. Delete or ignore the hash URL after use.

IMPORTANT
- The QR code should point to the public index.php page.
- After you print or share the QR code, you can still update links in admin.php.
- The QR image is generated using api.qrserver.com, so internet access is needed for QR display.
- If your server cannot write to data/links.json, set folder permissions for /data to 755 or 775.

CUSTOMIZE
- Change app name and tagline in config.php.
- Edit default links from admin.php.
- Replace styles in assets/style.css.
