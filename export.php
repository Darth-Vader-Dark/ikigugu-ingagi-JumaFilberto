<?php
require_once 'db.php';

// Get export type
$type = isset($_GET['type']) ? $_GET['type'] : 'csv';

// Get products
$sql = "SELECT * FROM products ORDER BY name";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Export as CSV
if ($type == 'csv') {
    // Set headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['ID', 'Name', 'Price', 'Quantity', 'Category', 'Image', 'Created At', 'Updated At']);
    
    // Add data
    foreach ($products as $product) {
        fputcsv($output, $product);
    }
    
    // Close output stream
    fclose($output);
    exit;
}

// Export as PDF (requires mPDF library, but we'll simulate it for this example)
if ($type == 'pdf') {
    // In a real application, you would use a PDF library like mPDF or TCPDF
    // For this example, we'll just output HTML that could be converted to PDF
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Products Export</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>Products Export - ' . date('Y-m-d') . '</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Category</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($products as $product) {
        echo '<tr>
                <td>' . $product['id'] . '</td>
                <td>' . $product['name'] . '</td>
                <td>$' . number_format($product['price'], 2) . '</td>
                <td>' . $product['quantity'] . '</td>
                <td>' . $product['category'] . '</td>
                <td>' . $product['created_at'] . '</td>
            </tr>';
    }
    
    echo '</tbody>
        </table>
    </body>
    </html>';
    
    exit;
}

// If no valid export type is specified, redirect back to index
header("Location: index.php");
exit;
?>