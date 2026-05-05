<?php include("db.php"); ?>

<!DOCTYPE html>
<html>
<head>
    <title>Task-4 Order Management</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
        }
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: black;
            color: white;
        }
        h2 {
            text-align: center;
        }
        .box {
            width: 90%;
            margin: auto;
        }
        .card {
            background: white;
            padding: 15px;
            margin: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>

<h2>🛒 Order Management Dashboard</h2>

<div class="box">

<?php
// JOIN QUERY (Order History)
$query = "SELECT customers.name, products.product_name, products.price,
          orders.quantity, orders.order_date,
          (products.price * orders.quantity) AS total
          FROM orders
          JOIN customers ON orders.customer_id = customers.id
          JOIN products ON orders.product_id = products.id
          ORDER BY orders.order_date DESC";

$result = mysqli_query($conn, $query);
?>

<table>
<tr>
    <th>Customer</th>
    <th>Product</th>
    <th>Price</th>
    <th>Qty</th>
    <th>Date</th>
    <th>Total</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)) { ?>
<tr>
    <td><?php echo $row['name']; ?></td>
    <td><?php echo $row['product_name']; ?></td>
    <td><?php echo $row['price']; ?></td>
    <td><?php echo $row['quantity']; ?></td>
    <td><?php echo $row['order_date']; ?></td>
    <td><?php echo $row['total']; ?></td>
</tr>
<?php } ?>
</table>

<?php
// Highest Order (Subquery)
$high = mysqli_query($conn, "
SELECT name, MAX(total) as highest FROM (
    SELECT customers.name,
    (products.price * orders.quantity) AS total
    FROM orders
    JOIN customers ON orders.customer_id = customers.id
    JOIN products ON orders.product_id = products.id
) as temp
");

$high_row = mysqli_fetch_assoc($high);

// Most Active Customer
$active = mysqli_query($conn, "
SELECT customers.name, COUNT(orders.id) as total_orders
FROM orders
JOIN customers ON orders.customer_id = customers.id
GROUP BY customers.name
ORDER BY total_orders DESC
LIMIT 1
");

$active_row = mysqli_fetch_assoc($active);
?>

<div class="card">
    <h3>💰 Highest Order</h3>
    <p><?php echo $high_row['name']; ?> - ₹<?php echo $high_row['highest']; ?></p>
</div>

<div class="card">
    <h3>🔥 Most Active Customer</h3>
    <p><?php echo $active_row['name']; ?> (<?php echo $active_row['total_orders']; ?> orders)</p>
</div>

</div>

</body>
</html>