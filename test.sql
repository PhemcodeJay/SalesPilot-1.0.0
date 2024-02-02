CREATE TABLE IF NOT EXISTS supply (
    id INT AUTO_INCREMENT PRIMARY KEY,
    productName VARCHAR(255) NOT NULL,
    supplyQty INT NOT NULL,
    salesPrice DECIMAL(10, 2) NOT NULL,
    costPrice DECIMAL(10, 2) NOT NULL,
    datetime DATETIME NOT NULL,
    supplierName VARCHAR(255) NOT NULL,
    supplierContact VARCHAR(20) NOT NULL,
    productCategory VARCHAR(50) NOT NULL
);

-- Create a table for stock information
CREATE TABLE IF NOT EXISTS product_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    stock_qty INT NOT NULL,
    sales_price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2) NOT NULL,
    datetime DATETIME NOT NULL,
    staff_name VARCHAR(255) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    category VARCHAR(50) NOT NULL
);


-- Create table for sales data
CREATE TABLE sales_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    stock_qty INT NOT NULL,
    supply_qty INT NOT NULL,
    inventory_qty INT NOT NULL,
    sales_qty INT NOT NULL,
    sales_price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2) NOT NULL,
    inventory_cost DECIMAL(10, 2) NOT NULL,
    inventory_value DECIMAL(10, 2) NOT NULL,
    sales_date DATE NOT NULL,
    period VARCHAR(10) NOT NULL -- 'daily', 'weekly', 'monthly', 'yearly'
);

-- Index for faster queries based on product and date
CREATE INDEX idx_product_date ON sales_data (product_name, sales_date);

-- Example of inserting data
INSERT INTO sales_data (product_name, stock_qty, supply_qty, inventory_qty, sales_qty, sales_price, cost_price, inventory_cost, inventory_value, sales_date, period)
VALUES 
('Product1', 100, 50, 50, 10, 20.99, 15.99, 159.90, 2099.00, '2024-01-30', 'daily'),
('Product2', 200, 100, 100, 15, 25.99, 18.99, 189.90, 2599.00, '2024-01-30', 'daily'),
-- Add more records as needed for your specific use case
;

-- Example query for retrieving daily sales
SELECT * FROM sales_data WHERE period = 'daily';

-- Example query for retrieving weekly sales
SELECT product_name, SUM(sales_qty) AS total_sales_qty, WEEK(sales_date) AS week_number
FROM sales_data
WHERE period = 'daily'
GROUP BY product_name, WEEK(sales_date);

-- Example query for retrieving monthly sales
SELECT product_name, SUM(sales_qty) AS total_sales_qty, MONTH(sales_date) AS month_number
FROM sales_data
WHERE period = 'daily'
GROUP BY product_name, MONTH(sales_date);

-- Example query for retrieving yearly sales
SELECT product_name, SUM(sales_qty) AS total_sales_qty, YEAR(sales_date) AS year_number
FROM sales_data
WHERE period = 'daily'
GROUP BY product_name, YEAR(sales_date);
