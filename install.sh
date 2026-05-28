#!/bin/bash

# Ensure the script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "Error: Please run this installer as root."
  exit 1
fi

echo "Starting installation of Blacklist/Whitelist Emails module..."

# Define paths
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_DEST="/usr/local/cwpsrv/htdocs/resources/admin/modules/blacklist_whitelist_emails.php"
TP_FILE="/usr/local/cwpsrv/htdocs/resources/admin/include/3rdparty.php"

# 1. Copy the PHP module to CWP modules directory
echo "Copying module file..."
cp "$REPO_DIR/blacklist_whitelist_emails.php" "$MODULE_DEST"
chmod 644 "$MODULE_DEST"

# 2. Safely append the menu injection script to 3rdparty.php
if [ -f "$TP_FILE" ]; then
    # Check if our module script is already in the file to prevent duplicate injections
    if grep -q "blacklist_whitelist_emails" "$TP_FILE"; then
        echo "Menu link already exists in 3rdparty.php. Skipping modification."
    else
        echo "Injecting menu link into 3rdparty.php..."
        
        cat << 'EOF' >> "$TP_FILE"

<script type="text/javascript">
$(document).ready(function() {
    // 1. Define your menu link as a variable
    var emailModule = '<li class="custom-menu"><a href="index.php?module=blacklist_whitelist_emails"><span class="icon16 icomoon-icon-arrow-right-3"></span>Blacklist/Whitelist Emails</a></li>';

    // 2. Force it to inject into the Email sub-menu container (ul#mn-11-sub)
    $("ul#mn-11-sub").append(emailModule);
});
</script>
EOF
    fi
else
    echo "Warning: Target file $TP_FILE not found. Is Centos Web Panel installed correctly?"
fi

echo "Installation complete! Please refresh your CWP Admin Panel."
