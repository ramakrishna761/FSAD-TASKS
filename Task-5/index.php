<?php
include("db.php");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment System</title>
    <style>
        body {
            font-family: Arial;
            background: linear-gradient(to right, #36d1dc, #5b86e5);
        }
        .container {
            width: 400px;
            margin: 80px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }
        h2 {
            text-align: center;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
        }
        button {
            width: 100%;
            padding: 10px;
            background: green;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: darkgreen;
        }
        .accounts {
            background: #f4f4f4;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .accounts p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>💳 Payment Simulation</h2>

    <!-- Show Account Balances -->
    <div class="accounts">
        <h4>Account Balances</h4>
        <?php
        $result = mysqli_query($conn, "SELECT * FROM accounts");
        while($row = mysqli_fetch_assoc($result)) {
            echo "<p><b>ID {$row['id']}</b> - {$row['name']} : ₹{$row['balance']}</p>";
        }
        ?>
    </div>

    <!-- Payment Form -->
    <form action="process.php" method="POST">
        <label>Sender ID:</label>
        <input type="number" name="sender" placeholder="Enter Sender ID" required>

        <label>Receiver ID:</label>
        <input type="number" name="receiver" placeholder="Enter Receiver ID" required>

        <label>Amount:</label>
        <input type="number" name="amount" placeholder="Enter Amount" required>

        <button type="submit">Pay</button>
    </form>
</div>

</body>
</html>