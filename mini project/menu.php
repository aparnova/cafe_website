<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu - Westley's Resto Cafe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Font & Color Variables */
    :root {
      --default-font: "Roboto", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      --heading-font: "Playfair Display", sans-serif;
      --nav-font: "Poppins", sans-serif;
      --background-color: #0c0b09;
      --default-color: rgba(255, 255, 255, 0.7);
      --heading-color: #ffffff;
      --accent-color: #cda45e;
      --surface-color: #29261f;
      --contrast-color: #0c0b09;
    }

    /* General Styles */
    body {
      color: var(--default-color);
      background-color: var(--background-color);
      font-family: var(--default-font);
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }

    a {
      color: var(--accent-color);
      text-decoration: none;
      transition: 0.3s;
    }

    a:hover {
      color: color-mix(in srgb, var(--accent-color), transparent 25%);
    }

    h1, h2, h3, h4, h5, h6 {
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    .container {
      width: 100%;
      max-width: 1140px;
      margin: 0 auto;
      padding: 0 15px;
    }

    .section {
      padding: 60px 0;
    }

    /* Header Styles - Matching About Page */
    .header {
      --background-color: rgba(12, 11, 9, 0.61);
      color: var(--default-color);
      transition: all 0.5s;
      z-index: 997;
      position: fixed;
      width: 100%;
      top: 0;
    }

    .header .branding {
      background-color: var(--background-color);
      min-height: 60px;
      padding: 10px 0;
      transition: 0.3s;
      border-bottom: 1px solid var(--background-color);
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header .logo {
      line-height: 1;
      display: flex;
      align-items: center;
    }

    .header .logo img {
      height: 50px;
      margin-right: 15px;
    }

    .header .logo h1 {
      font-size: 24px;
      margin: 0;
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    /* Section Title with Underline Animation */
    .section-title {
      padding-bottom: 60px;
      position: relative;
      text-align: center;
    }

    .section-title h2 {
      font-size: 14px;
      font-weight: 500;
      padding: 0;
      line-height: 1px;
      margin: 0;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      position: relative;
    }

    .section-title h2::after {
      content: "";
      width: 120px;
      height: 1px;
      display: inline-block;
      background: var(--accent-color);
      margin: 4px 10px;
    }

    .section-title p {
      color: var(--accent-color);
      margin: 15px 0 0;
      font-size: 36px;
      font-weight: 600;
      font-family: var(--heading-font);
      position: relative;
      display: inline-block;
      cursor: pointer;
    }

    .section-title p::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      background: var(--accent-color);
      bottom: -10px;
      left: 0;
      transition: width 0.3s ease;
    }

    .section-title p:hover::after {
      width: 100%;
    }

    /* Menu Section - Matching About Page Background */
    .menu {
      background: url("../img/about-bg.jpg") center center;
      background-size: cover;
      position: relative;
      padding: 80px 0;
      padding-top: 140px;
    }

    .menu:before {
      content: "";
      background: color-mix(in srgb, var(--background-color), transparent 12%);
      position: absolute;
      bottom: 0;
      top: 0;
      left: 0;
      right: 0;
    }

    .menu .container {
      position: relative;
      z-index: 2;
    }

    .menu-filters {
      padding: 0;
      margin: 0 auto 30px;
      list-style: none;
      text-align: center;
      border-radius: 50px;
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
    }

    .menu-filters li {
      color: var(--default-color);
      cursor: pointer;
      display: inline-block;
      padding: 8px 20px;
      font-size: 16px;
      font-weight: 500;
      line-height: 1;
      transition: all ease-in-out 0.3s;
      font-family: var(--nav-font);
      margin: 5px;
      border-radius: 50px;
      border: 1px solid var(--accent-color);
    }

    .menu-filters li:hover,
    .menu-filters li.filter-active {
      color: var(--contrast-color);
      background: var(--accent-color);
    }

    .menu-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 30px;
    }

    .menu-item {
      background: var(--surface-color);
      border-radius: 10px;
      overflow: hidden;
      transition: transform 0.3s;
      position: relative;
    }

    .menu-item:hover {
      transform: translateY(-5px);
    }

    .menu-item-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .menu-item-content {
      padding: 20px;
    }

    .menu-item-title {
      font-size: 18px;
      margin: 0 0 10px;
      display: flex;
      justify-content: space-between;
    }

    .menu-item-price {
      color: var(--accent-color);
      font-weight: bold;
    }

    .menu-item-desc {
      font-size: 14px;
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      margin-bottom: 15px;
    }

    .menu-item-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }

    .quantity-control {
      display: flex;
      align-items: center;
    }

    .quantity-btn {
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      width: 25px;
      height: 25px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 12px;
    }

    .quantity-input {
      width: 30px;
      text-align: center;
      background: transparent;
      border: none;
      color: var(--default-color);
      margin: 0 5px;
    }

    .add-to-cart {
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 6px 12px;
      border-radius: 50px;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 14px;
      flex: 1;
    }

    .order-now {
      background: #4CAF50;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 50px;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 14px;
      flex: 1;
    }

    .add-to-cart:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    .order-now:hover {
      background: #45a049;
    }

    /* Cart Section - Hidden by default */
    .cart-sidebar {
      position: fixed;
      top: 0;
      right: -400px;
      width: 400px;
      height: 100%;
      background: var(--surface-color);
      box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
      transition: right 0.3s;
      z-index: 1000;
      padding: 20px;
      overflow-y: auto;
      display: none; /* Hidden by default */
    }

    .cart-sidebar.open {
      right: 0;
      display: block; /* Show when open */
    }

    .cart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .close-cart {
      background: none;
      border: none;
      color: var(--default-color);
      font-size: 24px;
      cursor: pointer;
    }

    .cart-items-container {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .cart-item {
      background: var(--background-color);
      border-radius: 8px;
      padding: 10px;
      position: relative;
    }

    .cart-item-img {
      width: 100%;
      height: 80px;
      object-fit: cover;
      border-radius: 5px;
      margin-bottom: 5px;
    }

    .cart-item-details {
      padding: 0 5px;
    }

    .cart-item-title {
      margin: 0 0 3px;
      font-size: 14px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .cart-item-price {
      color: var(--accent-color);
      font-size: 13px;
      font-weight: bold;
    }

    .cart-item-remove {
      position: absolute;
      top: 5px;
      right: 5px;
      background: #ff6b6b;
      color: white;
      border: none;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 10px;
    }

    .cart-total {
      font-size: 18px;
      font-weight: bold;
      text-align: right;
      margin: 20px 0;
      padding-top: 15px;
      border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .checkout-btn {
      width: 100%;
      padding: 12px;
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .checkout-btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    /* Cart Button in Header */
    .header-cart {
      display: flex;
      align-items: center;
      cursor: pointer;
      position: relative;
    }

    .header-cart-icon {
      font-size: 22px;
      color: var(--accent-color);
      margin-right: 5px;
    }

    .header-cart-text {
      font-family: var(--nav-font);
      color: var(--default-color);
    }

    .header-cart-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ff6b6b;
      color: white;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
    }

    /* Notification */
    .notification {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--accent-color);
      color: var(--contrast-color);
      padding: 10px 20px;
      border-radius: 5px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
      z-index: 1001;
      display: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .cart-sidebar {
        width: 100%;
        right: -100%;
      }
      
      .cart-sidebar.open {
        right: 0;
      }
      
      .menu-container {
        grid-template-columns: 1fr;
      }

      .cart-items-container {
        grid-template-columns: 1fr;
      }

      .section-title p {
        font-size: 28px;
      }

      .header .logo h1 {
        font-size: 24px;
      }
      
      
      .header-cart-icon {
        margin-right: 0;
      }
    }

    @media (min-width: 992px) {
      .menu-container {
        grid-template-columns: repeat(3, 1fr);
      }
    }
  </style>
</head>
<body>
  <!-- Header - Matching About Page -->
  <header class="header">
    <div class="branding">
      <div class="container">
        <div class="logo">
          <img src="img.png" alt="Westley's Resto Cafe">
          <h1>Westley's Resto Cafe </h1>
        </div>
        <div class="header-cart" id="header-cart">
          <i class="fas fa-shopping-cart header-cart-icon"></i>
          <span class="header-cart-count" id="header-cart-count">0</span>
        </div>
      </div>
    </div>
  </header>

  <!-- Menu Section -->
  <section id="menu" class="menu section">
    <div class="container">
      <div class="section-title">
        <h2>Menu</h2>
        <p>Check Our Tasty Menu</p>
      </div>

      <ul class="menu-filters">
        <li class="filter-active" data-category="all">All</li>
        <li data-category="starters">Starters</li>
        <li data-category="main">Main Course</li>
        <li data-category="desserts">Desserts</li>
        <li data-category="beverages">Beverages</li>
      </ul>

      <div class="menu-container" id="menu-container">
        <!-- Menu items will be loaded here by JavaScript -->
      </div>
    </div>
  </section>

  <!-- Cart Sidebar - Hidden by default -->
  <div class="cart-sidebar" id="cart-sidebar">
    <div class="cart-header">
      <h3>Your Order</h3>
      <button class="close-cart" id="close-cart">&times;</button>
    </div>
    <div class="cart-items-container" id="cart-items">
      <!-- Cart items will be added here -->
    </div>
    <div class="cart-total">
      Total: ₹<span id="cart-total">0</span>
    </div>
    <button class="checkout-btn" id="checkout-btn">Proceed to Checkout</button>
  </div>

  <!-- Notification -->
  <div class="notification" id="notification">
    New order received!
  </div>

  <script>
    // Sample menu data - 40 items (10 in each category)
    const menuItems = [
      // Starters (10 items)
      {
        id: 1,
        name: "Lobster Bisque",
        category: "starters",
        price: 900 ,
        description: "Creamy soup with fresh lobster meat",
        image: "https://images.pexels.com/photos/17598231/pexels-photo-17598231.jpeg"
      },
      {
        id: 2,
        name: "Bread Barrel",
        category: "starters",
        price: 200,
        description: "Freshly baked artisan bread assortment",
        image: "https://images.pexels.com/photos/1775043/pexels-photo-1775043.jpeg"
      },
      {
        id: 3,
        name: "Crab Cake",
        category: "starters",
        price: 700,
        description: "Maryland-style crab cakes with remoulade",
        image: "https://images.pexels.com/photos/17321100/pexels-photo-17321100.jpeg"
      },
      {
        id: 4,
        name: "Bruschetta",
        category: "starters",
        price: 350,
        description: "Grilled bread rubbed with garlic and topped with olive oil, salt and tomato",
        image: "https://images.pexels.com/photos/5150304/pexels-photo-5150304.jpeg"
      },
      {
        id: 5,
        name: "Stuffed Mushrooms",
        category: "starters",
        price: 400,
        description: "Mushroom caps filled with herbed cream cheese and breadcrumbs",
        image: "https://images.pexels.com/photos/9219091/pexels-photo-9219091.jpeg"
      },
      {
        id: 6,
        name: "Calamari",
        category: "starters",
        price: 600,
        description: "Crispy fried squid served with marinara sauce",
        image: "https://images.pexels.com/photos/15801007/pexels-photo-15801007.jpeg"
      },
      {
        id: 7,
        name: "Spinach Artichoke Dip",
        category: "starters",
        price: 500,
        description: "Creamy blend of spinach, artichokes and cheeses served with tortilla chips",
        image: "https://images.pexels.com/photos/6544374/pexels-photo-6544374.jpeg"
      },
      {
        id: 8,
        name: "Shrimp Cocktail",
        category: "starters",
        price: 650,
        description: "Chilled shrimp served with cocktail sauce",
        image:"https://images.pexels.com/photos/5192396/pexels-photo-5192396.jpeg"
      },
      {
        id: 9,
        name: "Chicken Wings",
        category: "starters",
        price: 300,
        description: "Crispy wings tossed in your choice of sauce",
        image: "https://images.pexels.com/photos/11299734/pexels-photo-11299734.jpeg"
      },
      {
        id: 10,
        name: "Caprese Salad",
        category: "starters",
        price: 400,
        description: "Fresh mozzarella, tomatoes, basil, and balsamic glaze",
        image: "https://images.pexels.com/photos/19295809/pexels-photo-19295809.jpeg"
      },
      
      // Main Course (10 items)
      {
        id: 11,
        name: "Grilled Salmon",
        category: "main",
        price: 1000,
        description: "Fresh salmon with lemon butter sauce",
        image: "https://images.pexels.com/photos/262959/pexels-photo-262959.jpeg"
      },
      {
        id: 12,
        name: "Beef Tenderloin",
        category: "main",
        price: 1300,
        description: "8oz premium cut with roasted vegetables",
        image: "https://images.pexels.com/photos/7627422/pexels-photo-7627422.jpeg"
      },
      {
        id: 13,
        name: "Mushroom Risotto",
        category: "main",
        price: 550,
        description: "Creamy arborio rice with wild mushrooms",
        image: "https://images.pexels.com/photos/7883782/pexels-photo-7883782.jpeg"
      },
      {
        id: 14,
        name: "Chicken Parmesan",
        category: "main",
        price: 600,
        description: "Breaded chicken topped with marinara and mozzarella",
        image: "https://images.pexels.com/photos/29285458/pexels-photo-29285458.jpeg"
      },
      {
        id: 15,
        name: "Filet Mignon",
        category: "main",
        price: 1500,
        description: "8oz center-cut filet with red wine reduction",
        image: "https://images.pexels.com/photos/16064370/pexels-photo-16064370.jpeg"
      },
      {
        id: 16,
        name: "Lobster Tail",
        category: "main",
        price: 1200,
        description: "8oz Maine lobster tail with drawn butter",
        image: "https://images.pexels.com/photos/18675295/pexels-photo-18675295.jpeg"
      },
      {
        id: 17,
        name: "Vegetable Paella",
        category: "main",
        price: 600,
        description: "Spanish rice with saffron and seasonal vegetables",
        image: "https://images.pexels.com/photos/31710628/pexels-photo-31710628.jpeg"
      },
      {
        id: 18,
        name: "Ribeye Steak",
        category: "main",
        price: 1400,
        description: "12oz prime ribeye with garlic mashed potatoes",
        image: "https://images.pexels.com/photos/16444386/pexels-photo-16444386.jpeg"
      },
      {
        id: 19,
        name: "Eggplant Parmesan",
        category: "main",
        price: 500,
        description: "Breaded eggplant layered with cheese and marinara",
        image: "https://images.pexels.com/photos/1527602/pexels-photo-1527602.jpeg"
      },
      {
        id: 20,
        name: "Grilled Chicken",
        category: "main",
        price: 550,
        description: "Herb-marinated chicken breast with seasonal vegetables",
        image: "https://images.pexels.com/photos/2233729/pexels-photo-2233729.jpeg"
      },
      
      // Desserts (10 items)
      {
        id: 21,
        name: "Chocolate Lava Cake",
        category: "desserts",
        price: 350,
        description: "Warm chocolate cake with molten center",
        image: "https://images.pexels.com/photos/5163948/pexels-photo-5163948.jpeg"
      },
      {
        id: 22,
        name: "Creme Brulee",
        category: "desserts",
        price: 450,
        description: "Classic vanilla custard with caramelized sugar",
        image: "https://images.pexels.com/photos/18976997/pexels-photo-18976997.jpeg"
      },
      {
        id: 23,
        name: "Tiramisu",
        category: "desserts",
        price: 500,
        description: "Coffee-flavored Italian dessert with mascarpone",
        image: "https://images.pexels.com/photos/28848709/pexels-photo-28848709.jpeg"
      },
      {
        id: 24,
        name: "Cheesecake",
        category: "desserts",
        price: 450,
        description: "New York style with berry compote",
        image: "https://images.pexels.com/photos/27721659/pexels-photo-27721659.jpeg"
      },
      {
        id: 25,
        name: "Apple Pie",
        category: "desserts",
        price: 350,
        description: "Classic American pie with vanilla ice cream",
        image: "https://images.pexels.com/photos/31020416/pexels-photo-31020416.jpeg"
      },
      {
        id: 26,
        name: "Chocolate Mousse",
        category: "desserts",
        price: 300,
        description: "Light and airy chocolate dessert",
        image: "https://images.pexels.com/photos/15023073/pexels-photo-15023073.jpeg"
      },
      {
        id: 27,
        name: "Key Lime Pie",
        category: "desserts",
        price: 400,
        description: "Tart and sweet Florida specialty",
        image: "https://images.pexels.com/photos/8330845/pexels-photo-8330845.jpeg"
      },
      {
        id: 28,
        name: "Bread Pudding",
        category: "desserts",
        price: 350,
        description: "Warm pudding with bourbon sauce",
        image: "https://images.pexels.com/photos/28097283/pexels-photo-28097283.jpeg"
      },
      {
        id: 29,
        name: "Ice Cream Sundae",
        category: "desserts",
        price: 300,
        description: "Vanilla ice cream with hot fudge and toppings",
        image: "https://images.pexels.com/photos/9501344/pexels-photo-9501344.jpeg"
      },
      {
        id: 30,
        name: "Fruit Tart",
        category: "desserts",
        price: 400,
        description: "Buttery crust with pastry cream and fresh fruit",
        image: "https://images.pexels.com/photos/461431/pexels-photo-461431.jpeg"
      },
      
      // Beverages (10 items)
      {
        id: 31,
        name: "Red Wine",
        category: "beverages",
        price: 600,
        description: "Glass of premium Cabernet Sauvignon",
        image: "https://images.pexels.com/photos/95960/pexels-photo-95960.jpeg"
      },
      {
        id: 32,
        name: "Craft Cocktail",
        category: "beverages",
        price: 500,
        description: "Seasonal ingredients, house-made syrups",
        image: "https://images.pexels.com/photos/8084719/pexels-photo-8084719.jpeg"
      },
      {
        id: 33,
        name: "Local Beer",
        category: "beverages",
        price: 250,
        description: "Rotating selection of craft brews",
        image: "https://images.pexels.com/photos/3660307/pexels-photo-3660307.jpeg"
      },
      {
        id: 34,
        name: "Iced Tea",
        category: "beverages",
        price: 150,
        description: "Freshly brewed sweet or unsweetened",
        image: "https://images.pexels.com/photos/16826278/pexels-photo-16826278.jpeg"
      },
      {
        id: 35,
        name: "Lemonade",
        category: "beverages",
        price: 130,
        description: "Homemade with fresh lemons",
        image: "https://images.pexels.com/photos/31000076/pexels-photo-31000076.jpeg"
      },
      {
        id: 36,
        name: "Espresso",
        category: "beverages",
        price: 120,
        description: "Single or double shot",
        image: "https://images.pexels.com/photos/32339281/pexels-photo-32339281.jpeg"
      },
      {
        id: 37,
        name: "Cappuccino",
        category: "beverages",
        price: 180,
        description: "Espresso with steamed milk foam",
        image: "https://images.pexels.com/photos/8488379/pexels-photo-8488379.jpeg"
      },
      {
        id: 38,
        name: "White Wine",
        category: "beverages",
        price: 600,
        description: "Glass of Chardonnay or Sauvignon Blanc",
        image: "https://images.pexels.com/photos/11238673/pexels-photo-11238673.jpeg"
      },
      {
        id: 39,
        name: "Sparkling Water",
        category: "beverages",
        price: 150,
        description: "Imported Italian sparkling water",
        image: "https://images.pexels.com/photos/26859058/pexels-photo-26859058.jpeg"
      },
      {
        id: 40,
        name: "Fresh Juice",
        category: "beverages",
        price: 180,
        description: "Daily selection of fresh squeezed juices",
        image: "https://images.pexels.com/photos/11009201/pexels-photo-11009201.jpeg"
      }
    ];

    // DOM Elements
    const menuContainer = document.getElementById('menu-container');
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const headerCart = document.getElementById('header-cart');
    const headerCartCount = document.getElementById('header-cart-count');
    const closeCart = document.getElementById('close-cart');
    const checkoutBtn = document.getElementById('checkout-btn');
    const notification = document.getElementById('notification');
    const filterButtons = document.querySelectorAll('.menu-filters li');

    // Cart state
    let cart = [];

    // Initialize the page
    function init() {
      renderMenu();
      setupEventListeners();
    }

    // Render menu items
    function renderMenu(filter = 'all') {
      menuContainer.innerHTML = '';
      
      const filteredItems = filter === 'all' 
        ? menuItems 
        : menuItems.filter(item => item.category === filter);
      
      filteredItems.forEach(item => {
        const menuItem = document.createElement('div');
        menuItem.className = 'menu-item';
        menuItem.dataset.category = item.category;
        menuItem.innerHTML = `
          <img src="${item.image}" class="menu-item-img" alt="${item.name}">
          <div class="menu-item-content">
            <div class="menu-item-title">
              <span>${item.name}</span>
              <span class="menu-item-price">₹${item.price}</span>
            </div>
            <p class="menu-item-desc">${item.description}</p>
            <div class="menu-item-actions">
              <div class="quantity-control">
                <button class="quantity-btn minus" data-id="${item.id}">-</button>
                <input type="text" class="quantity-input" value="1" data-id="${item.id}" readonly>
                <button class="quantity-btn plus" data-id="${item.id}">+</button>
              </div>
              <button class="add-to-cart" data-id="${item.id}">Add</button>
              <button class="order-now" data-id="${item.id}">Order Now</button>
            </div>
          </div>
        `;
        menuContainer.appendChild(menuItem);
      });
    }

    // Setup event listeners
    function setupEventListeners() {
      // Filter buttons
      filterButtons.forEach(button => {
        button.addEventListener('click', () => {
          filterButtons.forEach(btn => btn.classList.remove('filter-active'));
          button.classList.add('filter-active');
          renderMenu(button.dataset.category);
        });
      });
      
      // Cart toggle in header
      headerCart.addEventListener('click', () => {
        cartSidebar.classList.add('open');
      });
      
      // Close cart
      closeCart.addEventListener('click', () => {
        cartSidebar.classList.remove('open');
      });
      
      // Add to cart (delegated)
      menuContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('add-to-cart')) {
          const itemId = parseInt(e.target.dataset.id);
          const quantityInput = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
          const quantity = parseInt(quantityInput.value);
          addToCart(itemId, quantity);
        }
        
        // Order Now (delegated)
        if (e.target.classList.contains('order-now')) {
          const itemId = parseInt(e.target.dataset.id);
          const quantityInput = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
          const quantity = parseInt(quantityInput.value);
          addToCart(itemId, quantity);
          cartSidebar.classList.add('open');
          showNotification('Item added to cart. Ready to checkout!');
        }
        
        // Quantity controls
        if (e.target.classList.contains('quantity-btn')) {
          const itemId = parseInt(e.target.dataset.id);
          const quantityInput = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
          let quantity = parseInt(quantityInput.value);
          
          if (e.target.classList.contains('plus')) {
            quantity++;
          } else if (e.target.classList.contains('minus') && quantity > 1) {
            quantity--;
          }
          
          quantityInput.value = quantity;
        }
      });
      
      // Checkout button
      checkoutBtn.addEventListener('click', () => {
        if (cart.length === 0) {
          showNotification('Your cart is empty!');
          return;
        }
        
        placeOrder();
      });
      
      // Cart item removal (delegated)
      cartItems.addEventListener('click', (e) => {
        if (e.target.classList.contains('cart-item-remove')) {
          const itemId = parseInt(e.target.dataset.id);
          removeFromCart(itemId);
        }
      });
    }

    // Add item to cart
    function addToCart(itemId, quantity) {
      const menuItem = menuItems.find(item => item.id === itemId);
      if (!menuItem) return;
      
      const existingItem = cart.find(item => item.id === itemId);
      
      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        cart.push({
          id: menuItem.id,
          name: menuItem.name,
          price: menuItem.price,
          quantity: quantity,
          image: menuItem.image
        });
      }
      
      updateCart();
      showNotification(`${quantity} ${menuItem.name} added to cart`);
    }

    // Remove item from cart
    function removeFromCart(itemId) {
      cart = cart.filter(item => item.id !== itemId);
      updateCart();
    }

    // Update cart display
    function updateCart() {
      cartItems.innerHTML = '';
      
      if (cart.length === 0) {
        cartItems.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">Your cart is empty</p>';
        cartTotal.textContent = '0';
        headerCartCount.textContent = '0';
        return;
      }
      
      let total = 0;
      
      cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
          <img src="${item.image}" class="cart-item-img" alt="${item.name}">
          <button class="cart-item-remove" data-id="${item.id}">&times;</button>
          <div class="cart-item-details">
            <h4 class="cart-item-title">${item.name}</h4>
            <div class="cart-item-price">₹${item.price} × ${item.quantity}</div>
          </div>
        `;
        cartItems.appendChild(cartItem);
      });
      
      cartTotal.textContent = total;
      headerCartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    }

    // Place order
    function placeOrder() {
      // In a real app, this would send the order to your backend
      console.log('Order placed:', cart);
      showNotification('Order placed successfully!');
      cart = [];
      updateCart();
      cartSidebar.classList.remove('open');
    }

    // Show notification
    function showNotification(message) {
      notification.textContent = message;
      notification.style.display = 'block';
      
      setTimeout(() => {
        notification.style.display = 'none';
      }, 3000);
    }

    // Initialize the app
    document.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>