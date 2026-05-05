<?php
include("db.php");

$sender = $_POST['sender'];
$receiver = $_POST['receiver'];
$amount = $_POST['amount'];

// Start transaction
mysqli_begin_transaction($conn);

try {

    // Check sender balance
    $check = mysqli_query($conn, "SELECT balance FROM accounts WHERE id=$sender");
    $row = mysqli_fetch_assoc($check);

    if ($row['balance'] < $amount) {
        throw new Exception("Insufficient Balance");
    }

    // Deduct from sender
    mysqli_query($conn, "UPDATE accounts SET balance = balance - $amount WHERE id=$sender");

    // Add to receiver
    mysqli_query($conn, "UPDATE accounts SET balance = balance + $amount WHERE id=$receiver");

    // Insert transaction
    mysqli_query($conn, "INSERT INTO transactions(sender_id, receiver_id, amount, status)
                        VALUES($sender, $receiver, $amount, 'SUCCESS')");

    // Commit
    mysqli_commit($conn);

    header("Location: success.php");

} catch (Exception $e) {

    // Rollback
    mysqli_rollback($conn);

    mysqli_query($conn, "INSERT INTO transactions(sender_id, receiver_id, amount, status)
                        VALUES($sender, $receiver, $amount, 'FAILED')");

    echo "Transaction Failed: " . $e->getMessage();
}
?>