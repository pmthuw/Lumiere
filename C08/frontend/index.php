<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LUMIERE | Hương thơm sang trọng</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Cormorant+Garamond:wght@300;400;600&family=Jost:wght@300;400;500&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../frontend/styles/style.css" />
  </head>
  <body>
    <?php include __DIR__ . '/components/header.php'; ?>
    <?php include __DIR__ . '/components/hero.php'; ?>
    <?php include __DIR__ . '/components/products-section.php'; ?>
    <?php include __DIR__ . '/components/contact-section.php'; ?>
    <?php include __DIR__ . '/components/footer.php'; ?>
    <?php include __DIR__ . '/components/modals.php'; ?>
    
    <div class="toast" id="toast"></div>

    <!-- JS Modules in dependency order -->
    <script src="js/init.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/products.js"></script>
    <script src="js/search.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/auth.js?v=20260413-4"></script>
    <script src="js/orders.js"></script>
    <script src="js/profile.js"></script>
  </body>
</html>
