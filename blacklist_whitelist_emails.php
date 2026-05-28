<?php
if (!isset($include_path)) {
    echo("Invalid access");
    exit;
}

// ---------------------------------------------------------
// 1. CONFIGURATION
// ---------------------------------------------------------
$blacklist_file = '/etc/postfix/sender_blacklist';
$whitelist_file = '/etc/postfix/sender_whitelist';

if (!file_exists($blacklist_file)) { @touch($blacklist_file); }
if (!file_exists($whitelist_file)) { @touch($whitelist_file); }

$message = "";
$message_type = "success";
$should_clean_history = false;

// ---------------------------------------------------------
// 2. HANDLE ACTIONS
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD ENTRY
    if (isset($_POST['action']) && $_POST['action'] === 'add') {

        $type = $_POST['type'];
        $input = trim($_POST['entry_value']);

        $target_file = ($type === 'blacklist') ? $blacklist_file : $whitelist_file;
        $suffix = ($type === 'blacklist') ? 'REJECT' : 'OK';

        if (!empty($input)) {

            $input = preg_replace('/\s+/', '', $input);
            $line_to_add = $input . " " . $suffix . "\n";

            if (@file_put_contents($target_file, $line_to_add, FILE_APPEND | LOCK_EX) !== false) {
                @shell_exec("postmap " . escapeshellarg($target_file));
                @shell_exec("systemctl reload postfix");
                $message = "Successfully added " . htmlspecialchars($input) . " to " . $type . ".";
                $should_clean_history = true;
            } else {
                $message = "Error writing file.";
                $message_type = "danger";
            }
        }
    }

    // REMOVE ENTRY
    if (isset($_POST['action']) && $_POST['action'] === 'remove') {

        $type = $_POST['type'];
        $line_to_remove = trim($_POST['line_value']);
        $target_file = ($type === 'blacklist') ? $blacklist_file : $whitelist_file;

        if (file_exists($target_file)) {

            $lines = file($target_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $new_lines = [];
            $removed = false;

            foreach ($lines as $line) {
                if (trim($line) === $line_to_remove) {
                    $removed = true;
                    continue;
                }
                $new_lines[] = $line;
            }

            if ($removed) {
                $content = !empty($new_lines) ? implode("\n", $new_lines) . "\n" : "";
                @file_put_contents($target_file, $content, LOCK_EX);
                @shell_exec("postmap " . escapeshellarg($target_file));
                @shell_exec("systemctl reload postfix");
                $message = "Entry removed from " . $type . ".";
                $should_clean_history = true;
            }
        }
    }
}

// ---------------------------------------------------------
// 3. READ DATA (Filters out comment lines)
// ---------------------------------------------------------
$blacklist_entries = [];
if (file_exists($blacklist_file)) {
    $raw_blacklist = file($blacklist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($raw_blacklist as $line) {
        if (strpos(ltrim($line), '#') === 0) {
            continue;
        }
        $blacklist_entries[] = $line;
    }
}

$whitelist_entries = [];
if (file_exists($whitelist_file)) {
    $raw_whitelist = file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($raw_whitelist as $line) {
        if (strpos(ltrim($line), '#') === 0) {
            continue;
        }
        $whitelist_entries[] = $line;
    }
}

// ---------------------------------------------------------
// 4. OUTPUT HEADER & MESSAGES
// ---------------------------------------------------------
echo("<h3>Black/Whitelist Emails</h3>");

// User description added here
echo("<p style='color: #666; font-size: 14px; margin-top: 5px; margin-bottom: 15px;'>
    Manage your mail routing rules below. You can blacklist or whitelist senders by entering a specific email address (e.g., <code>user@example.com</code>), an entire domain (e.g., <code>example.com</code>), or an IP address. Blacklisted senders are automatically rejected, while whitelisted senders bypass default restrictions.
</p>");

if (!empty($message)) {
    echo("<div class='alert alert-{$message_type}' style='margin-top:15px;'>
        {$message}
    </div>");
}

// AntiSpam check
$command_output = @shell_exec('grep -E "zen.spamhaus.org" /etc/postfix/main.cf');
$is_antispam_enabled = ($command_output !== null && strpos($command_output, 'zen.spamhaus.org') !== false);

echo("<div style='margin:20px 0; display:flex; gap:10px; align-items:center;'>
    <span>Is AntiSpam enabled?</span>");

echo($is_antispam_enabled
    ? "<span class='label label-success'>TRUE</span>"
    : "<span class='label label-danger'>FALSE</span>"
);

echo("</div>");

// ---------------------------------------------------------
// 5. LAYOUT
// ---------------------------------------------------------
echo("<div style='display:flex; gap:30px; flex-wrap:wrap;'>");

// ---------------- BLACKLIST ----------------
echo("<div style='flex:1; min-width:300px; padding:20px; border:1px solid #ddd;'>");
echo("<h4>Blacklist</h4>");

echo("
<form method='post'>
    <input type='hidden' name='action' value='add'>
    <input type='hidden' name='type' value='blacklist'>
    <input type='text' name='entry_value' required style='width:70%'>
    <button type='submit'>Add</button>
</form>
");

echo("<table>");
foreach ($blacklist_entries as $entry) {
    echo("<tr>
        <td><code>" . htmlspecialchars($entry) . "</code></td>
        <td>
            <form method='post'>
                <input type='hidden' name='action' value='remove'>
                <input type='hidden' name='type' value='blacklist'>
                <input type='hidden' name='line_value' value='" . htmlspecialchars($entry, ENT_QUOTES) . "'>
                <button type='submit'>X</button>
            </form>
        </td>
    </tr>");
}
echo("</table>");

echo("</div>");

// ---------------- WHITELIST ----------------
echo("<div style='flex:1; min-width:300px; padding:20px; border:1px solid #ddd;'>");
echo("<h4>Whitelist</h4>");

echo("
<form method='post'>
    <input type='hidden' name='action' value='add'>
    <input type='hidden' name='type' value='whitelist'>
    <input type='text' name='entry_value' required style='width:70%'>
    <button type='submit'>Add</button>
</form>
");

echo("<table>");
foreach ($whitelist_entries as $entry) {
    echo("<tr>
        <td><code>" . htmlspecialchars($entry) . "</code></td>
        <td>
            <form method='post'>
                <input type='hidden' name='action' value='remove'>
                <input type='hidden' name='type' value='whitelist'>
                <input type='hidden' name='line_value' value='" . htmlspecialchars($entry, ENT_QUOTES) . "'>
                <button type='submit'>X</button>
            </form>
        </td>
    </tr>");
}
echo("</table>");

echo("</div>");

echo("</div>");

// ---------------------------------------------------------
// 6. PREVENT REFRESH FORM RESUBMISSION (JavaScript Trick)
// ---------------------------------------------------------
if ($should_clean_history) {
    echo "
    <script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
    ";
}
?>
