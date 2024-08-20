<?php
// Include the mail server and database configuration files
include 'email_config.php';
include 'db_config.php';

echo "email config included...<br>";
echo "db config included...<br>";

echo "Starting rest of the script...<br>";

// Connecting to the mail server
$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to the mail server: ' . imap_last_error());
echo "Connected to the mail server.<br>";

// PostgreSQL database connection
$conn_string = "host=$db_host dbname=$db_name user=$db_user password=$db_password";

echo "Attempting to connect to the database...<br>";

$conn = pg_connect($conn_string);

if (!$conn) {
    die("Connection failed: " . pg_last_error());
} else {
    echo "Connected to the database successfully.<br>";
}

// Get the number of emails
$emails = imap_search($inbox, 'ALL');

if ($emails) {
    echo "Found " . count($emails) . " emails.<br>";
    rsort($emails); // Sort emails in reverse order (newest first)

    $stopProcessing = false;

    foreach ($emails as $email_number) {
        if ($stopProcessing) {
            echo "Stopping processing due to known hash.<br>";
            break; // Stop processing if a known hash is found
        }

        echo "Processing email number: $email_number<br>";

        // Fetch the email overview
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        echo "Subject: " . htmlspecialchars($overview[0]->subject) . "<br>";

        // Fetch the email body (plaintext)
        $message = imap_fetchbody($inbox, $email_number, 1.1);
        if (empty($message)) { // sometimes 1.1 doesn't exist, try 1
            $message = imap_fetchbody($inbox, $email_number, 1);
        }
        echo "Fetched message body.<br>";

        // Calculate the hash of the message
        $hash = hash('sha256', $message);
        echo "Calculated hash: $hash<br>";

        // Check if the hash already exists in the database
        $query = "SELECT COUNT(*) FROM tb_emails WHERE hash = $1";
        $result = pg_query_params($conn, $query, array($hash));

        if ($result) {
            $count = pg_fetch_result($result, 0, 0);
            echo "Hash exists count: $count<br>";

            if ($count == 0) {
                // Decode the subject
                $subject = '';
                foreach (imap_mime_header_decode($overview[0]->subject) as $part) {
                    $subject .= $part->text;
                }
                echo "Decoded subject: " . htmlspecialchars($subject) . "<br>";

                // Extract the IP address
                $ip_address = null;
                if (preg_match('/IP address of “([^“]+)”/', $message, $matches)) {
                    $ip_address = $matches[1];
                }
                echo "Extracted IP address: $ip_address<br>";

                // Extract the timestamp
                if (preg_match('/generated this notice on ([^“]+ UTC)/', $message, $matches)) {
                    $timestamp = date('Y-m-d H:i:s', strtotime($matches[1]));
                } else {
                    // Use the current time as a fallback
                    $timestamp = date('Y-m-d H:i:s');
                }
                
                echo "Extracted timestamp: $timestamp<br>";

                // Insert the email into the database
                $query = "INSERT INTO tb_emails (subject, from_email, message, ip_address, timestamp, hash) VALUES ($1, $2, $3, $4, $5, $6)";
                $result = pg_query_params($conn, $query, array($subject, $overview[0]->from, $message, $ip_address, $timestamp, $hash));

                if ($result) {
                    echo "Stored email with subject: " . htmlspecialchars($subject) . "<br>";
                } else {
                    echo "Failed to store email: " . pg_last_error($conn) . "<br>";
                }
            } else {
                echo "Known email found. Stopping processing.<br>";
                $stopProcessing = true; // Set flag to stop further processing
            }
        } else {
            echo "Failed to check hash: " . pg_last_error($conn) . "<br>";
        }
    }
} else {
    echo "No emails found.<br>";
}

// Close connections
pg_close($conn);
imap_close($inbox);

echo "Script finished.<br>";
?>
