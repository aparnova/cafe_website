<?php
include('db.php');

$menuItems = [
    // Starters (10 items)
    [
        'name' => 'Lobster Bisque',
        'description' => 'Creamy soup with fresh lobster meat',
        'price' => 900,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/17598231/pexels-photo-17598231.jpeg'
    ],
    [
        'name' => 'Bread Barrel',
        'description' => 'Freshly baked artisan bread assortment',
        'price' => 200,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/1775043/pexels-photo-1775043.jpeg'
    ],
    [
        'name' => 'Crab Cake',
        'description' => 'Maryland-style crab cakes with remoulade',
        'price' => 700,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/17321100/pexels-photo-17321100.jpeg'
    ],
    [
        'name' => 'Bruschetta',
        'description' => 'Grilled bread rubbed with garlic and topped with olive oil, salt and tomato',
        'price' => 350,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/5150304/pexels-photo-5150304.jpeg'
    ],
    [
        'name' => 'Stuffed Mushrooms',
        'description' => 'Mushroom caps filled with herbed cream cheese and breadcrumbs',
        'price' => 400,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/9219091/pexels-photo-9219091.jpeg'
    ],
    [
        'name' => 'Calamari',
        'description' => 'Crispy fried squid served with marinara sauce',
        'price' => 600,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/15801007/pexels-photo-15801007.jpeg'
    ],
    [
        'name' => 'Spinach Artichoke Dip',
        'description' => 'Creamy blend of spinach, artichokes and cheeses served with tortilla chips',
        'price' => 500,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/6544374/pexels-photo-6544374.jpeg'
    ],
    [
        'name' => 'Shrimp Cocktail',
        'description' => 'Chilled shrimp served with cocktail sauce',
        'price' => 650,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/5192396/pexels-photo-5192396.jpeg'
    ],
    [
        'name' => 'Chicken Wings',
        'description' => 'Crispy wings tossed in your choice of sauce',
        'price' => 300,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/11299734/pexels-photo-11299734.jpeg'
    ],
    [
        'name' => 'Caprese Salad',
        'description' => 'Fresh mozzarella, tomatoes, basil, and balsamic glaze',
        'price' => 400,
        'category' => 'starters',
        'image' => 'https://images.pexels.com/photos/19295809/pexels-photo-19295809.jpeg'
    ],
    
    // Main Course (10 items)
    [
        'name' => 'Grilled Salmon',
        'description' => 'Fresh salmon with lemon butter sauce',
        'price' => 1000,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/262959/pexels-photo-262959.jpeg'
    ],
    [
        'name' => 'Beef Tenderloin',
        'description' => '8oz premium cut with roasted vegetables',
        'price' => 1300,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/7627422/pexels-photo-7627422.jpeg'
    ],
    [
        'name' => 'Mushroom Risotto',
        'description' => 'Creamy arborio rice with wild mushrooms',
        'price' => 550,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/7883782/pexels-photo-7883782.jpeg'
    ],
    [
        'name' => 'Chicken Parmesan',
        'description' => 'Breaded chicken topped with marinara and mozzarella',
        'price' => 600,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/29285458/pexels-photo-29285458.jpeg'
    ],
    [
        'name' => 'Filet Mignon',
        'description' => '8oz center-cut filet with red wine reduction',
        'price' => 1500,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/16064370/pexels-photo-16064370.jpeg'
    ],
    [
        'name' => 'Lobster Tail',
        'description' => '8oz Maine lobster tail with drawn butter',
        'price' => 1200,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/18675295/pexels-photo-18675295.jpeg'
    ],
    [
        'name' => 'Vegetable Paella',
        'description' => 'Spanish rice with saffron and seasonal vegetables',
        'price' => 600,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/31710628/pexels-photo-31710628.jpeg'
    ],
    [
        'name' => 'Ribeye Steak',
        'description' => '12oz prime ribeye with garlic mashed potatoes',
        'price' => 1400,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/16444386/pexels-photo-16444386.jpeg'
    ],
    [
        'name' => 'Eggplant Parmesan',
        'description' => 'Breaded eggplant layered with cheese and marinara',
        'price' => 500,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/1527602/pexels-photo-1527602.jpeg'
    ],
    [
        'name' => 'Grilled Chicken',
        'description' => 'Herb-marinated chicken breast with seasonal vegetables',
        'price' => 550,
        'category' => 'main',
        'image' => 'https://images.pexels.com/photos/2233729/pexels-photo-2233729.jpeg'
    ],
    
    // Desserts (10 items)
    [
        'name' => 'Chocolate Lava Cake',
        'description' => 'Warm chocolate cake with molten center',
        'price' => 350,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/5163948/pexels-photo-5163948.jpeg'
    ],
    [
        'name' => 'Creme Brulee',
        'description' => 'Classic vanilla custard with caramelized sugar',
        'price' => 450,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/18976997/pexels-photo-18976997.jpeg'
    ],
    [
        'name' => 'Tiramisu',
        'description' => 'Coffee-flavored Italian dessert with mascarpone',
        'price' => 500,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/28848709/pexels-photo-28848709.jpeg'
    ],
    [
        'name' => 'Cheesecake',
        'description' => 'New York style with berry compote',
        'price' => 450,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/27721659/pexels-photo-27721659.jpeg'
    ],
    [
        'name' => 'Apple Pie',
        'description' => 'Classic American pie with vanilla ice cream',
        'price' => 350,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/31020416/pexels-photo-31020416.jpeg'
    ],
    [
        'name' => 'Chocolate Mousse',
        'description' => 'Light and airy chocolate dessert',
        'price' => 300,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/15023073/pexels-photo-15023073.jpeg'
    ],
    [
        'name' => 'Key Lime Pie',
        'description' => 'Tart and sweet Florida specialty',
        'price' => 400,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/8330845/pexels-photo-8330845.jpeg'
    ],
    [
        'name' => 'Bread Pudding',
        'description' => 'Warm pudding with bourbon sauce',
        'price' => 350,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/28097283/pexels-photo-28097283.jpeg'
    ],
    [
        'name' => 'Ice Cream Sundae',
        'description' => 'Vanilla ice cream with hot fudge and toppings',
        'price' => 300,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/9501344/pexels-photo-9501344.jpeg'
    ],
    [
        'name' => 'Fruit Tart',
        'description' => 'Buttery crust with pastry cream and fresh fruit',
        'price' => 400,
        'category' => 'desserts',
        'image' => 'https://images.pexels.com/photos/461431/pexels-photo-461431.jpeg'
    ],
    
    // Beverages (10 items)
    [
        'name' => 'Red Wine',
        'description' => 'Glass of premium Cabernet Sauvignon',
        'price' => 600,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/95960/pexels-photo-95960.jpeg'
    ],
    [
        'name' => 'Craft Cocktail',
        'description' => 'Seasonal ingredients, house-made syrups',
        'price' => 500,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/8084719/pexels-photo-8084719.jpeg'
    ],
    [
        'name' => 'Local Beer',
        'description' => 'Rotating selection of craft brews',
        'price' => 250,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/3660307/pexels-photo-3660307.jpeg'
    ],
    [
        'name' => 'Iced Tea',
        'description' => 'Freshly brewed sweet or unsweetened',
        'price' => 150,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/16826278/pexels-photo-16826278.jpeg'
    ],
    [
        'name' => 'Lemonade',
        'description' => 'Homemade with fresh lemons',
        'price' => 130,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/31000076/pexels-photo-31000076.jpeg'
    ],
    [
        'name' => 'Espresso',
        'description' => 'Single or double shot',
        'price' => 120,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/32339281/pexels-photo-32339281.jpeg'
    ],
    [
        'name' => 'Cappuccino',
        'description' => 'Espresso with steamed milk foam',
        'price' => 180,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/8488379/pexels-photo-8488379.jpeg'
    ],
    [
        'name' => 'White Wine',
        'description' => 'Glass of Chardonnay or Sauvignon Blanc',
        'price' => 600,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/11238673/pexels-photo-11238673.jpeg'
    ],
    [
        'name' => 'Sparkling Water',
        'description' => 'Imported Italian sparkling water',
        'price' => 150,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/26859058/pexels-photo-26859058.jpeg'
    ],
    [
        'name' => 'Fresh Juice',
        'description' => 'Daily selection of fresh squeezed juices',
        'price' => 180,
        'category' => 'beverages',
        'image' => 'https://images.pexels.com/photos/11009201/pexels-photo-11009201.jpeg'
    ]
];

// Prepare SQL statement
$sql = "INSERT INTO menu_items (name, description, price, category, image) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Counter for successful inserts
$inserted = 0;

// Insert each item
foreach ($menuItems as $item) {
    $stmt->bind_param("ssdss", 
        $item['name'], 
        $item['description'], 
        $item['price'], 
        $item['category'], 
        $item['image']);
    
    if ($stmt->execute()) {
        $inserted++;
    } else {
        echo "Error inserting " . $item['name'] . ": " . $stmt->error . "<br>";
    }
}

echo "Successfully inserted $inserted menu items.";

$stmt->close();
$conn->close();
?>