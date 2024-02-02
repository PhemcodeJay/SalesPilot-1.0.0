const http = require('http');
const fs = require('fs');
const path = require('path');
const express = require('express');
const mysql = require('mysql');
const { spawn } = require('child_process'); // Needed for PHP execution

// Database configuration
const dbConfig = {
  
};

var express = require('express');
var app = express();

// set up rate limiter: maximum of five requests per minute
var RateLimit = require('express-rate-limit');
var limiter = RateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100, // max 100 requests per windowMs
});

// apply rate limiter to all requests
app.use(limiter);

app.get('/:path', function(req, res) {
  let path = req.params.path;
  if (isValidPath(path))
    res.sendFile(path);
});

// Create a MySQL connection pool
const pool = mysql.createPool(dbConfig);

// Create Express app
const app = express();

// Serve static files (e.g., images, CSS, etc.)
app.use(express.static('web'));

// Define a route to handle requests to the homepage (HTML)
app.get('/', (req, res) => {
  const path = require('path');
  const filePath = path.join(__dirname, 'index.html');

  res.sendFile(filePath);
});

// Define a route to fetch business records
app.get('/api/business_records', (req, res) => {
  const query = 'SELECT * FROM business_records';

  pool.query(query, (error, results) => {
    if (error) {
      console.error(error);
      res.status(500).json({ error: 'An error occurred while fetching business records' });
    } else {
      res.json(results);
    }
  });
});

// Define a route to fetch inventory records
app.get('/api/inventory', (req, res) => {
  const query = 'SELECT * FROM inventory';

  pool.query(query, (error, results) => {
    if (error) {
      console.error(error);
      res.status(500).json({ error: 'An error occurred while fetching inventory records' });
    } else {
      res.json(results);
    }
  });
});

// Define a route to fetch sales records
app.get('/api/sales', (req, res) => {
  const query = 'SELECT * FROM sales';

  pool.query(query, (error, results) => {
    if (error) {
      console.error(error);
      res.status(500).json({ error: 'An error occurred while fetching sales records' });
    } else {
      res.json(results);
    }
  });
});

// Define a route to fetch category records
app.get('/api/category', (req, res) => {
  const query = 'SELECT * FROM category';

  pool.query(query, (error, results) => {
    if (error) {
      console.error(error);
      res.status(500).json({ error: 'An error occurred while fetching category records' });
    } else {
      res.json(results);
    }
  });
});
// Define a route to handle PHP files (dynamic content)
app.get('/dynamic', (req, res) => {
  // You can use a child process to execute PHP code and capture its output
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

// Start the server on port 3000
const PORT = 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});


