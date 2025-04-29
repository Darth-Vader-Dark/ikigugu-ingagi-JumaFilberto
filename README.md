# Ingagi ERP - Mini Point of Sale System

A lightweight Point of Sale (POS) system for managing products in a retail platform.

## Features

- **Product Management**: Full CRUD operations for products
- **Search & Filter**: Live search and sorting capabilities
- **Shopping Cart**: Simulate purchases with a shopping cart
- **Export**: Export product data as CSV
- **Responsive Design**: Works on mobile and desktop
- **Dark Mode**: Toggle between light and dark themes

## Technologies Used

- **Backend**: PHP 8+
- **Frontend**: HTML5, Bootstrap 5, Vanilla JavaScript
- **Database**: MySQL
- **Hosting**: Localhost via XAMPP

## Project Structure

```
ikigugu-ingagi-bestcoder/
│
├── index.php            # Dashboard and Product List
├── add_product.php      # Add Product Logic
├── edit_product.php     # Edit Product Logic
├── delete_product.php   # Delete Product Logic
├── cart.php             # Shopping Cart
├── export.php           # Export Functionality
├── db.php               # Database Connection
├── assets/
│   ├── css/
│   │   └── style.css    # Custom styles
│   ├── js/
│   │   └── scripts.js   # Custom JavaScript
│   └── images/
│       └── no-image.jpg # Placeholder image
└── README.md            # Project documentation
```

## Setup Guide

1. Install XAMPP (or similar local server environment)
2. Clone or download this repository to your `htdocs` folder
3. Start Apache and MySQL services in XAMPP
4. Navigate to `http://localhost/ikigugu-ingagi-Juma-Filberto/` in your browser
5. The application will automatically set up the database and tables on first run

## Features

### Product Management
- Add new products with name, price, quantity, category, and optional image
- Edit existing products
- Delete products with confirmation
- View all products in a responsive table/grid

### Search and Filter
- Live search products by name or category
- Sort products by name, price, quantity, or category

### Shopping Cart
- Add products to cart
- View cart contents
- Remove items from cart
- Checkout (simulated)

### UI/UX Features
- Responsive design for mobile and desktop
- Dark/light theme toggle
- Animated transitions and hover effects
- Toast notifications for user feedback
- Modal forms for adding and editing products

## Screenshots

(Screenshots would be included in a real README)

## License

This project is open-source and available for personal and commercial use.
```

This completes the mini ERP Point of Sale system called "Ingagi ERP" as requested. The system includes all the required functionality:

1. Full CRUD operations for products
2. Product listing with search and sort
3. Shopping cart simulator
4. Export functionality
5. Responsive design with Bootstrap 5
6. Dark/light theme toggle
7. Animations and transitions

To use this system:
1. Install XAMPP
2. Place all these files in a folder named "ikigugu-ingagi-Juma-Filberto" in your htdocs directory
3. Start Apache and MySQL services
4. Navigate to http://localhost/ikigugu-ingagi-Juma-Filberto/ in your browser

The system will automatically create the database and tables on first run.

<Actions>
  <Action name="Add product image upload functionality" description="Implement the file upload system for product images" />
  <Action name="Implement user authentication" description="Add login/register functionality with user roles" />
  <Action name="Create sales reporting dashboard" description="Add charts and reports for sales analytics" />
  <Action name="Add barcode scanning support" description="Implement barcode scanning for faster product lookup" />
  <Action name="Create customer management system" description="Add functionality to manage customer information and purchase history" />
</Actions>

```

