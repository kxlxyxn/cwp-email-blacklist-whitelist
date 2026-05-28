#!/bin/bash

# Ensure the script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "Error: Please run this installer as root."
  exit 1
fi

echo "Starting installation of Blacklist/Whitelist Emails module..."

# Define paths
MODULE_DEST="/usr/local/cwpsrv/htdocs/resources/admin/modules/blacklist_whitelist_emails.php"
TP_FILE="/usr/local/cwpsrv/htdocs/resources/admin/include/3rdparty.php"
MAIN_CF="/etc/postfix/main.cf"
RAW_PHP_URL="https://raw.githubusercontent.com/kxlxyxn/cwp-email-blacklist-whitelist/main/blacklist_whitelist_emails.php"

# 1. Download the PHP module directly from GitHub to CWP modules directory
echo "Downloading module file..."
curl -sSL "$RAW_PHP_URL" -o "$MODULE_DEST"
chmod 644 "$MODULE_DEST"

# 2. Safely append the menu injection script to 3rdparty.php
if [ -f "$TP_FILE" ]; then
    if grep -q "blacklist_whitelist_emails" "$TP_FILE"; then
        echo "Menu link already exists in 3rdparty.php. Skipping modification."
    else
        echo "Injecting menu link into 3rdparty.php..."
        cat << 'EOF' >> "$TP_FILE"

<script type="text/javascript">
$(document).ready(function() {
    var emailModule = '<li class="custom-menu"><a href="index.php?module=blacklist_whitelist_emails"><span class="icon16 icomoon-icon-arrow-right-3"></span>Blacklist/Whitelist Emails</a></li>';
    $("ul#mn-11-sub").append(emailModule);
});
</script>
EOF
    fi
else
    echo "Warning: Target file $TP_FILE not found. Is Centos Web Panel installed correctly?"
fi

# 3. Configure Postfix sender restrictions
if [ -f "$MAIN_CF" ]; then

    if ! grep -q "check_sender_access hash:/etc/postfix/sender_whitelist" "$MAIN_CF"; then
        postconf -e "smtpd_sender_restrictions=check_sender_access hash:/etc/postfix/sender_whitelist, check_sender_access hash:/etc/postfix/sender_blacklist"
        echo "Added sender whitelist/blacklist restrictions to Postfix."
    else
        echo "Sender restrictions already configured. Skipping."
    fi

    # Ensure map files exist
    touch /etc/postfix/sender_blacklist
    touch /etc/postfix/sender_whitelist

    # Build hash DB files
    postmap /etc/postfix/sender_blacklist
    postmap /etc/postfix/sender_whitelist

    # Restart postfix
    systemctl restart postfix

else
    echo "Warning: Postfix main.cf not found."
fi

echo "Installation complete! Please refresh your CWP Admin Panel."
