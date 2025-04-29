<?php
require_once 'db.php';
require_once 'vendor/autoload.php'; // You'll need to install phpqrcode via composer

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// Check if product ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = intval($_GET['id']);

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();

// Create QR code content
$qr_content = json_encode([
    'id' => $product['id'],
    'name' => $product['name'],
    'price' => $product['price'],
    'url' => "http://{$_SERVER['HTTP_HOST']}/product_details.php?id={$product['id']}"
]);

// Generate QR code
$renderer = new ImageRenderer(
    new RendererStyle(300),
    new SvgImageBackEnd()
);
$writer = new Writer($renderer);

// Generate SVG QR code
$qr_svg = $writer->writeString($qr_content);

// Convert to data URI
$qr_data_uri = 'data:image/svg+xml;base64,' . base64_encode($qr_svg);

// Return QR code as JSON
echo json_encode([
    'success' => true,
    'qr_code' => $qr_data_uri,
    'product' => [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price']
    ]
]);
