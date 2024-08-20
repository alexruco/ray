<?php
// Include the mail server and database configuration files
include 'email_config.php';
include 'db_config.php';

// Connecting to the mail server
$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to the mail server: ' . imap_last_error());

// MySQL database connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the number of emails
$emails = imap_search($inbox, 'ALL');

if ($emails) {
    rsort($emails); // Sort emails in reverse order (newest first)

    $stopProcessing = false;

    foreach ($emails as $email_number) {
        if ($stopProcessing) {
            break; // Stop processing if a known hash is found
        }

        // Fetch the email overview
        $overview = imap_fetch_overview($inbox, $email_number, 0);

        // Fetch the email body (plaintext)
        $message = imap_fetchbody($inbox, $email_number, 1.1);
        if (empty($message)) { // sometimes 1.1 doesn't exist, try 1
            $message = imap_fetchbody($inbox, $email_number, 1);
        }

        // Calculate the hash of the message
        $hash = hash('sha256', $message);

        // Check if the hash already exists in the database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tb_emails WHERE hash = ?");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            // Decode the subject
            $subject = '';
            foreach (imap_mime_header_decode($overview[0]->subject) as $part) {
                $subject .= $part->text;
            }

            // Extract the IP address
            if (preg_match('/IP address of “([^“]+)”/', $message, $matches)) {
                $ip_address = $matches[1];
            }

            // Extract the timestamp
            if (preg_match('/generated this notice on ([^“]+ UTC)/', $message, $matches)) {
                $timestamp = date('Y-m-d H:i:s', strtotime($matches[1]));
            }

            // Insert the email into the database
            $stmt = $conn->prepare("INSERT INTO tb_emails (subject, from_email, message, ip_address, timestamp, hash) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $subject, $overview[0]->from, $message, $ip_address, $timestamp, $hash);
            $stmt->execute();
            $stmt->close();

            echo "Stored email with subject: " . htmlspecialchars($subject) . "<br>";
        } else {
            echo "Known email found. Stopping processing.<br>";
            $stopProcessing = true; // Set flag to stop further processing
        }
    }
}

// Close connections
$conn->close();
imap_close($inbox);
?>
