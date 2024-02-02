<?php
// Establish a connection to your MySQL database
$servername = "your_server_name";
$username = "your_username";
$password = "your_password";
$dbname = "your_database_name";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve data from the HTML form
$productName = $_POST['productName'];
$stockQty = $_POST['stockQty'];
$salesPrice = $_POST['salesPrice'];
$costPrice = $_POST['costPrice'];
$datetime = $_POST['datetime'];
$staffName = $_POST['staffName'];
$contact = $_POST['contact'];
$category = $_POST['category'];

// Insert data into the database
$sql = "INSERT INTO product_stock (product_name, stock_qty, sales_price, cost_price, datetime, staff_name, contact, category)
        VALUES ('$productName', $stockQty, $salesPrice, $costPrice, '$datetime', '$staffName', '$contact', '$category')";

if ($conn->query($sql) === TRUE) {
    echo "Record added successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the database connection
$conn->close();
?>
