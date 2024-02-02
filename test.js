const express = require('express');
const mysql = require('mysql');
const { spawn } = require('child_process');
const path = require('path');

const dbConfig = {
  host: 'PHEMCODE:3306', // Correct the database host
  business: 'phemcode',
  password: 'kokochulo@1987#',
  database: 'sales_pilot',
};

const pool = mysql.createPool(dbConfig);
const app = express();
const PORT = 3000;

app.use(express.static('web'));

app.get('/', (req, res) => {
  const filePath = path.join(__dirname, 'index.html');
  res.sendFile(filePath);
});

// Define a generic route to fetch records
const fetchRecords = (tableName, res) => {
  const query = `SELECT * FROM ${tableName}`;

  pool.query(query, (error, results) => {
    if (error) {
      console.error(error);
      res.status(500).json({ error: `An error occurred while fetching ${tableName} records` });
    } else {
      res.json(results);
    }
  });
};

app.get('/api/business_records', (req, res) => {
  fetchRecords('business_records', res);
});

app.get('/api/inventory', (req, res) => {
  fetchRecords('inventory', res);
});

app.get('/api/sales', (req, res) => {
  fetchRecords('sales', res);
});

app.get('/api/category', (req, res) => {
  fetchRecords('category', res);
});

// Define a route to handle PHP files (dynamic content)
app.get('/dynamic', (req, res) => {
  const phpProcess = spawn('php', ['-r', 'echo "Dynamic content with PHP";']);
  
  let output = '';
  phpProcess.stdout.on('data', (data) => {
    output += data;
  });

  phpProcess.on('close', (code) => {
    if (code === 0) {
      res.send(`<h1>${output}</h1>`);
    } else {
      res.status(500).send('Error executing PHP.');
    }
  });
});

app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
