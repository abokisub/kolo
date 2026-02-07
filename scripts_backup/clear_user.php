<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'pointwave';

echo "Connecting to database...\n";
$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
echo "Connected successfully.\n";

$usernames = ['customer', 'developer', 'customer developer'];
foreach ($usernames as $username) {
    echo "Processing username: $username\n";
    $stmt = $mysqli->prepare("DELETE FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    echo "Deleted rows for $username: " . $stmt->affected_rows . "\n";
    $stmt->close();
}

$mysqli->close();
echo "Done.\n";
