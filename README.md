# CWP Postfix Email Blacklist & Whitelist Module

An intuitive management module for CentOS Web Panel (CWP) that allows administrators to easily control Postfix mail routing rules directly from the CWP Admin UI.

## Features

* **Visual Rules Management:** Add or remove blacklist/whitelist entries without touching the command line.
* **Granular Control:** Supports blocking or bypassing by specific email addresses (`user@example.com`), entire domains (`example.com`), or server IP addresses.
* **Real-time AntiSpam Status:** Displays whether `zen.spamhaus.org` is currently active in your Postfix configuration.
* **No Double-Submissions:** Built-in browser history handling prevents duplicate entries or annoying alert prompts if a user refreshes the page.
* **Seamless UI Integration:** Injects itself elegantly into the existing **Email** sub-menu container in your CWP sidebar.

---

## 🚀 One-Step Installation via SSH

Log into your server via SSH as the `root` user and run this single command to download and install the module automatically:


```bash
curl -sSL https://raw.githubusercontent.com/kxlxyxn/cwp-email-blacklist-whitelist/main/install.sh | bash
