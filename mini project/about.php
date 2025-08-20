<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "westleys_resto_cafe";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Why Us items
$why_us_items = $conn->query("SELECT * FROM why_us_items WHERE is_active = 1 ORDER BY number");

// Fetch About content
$about_content = $conn->query("SELECT * FROM about_content WHERE id = 1");
$about = $about_content->fetch_assoc();

// Fetch Chefs
$chefs = $conn->query("SELECT * FROM chefs WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - Westley's Resto Cafe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Font & Color Variables - Exact copy from menu page */
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

    /* General Styles - Exact copy from menu page */
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

    /* Header Styles - Exact copy from menu page */
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

    /* Section Title with Underline Animation - Exact copy from menu page */
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

    /* Main Content Padding - Matching menu page */
    .main-content {
      padding-top: 80px;
    }

    /* About Section - Using menu page's background approach */
    .about {
      background: url("assets/img/about-bg.jpg") center center;
      background-size: cover;
      position: relative;
      padding: 80px 0;
    }

    .about:before {
      content: "";
      background: color-mix(in srgb, var(--background-color), transparent 12%);
      position: absolute;
      bottom: 0;
      top: 0;
      left: 0;
      right: 0;
    }

    .about .container {
      position: relative;
      z-index: 2;
    }

    /* Why Us Section */
    .why-us {
      background-color: var(--background-color);
      padding: 80px 0;
    }

    .why-us .card-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      margin-top: 40px;
    }

    .why-us .card-item {
      background: color-mix(in srgb, var(--default-color), transparent 95%);
      padding: 40px 30px;
      transition: all ease-in-out 0.3s;
      flex: 1;
      min-width: 300px;
      max-width: 350px;
      border-radius: 5px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .why-us .card-item span {
      color: var(--accent-color);
      display: block;
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .why-us .card-item h4 {
      font-size: 24px;
      font-weight: 600;
      padding: 0;
      margin: 0 0 15px 0;
    }

    .why-us .card-item h4 a {
      color: var(--heading-color);
    }

    .why-us .card-item p {
      font-size: 15px;
      color: color-mix(in srgb, var(--default-color), transparent 40%);
      margin: 0;
      padding: 0;
    }

    .why-us .card-item:hover {
      background: var(--accent-color);
      transform: translateY(-10px);
    }

    .why-us .card-item:hover span,
    .why-us .card-item:hover h4 a,
    .why-us .card-item:hover p {
      color: var(--contrast-color);
    }

    /* Chefs Section */
    .chefs {
      --default-color: #ffffff;
      --contrast-color: #ffffff;
      background-color: var(--background-color);
      padding: 80px 0;
    }

    .chefs .chefs-container {
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
    }

    .chefs .member {
      background: color-mix(in srgb, var(--default-color), transparent 95%);
      border-radius: 5px;
      overflow: hidden;
      transition: all 0.3s;
      width: 300px;
      display: flex;
      flex-direction: column;
    }

    .chefs .member:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
    }

    .chefs .member img {
      width: 100%;
      height: 300px;
      object-fit: cover;
    }

    .chefs .member-info {
      padding: 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .chefs .member h4 {
      font-weight: 700;
      margin-bottom: 5px;
      font-size: 20px;
      color: var(--heading-color);
    }

    .chefs .member span {
      display: block;
      font-size: 15px;
      color: var(--accent-color);
      margin-bottom: 15px;
      font-style: italic;
    }

    .chefs .member p {
      color: var(--default-color);
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 0;
    }

    /* Video Container Styles */
    .about-video-container {
      position: relative;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 30px;
      overflow: hidden;
      border: 6px solid color-mix(in srgb, var(--default-color), transparent 80%);
      border-radius: 5px;
    }

    .about-video {
      width: 100%;
      max-width: 1000px;
      height: auto;
      transition: transform 0.3s;
    }

    .about-video:hover {
      transform: scale(1.03);
    }

    /* Content Styles */
    .content h3 {
      font-size: 1.75rem;
      font-weight: 700;
    }

    .content .fst-italic {
      color: color-mix(in srgb, var(--default-color), var(--contrast-color) 50%);
      font-style: italic;
    }

    .content ul {
      list-style: none;
      padding: 0;
    }

    .content ul li {
      padding: 10px 0 0 0;
      display: flex;
    }

    .content ul i {
      color: var(--accent-color);
      margin-right: 0.5rem;
      line-height: 1.2;
      font-size: 1.25rem;
    }

    .content p:last-child {
      margin-bottom: 0;
    }

    /* Responsive adjustments - Exact copy from menu page with about additions */
    @media (max-width: 992px) {
      .chefs .chefs-container,
      .why-us .card-container {
        flex-direction: column;
        align-items: center;
      }
      
      .chefs .member,
      .why-us .card-item {
        width: 100%;
        max-width: 400px;
        margin-bottom: 30px;
      }
    }

    @media (max-width: 768px) {
      .about,
      .why-us,
      .chefs {
        padding: 60px 0;
      }
      
      .section-title p {
        font-size: 28px;
      }
      
      .header .logo h1 {
        font-size: 24px;
      }
    }
  </style>
</head>
<body>

  <!-- Header - Exact copy from menu page -->
  <header class="header">
    <div class="branding">
      <div class="container">
        <div class="logo">
          <img src="img.png" alt="Westley's Resto Cafe">
          <h1>Westley's Resto Cafe </h1>
        </div>
      </div>
    </div>
  </header>

  <main class="main-content">
    <!-- Why Us Section - Moved to top -->
    <section id="why-us" class="why-us section">
      <div class="container">
        <div class="section-title">
          <h2>WHY US</h2>
          <p>Why Choose Our Restaurant</p>
        </div>
        
        <div class="card-container">
          <?php if ($why_us_items->num_rows > 0): ?>
            <?php while($item = $why_us_items->fetch_assoc()): ?>
            <div class="card-item">
              <span><?php echo htmlspecialchars($item['number']); ?></span>
              <h4><a href="#" class="stretched-link"><?php echo htmlspecialchars($item['title']); ?></a></h4>
              <p><?php echo htmlspecialchars($item['description']); ?></p>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Fallback content if no items in database -->
            <div class="card-item">
              <span>01</span>
              <h4><a href="#" class="stretched-link">Premium Ingredients</a></h4>
              <p>We source only the finest, freshest ingredients from trusted local suppliers and international markets to ensure exceptional quality in every dish.</p>
            </div>

            <div class="card-item">
              <span>02</span>
              <h4><a href="#" class="stretched-link">Masterful Techniques</a></h4>
              <p>Our chefs combine traditional cooking methods with innovative approaches to create unforgettable culinary experiences that delight all senses.</p>
            </div>

            <div class="card-item">
              <span>03</span>
              <h4><a href="#" class="stretched-link">Impeccable Service</a></h4>
              <p>From the moment you arrive until your last bite, our attentive staff provides warm, professional service tailored to your preferences.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Why Us Section -->

    <!-- About Section -->
    <section id="about" class="about section">
      <div class="container">
        <div class="section-title">
          <h2>ABOUT</h2>
          <p>About Our Restaurant</p>
        </div>
        <div class="row">

          <div class="about-video-container">
            <?php if (isset($about['video_path'])): ?>
            <video class="about-video" autoplay muted loop playsinline>
              <source src="<?php echo htmlspecialchars($about['video_path']); ?>" type="video/mp4">
              <?php if (isset($about['fallback_image'])): ?>
              <img src="<?php echo htmlspecialchars($about['fallback_image']); ?>" class="img-fluid" alt="Our Restaurant">
              <?php else: ?>
              <img src="assets/img/about.jpg" class="img-fluid" alt="Our Restaurant">
              <?php endif; ?>
            </video>
            <?php else: ?>
            <!-- Fallback video if none in database -->
            <video class="about-video" autoplay muted loop playsinline>
              <source src="cook1.mp4" type="video/mp4">
              <img src="assets/img/about.jpg" class="img-fluid" alt="Our Restaurant">
            </video>
            <?php endif; ?>
          </div>

          <div class="col-lg-6 order-2 order-lg-1 content">
            <?php if (isset($about['title'])): ?>
            <h3><?php echo htmlspecialchars($about['title']); ?></h3>
            <?php else: ?>
            <h3>Our Culinary Journey</h3>
            <?php endif; ?>
            
            <?php if (isset($about['subtitle'])): ?>
            <p class="fst-italic">
              <?php echo htmlspecialchars($about['subtitle']); ?>
            </p>
            <?php else: ?>
            <p class="fst-italic">
              Founded in 2020, our restaurant has been serving exquisite dishes that blend traditional flavors with contemporary techniques.
            </p>
            <?php endif; ?>
            
            <?php if (isset($about['content'])): ?>
            <?php echo $about['content']; ?>
            <?php else: ?>
            <ul>
              <li><i>✓</i> <span>Michelin-starred chefs crafting each menu with passion and precision</span></li>
              <li><i>✓</i> <span>Seasonal menus featuring locally-sourced, sustainable ingredients</span></li>
              <li><i>✓</i> <span>An award-winning wine cellar with over 500 selections from around the world</span></li>
            </ul>
            <p>
              Our philosophy centers on creating memorable dining experiences through exceptional food, impeccable service, and a warm, inviting atmosphere. We believe every meal should be a celebration of flavor, artistry, and hospitality.
            </p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section><!-- /About Section -->

    <!-- Chefs Section -->
    <section id="chefs" class="chefs section">
      <div class="container">
        <div class="section-title">
          <h2>TEAM</h2>
          <p>Our Professional Chefs</p>
        </div>

        <div class="chefs-container">
          <?php if ($chefs->num_rows > 0): ?>
            <?php while($chef = $chefs->fetch_assoc()): ?>
            <div class="member">
              <img src="<?php echo htmlspecialchars($chef['image_path']); ?>" class="img-fluid" alt="Chef <?php echo htmlspecialchars($chef['name']); ?>">
              <div class="member-info">
                <h4><?php echo htmlspecialchars($chef['name']); ?></h4>
                <span><?php echo htmlspecialchars($chef['position']); ?></span>
                <p><?php echo htmlspecialchars($chef['bio']); ?></p>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Fallback content if no chefs in database -->
            <div class="member">
              <img src="masterchef.jpg" class="img-fluid" alt="Chef Walter White">
              <div class="member-info">
                <h4>Walter White</h4>
                <span>Master Chef</span>
                <p>3 Michelin-starred chef blending French techniques with Japanese precision. Creator of the acclaimed "Molecular Symphony" dish featured in Gourmet Magazine.</p>
              </div>
            </div>

            <div class="member">
              <img src="chef2.jpg" class="img-fluid" alt="Chef Sarah Jhonson">
              <div class="member-info">
                <h4>Sarah Jhonson</h4>
                <span>Patissier</span>
                <p>Le Cordon Bleu graduate and World Pastry Champion. Famous for her Instagram-famous "Deconstructed Tiramisu".</p>
              </div>
            </div>

            <div class="member">
              <img src="chef1.jpg" class="img-fluid" alt="Chef William Anderson">
              <div class="member-info">
                <h4>William Anderson</h4>
                <span>Executive Sous Chef</span>
                <p>Culinary Arts Magazine's "Young Chef of the Year". Specializes in modern classics with molecular gastronomy techniques.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Chefs Section -->
  </main>
</body>
</html>