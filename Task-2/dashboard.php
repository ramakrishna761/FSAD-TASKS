<?php include("db.php"); ?>

<?php
// Filter
$dept = isset($_GET['dept']) ? $_GET['dept'] : "";

// Sort
$sort = isset($_GET['sort']) ? $_GET['sort'] : "";

// Query building
$query = "SELECT * FROM students WHERE 1";

if($dept != ""){
    $query .= " AND department='$dept'";
}

if($sort == "name"){
    $query .= " ORDER BY name ASC";
}
elseif($sort == "dob"){
    $query .= " ORDER BY dob ASC";
}

$result = mysqli_query($conn, $query);

// Count per department
$count_query = "SELECT department, COUNT(*) as total FROM students GROUP BY department";
$count_result = mysqli_query($conn, $count_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Task-2 Dashboard</title>
    <style>
        body {
            font-family: Arial;
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
        }
        .container {
            width: 90%;
            margin: auto;
        }
        h1 {
            text-align: center;
        }
        .controls {
            margin: 20px 0;
        }
        select, button {
            padding: 8px;
            margin-right: 10px;
        }
        table {
            width: 100%;
            background: white;
            color: black;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #333;
            color: white;
        }
        .cards {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .card {
            background: white;
            color: black;
            padding: 15px;
            flex: 1;
            text-align: center;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📊 Task-2 Dashboard</h1>

    <!-- Controls -->
    <form method="GET" class="controls">
        <select name="dept">
            <option value="">All Departments</option>
            <option value="CSE">CSE</option>
            <option value="ECE">ECE</option>
            <option value="EEE">EEE</option>
        </select>

        <button name="sort" value="name">Sort by Name</button>
        <button name="sort" value="dob">Sort by DOB</button>

        <button type="submit">Apply</button>
    </form>

    <!-- Department Count -->
    <div class="cards">
        <?php while($row = mysqli_fetch_assoc($count_result)) { ?>
            <div class="card">
                <h3><?php echo $row['department']; ?></h3>
                <p><?php echo $row['total']; ?> Students</p>
            </div>
        <?php } ?>
    </div>

    <!-- Table -->
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>DOB</th>
            <th>Department</th>
            <th>Phone</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td><?php echo $row['dob']; ?></td>
            <td><?php echo $row['department']; ?></td>
            <td><?php echo $row['phone']; ?></td>
        </tr>
        <?php } ?>
    </table>

</div>

</body>
</html>