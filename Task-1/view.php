
<?php include("db.php"); ?>

<!DOCTYPE html>
<html>
<head>
    <title>View Students</title>
    <style>
        table {
            width: 90%;
            margin: 50px auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid black;
            text-align: center;
        }
        th {
            background: blue;
            color: white;
        }
    </style>
</head>
<body>

<h2 align="center">Student Data</h2>

<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>DOB</th>
    <th>Department</th>
    <th>Phone</th>
</tr>

<?php
$result = mysqli_query($conn, "SELECT * FROM students");

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['name']}</td>
        <td>{$row['email']}</td>
        <td>{$row['dob']}</td>
        <td>{$row['department']}</td>
        <td>{$row['phone']}</td>
    </tr>";
}
?>

</table>

<br>
<center><a href="index.php">Back</a></center>

</body>
</html>