<?php
session_start();
include('config.php');

try {
    $connection = new mysqli($hostname, $username, $password, $database);

    if ($connection->connect_error) {
        throw new Exception("Error: " . $connection->connect_error);
    }
} catch (Exception $e) {
    exit($e->getMessage());
}

if (isset($_POST['update'])) {
    $products = $_POST['Product'];
    $monthlySalesQty = $_POST['MonthlySalesQty'];
    $weeklySalesQty = $_POST['WeeklySalesQty'];
    $dailySalesQty = $_POST['DailySalesQty'];
    $yearlySalesQty = $_POST['YearlySalesQty'];
    $salesPrice = $_POST['SalesPrice'];

    // Prepare and bind parameters
    $stmt = $connection->prepare('UPDATE sales SET MonthlySalesQty = ?, WeeklySalesQty = ?, DailySalesQty = ?, SalesPrice = ?, YearlySalesQty = ? WHERE Product = ?');
    $stmt->bind_param('dddsss', $monthlySalesQtyValue, $weeklySalesQtyValue, $dailySalesQtyValue, $salesPriceValue, $yearlySalesQtyValue, $product);

    // Loop through the form data and update the database
    foreach ($products as $key => $product) {
        $monthlySalesQtyValue = isset($monthlySalesQty[$key]) ? $monthlySalesQty[$key] : null;
        $weeklySalesQtyValue = isset($weeklySalesQty[$key]) ? $weeklySalesQty[$key] : null;
        $dailySalesQtyValue = isset($dailySalesQty[$key]) ? $dailySalesQty[$key] : null;
        $yearlySalesQtyValue = isset($yearlySalesQty[$key]) ? $yearlySalesQty[$key] : null;
        $salesPriceValue = isset($salesPrice[$key]) ? $salesPrice[$key] : null;

        // Execute the statement only if values are not null
        if ($monthlySalesQtyValue !== null && $weeklySalesQtyValue !== null && $dailySalesQtyValue !== null && $yearlySalesQtyValue !== null && $salesPriceValue !== null && $product !== null) {
            // Update the order of parameters in the bind_param method to match the order of placeholders in your SQL query
            $stmt->bind_param('dddsss', $monthlySalesQtyValue, $weeklySalesQtyValue, $dailySalesQtyValue, $salesPriceValue, $yearlySalesQtyValue, $product);

            $stmt->execute();
        }
    }


// Redirect to the current page to refresh the data
header('Location: ' . $_SERVER['PHP_SELF']); {
    exit();
}

}

// Disable safe update mode (for this operation)
$disableSafeUpdateQuery = "SET SQL_SAFE_UPDATES = 0;";
$connection->query($disableSafeUpdateQuery);

$Username = $_SESSION['Username'];


// Use the sales_pilot database
$useDatabaseQuery = "USE sales_pilot";
$connection->query($useDatabaseQuery);


// Get the current date
$currentDate = date('Y-m-d');

// Calculate week start date as last Sunday
$weekStartDate = date('Y-m-d', strtotime('last Sunday', strtotime($currentDate)));

// Create temporary table for weekly sales with daily quantities
$weeklySalesTable = "
    CREATE TEMPORARY TABLE temp_weekly_sales AS
    SELECT 
        Product, 
        WEEK(Date) AS SalesWeek,
        MONTH(Date) AS SalesMonth, 
        YEAR(Date) AS SalesYear,
        DAYOFWEEK(Date) AS DayOfWeek,
        SUM(DailySalesQty) AS DailySalesQty
    FROM 
        sales
    WHERE 
        Date >= '$weekStartDate' AND Date <= '$currentDate'
    GROUP BY 
        Product, SalesWeek, SalesMonth, SalesYear, DayOfWeek, Date
";

$connection->query($weeklySalesTable);

// Update sales table with weekly and daily sales data
$updateWeeklySales = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            SalesWeek,
            SalesYear,
            DayOfWeek,
            SUM(DailySalesQty) AS DailySalesQty
        FROM 
            temp_weekly_sales
        GROUP BY 
            Product, SalesWeek, SalesYear, DayOfWeek
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND WEEK(sr.Date) = temp.SalesWeek 
        AND YEAR(sr.Date) = temp.SalesYear
        AND DAYOFWEEK(sr.Date) = temp.DayOfWeek
    SET 
        sr.DailySalesQty = temp.DailySalesQty
";
$connection->query($updateWeeklySales);

// Drop temporary table for weekly sales
$dropTempTable = "DROP TEMPORARY TABLE IF EXISTS temp_weekly_sales";
$connection->query($dropTempTable);


// Create temporary table for monthly sales with daily quantities
$monthlySalesTable = "
    CREATE TEMPORARY TABLE temp_monthly_sales AS
    SELECT 
        Product, 
        MONTH(Date) AS SalesMonth, 
        YEAR(Date) AS SalesYear,
        DAY(Date) AS DayOfMonth,
        SUM(DailySalesQty) AS DailySalesQty
    FROM 
        sales
    WHERE 
        Date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND Date <= '$currentDate'
    GROUP BY 
        Product, SalesMonth, SalesYear, DayOfMonth
";
$connection->query($monthlySalesTable);

// Update sales table with monthly and daily sales data
$updateMonthlySales = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            SalesMonth,
            SalesYear,
            DayOfMonth,
            SUM(DailySalesQty) AS DailySalesQty
        FROM 
            temp_monthly_sales
        GROUP BY 
            Product, SalesMonth, SalesYear, DayOfMonth
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND MONTH(sr.Date) = temp.SalesMonth 
        AND YEAR(sr.Date) = temp.SalesYear
        AND DAY(sr.Date) = temp.DayOfMonth
    SET 
        sr.DailySalesQty = temp.DailySalesQty
";
$connection->query($updateMonthlySales);

// Drop temporary table for monthly sales
$dropTempMonthlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_monthly_sales";
$connection->query($dropTempMonthlyTable);

// Create temporary table for yearly sales with daily quantities
$yearlySalesTable = "
    CREATE TEMPORARY TABLE temp_yearly_sales AS
    SELECT 
        Product, 
        YEAR(Date) AS SalesYear,
        DAY(Date) AS DayOfYear,
        SUM(DailySalesQty) AS DailySalesQty
    FROM 
        sales
    WHERE 
        YEAR(Date) = YEAR(NOW())
    GROUP BY 
        Product, SalesYear, DayOfYear
";
$connection->query($yearlySalesTable);

// Update sales table with yearly and daily sales data
$updateYearlySales = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            SalesYear,
            DayOfYear,
            SUM(DailySalesQty) AS DailySalesQty
        FROM 
            temp_yearly_sales
        GROUP BY 
            Product, SalesYear, DayOfYear
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND YEAR(sr.Date) = temp.SalesYear
        AND DAYOFYEAR(sr.Date) = temp.DayOfYear
    SET 
        sr.DailySalesQty = temp.DailySalesQty
";
$connection->query($updateYearlySales);

// Drop temporary table for yearly sales
$dropTempYearlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_yearly_sales";
$connection->query($dropTempYearlyTable);


// Create temporary table for weekly Expenses with daily quantities
$weeklyExpensesTable = "
    CREATE TEMPORARY TABLE temp_weekly_Expenses AS
    SELECT 
        Product, 
        WEEK(Date) AS ExpensesWeek,
        MONTH(Date) AS ExpensesMonth, 
        YEAR(Date) AS ExpensesYear,
        DAYOFWEEK(Date) AS DayOfWeek,
        SUM(DailyExpenses) AS DailyExpenses
    FROM 
        sales
    WHERE 
        Date >= '$weekStartDate' AND Date <= '$currentDate'
    GROUP BY 
        Product, ExpensesWeek, ExpensesMonth, ExpensesYear, DayOfWeek, Date
";

$connection->query($weeklyExpensesTable);

// Update Expenses table with weekly and daily Expenses data
$updateWeeklyExpenses = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            ExpensesWeek,
            ExpensesYear,
            DayOfWeek,
            SUM(DailyExpenses) AS DailyExpenses
        FROM 
            temp_weekly_Expenses
        GROUP BY 
            Product, ExpensesWeek, ExpensesYear, DayOfWeek
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND WEEK(sr.Date) = temp.ExpensesWeek 
        AND YEAR(sr.Date) = temp.ExpensesYear
        AND DAYOFWEEK(sr.Date) = temp.DayOfWeek
    SET 
        sr.DailyExpenses = temp.DailyExpenses
";
$connection->query($updateWeeklyExpenses);

// Drop temporary table for weekly Expenses
$dropTempTable = "DROP TEMPORARY TABLE IF EXISTS temp_weekly_Expenses";
$connection->query($dropTempTable);


// Create temporary table for monthly Expenses with daily quantities
$monthlyExpensesTable = "
    CREATE TEMPORARY TABLE temp_monthly_Expenses AS
    SELECT 
        Product, 
        MONTH(Date) AS ExpensesMonth, 
        YEAR(Date) AS ExpensesYear,
        DAY(Date) AS DayOfMonth,
        SUM(DailyExpenses) AS DailyExpenses
    FROM 
        sales
    WHERE 
        Date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND Date <= '$currentDate'
    GROUP BY 
        Product, ExpensesMonth, ExpensesYear, DayOfMonth
";
$connection->query($monthlyExpensesTable);

// Update Expenses table with monthly and daily Expenses data
$updateMonthlyExpenses = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            ExpensesMonth,
            ExpensesYear,
            DayOfMonth,
            SUM(DailyExpenses) AS DailyExpenses
        FROM 
            temp_monthly_Expenses
        GROUP BY 
            Product, ExpensesMonth, ExpensesYear, DayOfMonth
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND MONTH(sr.Date) = temp.ExpensesMonth 
        AND YEAR(sr.Date) = temp.ExpensesYear
        AND DAY(sr.Date) = temp.DayOfMonth
    SET 
        sr.DailyExpenses = temp.DailyExpenses
";
$connection->query($updateMonthlyExpenses);

// Drop temporary table for monthly Expenses
$dropTempMonthlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_monthly_Expenses";
$connection->query($dropTempMonthlyTable);

// Create temporary table for yearly Expenses with daily quantities
$yearlyExpensesTable = "
    CREATE TEMPORARY TABLE temp_yearly_Expenses AS
    SELECT 
        Product, 
        YEAR(Date) AS ExpensesYear,
        DAY(Date) AS DayOfYear,
        SUM(DailyExpenses) AS DailyExpenses
    FROM 
        sales
    WHERE 
        YEAR(Date) = YEAR(NOW())
    GROUP BY 
        Product, ExpensesYear, DayOfYear
";
$connection->query($yearlyExpensesTable);

// Update Expenses table with yearly and daily Expenses data
$updateYearlyExpenses = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            ExpensesYear,
            DayOfYear,
            SUM(DailyExpenses) AS DailyExpenses
        FROM 
            temp_yearly_Expenses
        GROUP BY 
            Product, ExpensesYear, DayOfYear
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND YEAR(sr.Date) = temp.ExpensesYear
        AND DAYOFYEAR(sr.Date) = temp.DayOfYear
    SET 
        sr.DailyExpenses = temp.DailyExpenses
";
$connection->query($updateYearlyExpenses);

// Drop temporary table for yearly Expenses
$dropTempYearlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_yearly_Expenses";
$connection->query($dropTempYearlyTable);

// Fetch user records based on their Username
$stmt = $connection->prepare('SELECT * FROM sales WHERE Username = ?');
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Function to fetch products with highest values
function fetchProductWithHighestValue($connection, $Username, $valueType) {
    $valueColumn = ($valueType === 'sales') ? 'AnnualRevenue' : 'InventoryValue';
    $stmt = $connection->prepare("SELECT Product FROM sales WHERE Username = ? ORDER BY $valueColumn DESC LIMIT 1");
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Product'];
    } else {
        return 'No products found';
    }
}

// Fetch products with highest values
$HighestRevenue = fetchProductWithHighestValue($connection, $Username, 'sales');
$HighestInventoryValue = fetchProductWithLowestValue($connection, $Username, 'inventory');

// Function to fetch products with lowest values
function fetchProductWithLowestValue($connection, $Username, $valueType) {
    $valueColumn = ($valueType === 'sales') ? 'AnnualRevenue' : 'InventoryValue';
    $stmt = $connection->prepare("SELECT Product FROM sales WHERE Username = ? ORDER BY $valueColumn ASC LIMIT 1");
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Product'];
    } else {
        return 'No products found';
    }
}

// Fetch products with lowest sales values
$LowestRevenue = fetchProductWithLowestValue($connection, $Username, 'sales');
$LowestInventoryValue = fetchProductWithLowestValue($connection, $Username, 'inventory');


$stmt2 = $connection->prepare('SELECT category.categoryname AS categoryname, 
                                    GROUP_CONCAT(sales.Product) AS ProductNames, 
                                    SUM(sales.InventoryToSalesRatio) AS InventoryToSalesRatio, 
                                    SUM(sales.ReturnOnInvestment) AS ReturnOnInvestment, 
                                    SUM(sales.NetProfitMargin) AS NetProfitMargin, 
                                    SUM(sales.GrossProfitMargin) AS GrossProfitMargin
                                FROM sales
                                INNER JOIN category ON sales.sales_categoryid = category.id_category
                                WHERE sales.Username = ?
                                GROUP BY categoryname'); // Use categoryname instead of category.categoryname
$stmt2->bind_param('s', $Username);
$stmt2->execute();
$result2 = $stmt2->get_result(); // Corrected variable name

// Fetch data for the chart (e.g., products and net profit)
$data = array(
    'Category' => array(),
    'Products' => array(),
    'InventoryToSalesRatio' => array(),
    'ReturnOnInvestment' => array(),
    'NetProfitMargin' => array(),
    'GrossProfitMargin' => array(),
);

while ($row = $result2->fetch_assoc()) {
    $data['Category'][] = $row['categoryname'];
    $data['Products'][] = $row['ProductNames']; // Updated to use ProductNames
    $data['InventoryToSalesRatio'][] = $row['InventoryToSalesRatio'];
    $data['ReturnOnInvestment'][] = $row['ReturnOnInvestment'];
    $data['NetProfitMargin'][] = $row['NetProfitMargin'];
    $data['GrossProfitMargin'][] = $row['GrossProfitMargin'];
}


// Function to fetch products with highest and lowest Net profit
function fetchProductsByNetProfit($connection, $Username, $order) {
    $stmt = $connection->prepare("SELECT Product, NetProfit FROM sales WHERE Username = ? ORDER BY NetProfit $order LIMIT 1");
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'Product' => $row['Product'],
            'NetProfit' => $row['NetProfit'],
        ];
    } else {
        return [
            'Product' => 'No products found',
            'GrossProfit' => 0,
        ];
    }
}

// Fetch product with highest net profit
$highestNetProfitProduct = fetchProductsByNetProfit($connection, $Username, 'DESC');

// Fetch product with lowest net profit
$lowestNetProfitProduct = fetchProductsByNetProfit($connection, $Username, 'ASC');

// Re-enable safe update mode (recommended for security)
$enableSafeUpdateQuery = "SET SQL_SAFE_UPDATES = 1;";
$connection->query($enableSafeUpdateQuery);


// Fetch data for sales metrics with category information
$stmt_metrics = $connection->prepare('
    SELECT sales.*, category.categoryname
    FROM sales
    JOIN category ON sales.sales_categoryid = category.id_category
    WHERE sales.Username = ?
');
$stmt_metrics->bind_param('s', $Username);
$stmt_metrics->execute();
$result_metrics = $stmt_metrics->get_result();


// Define the fetchCategoryName function
function fetchCategoryName($connection, $categoryName) {
    $stmt = $connection->prepare("SELECT categoryname FROM category WHERE id_category = ?");
    $stmt->bind_param('s', $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['categoryname'];
    } else {
        return 'Category not found';
    }
}
// Function to fetch and calculate totals
function fetchAndCalculateTotals($connection, $Username) {
    $stmt = $connection->prepare('SELECT COUNT(*) AS TotalProduct, SUM(InventoryValue) AS TotalInventoryValue FROM sales WHERE Username = ?');
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'TotalProduct' => $row['TotalProduct'],
            'TotalInventoryValue' => $row['TotalInventoryValue']
        ];
    } else {
        return [
            'TotalProduct' => 0,
            'TotalInventoryValue' => 0
        ];
    }
}

// Fetch and calculate totals
$totalData = fetchAndCalculateTotals($connection, $Username);

// Fetch category names and IDs
$categoryNames = array();

$stmt = $connection->prepare('SELECT id_category, categoryname FROM category');
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $categoryNames[$row['id_category']] = $row['categoryname'];
}


