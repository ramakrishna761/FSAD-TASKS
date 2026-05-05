<?php
include("db.php");

// Insert
if(isset($_POST['add'])){
    $name = $_POST['name'];
    $action = $_POST['action'];

    mysqli_query($conn, "INSERT INTO activity(name, action) VALUES('$name','$action')");
}

// Update
if(isset($_POST['update'])){
    $id = $_POST['id'];
    $action = $_POST['action'];

    mysqli_query($conn, "UPDATE activity SET action='$action' WHERE id=$id");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Task-6 Logging System</title>
    <style>
        body {
            font-family: Arial;
            background: #eef2f3;
        }
        .container {
            width: 80%;
            margin: auto;
        }
        h2 {
            text-align: center;
        }
        form {
            background: white;
            padding: 15px;
            margin: 15px 0;
        }
        input, button {
            padding: 8px;
            margin: 5px;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background: black;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📋 Automated Logging System</h2>

    <!-- Insert -->
    <form method="POST">
        <h3>Add Activity</h3>
        <input type="text" name="name" placeholder="Name" required>
        <input type="text" name="action" placeholder="Action" required>
        <button name="add">Add</button>
    </form>

    <!-- Update -->
    <form method="POST">
        <h3>Update Activity</h3>
        <input type="number" name="id" placeholder="Activity ID" required>
        <input type="text" name="action" placeholder="New Action" required>
        <button name="update">Update</button>
    </form>

    <!-- Logs -->
    <h3>📌 Activity Logs (Trigger Output)</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Action</th>
            <th>Time</th>
        </tr>

        <?php
        $logs = mysqli_query($conn, "SELECT * FROM activity_log ORDER BY log_time DESC");
        while($row = mysqli_fetch_assoc($logs)){
            echo "<tr>
                <td>{$row['activity_id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['action']}</td>
                <td>{$row['log_time']}</td>
            </tr>";
        }
        ?>
    </table>

    <!-- VIEW -->
    <h3>📊 Daily Activity Report (VIEW)</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Action</th>
            <th>Date</th>
            <th>Total</th>
        </tr>

        <?php
        $view = mysqli_query($conn, "SELECT * FROM daily_activity");
        while($row = mysqli_fetch_assoc($view)){
            echo "<tr>
                <td>{$row['name']}</td>
                <td>{$row['action']}</td>
                <td>{$row['date']}</td>
                <td>{$row['total']}</td>
            </tr>";
        }
        ?>
    </table>

</div>

</body>
</html>