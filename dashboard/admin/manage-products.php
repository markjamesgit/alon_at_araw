<?php
// Define allowed tab filenames
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
$allowed_tabs = ['flavors', 'cup-sizes', 'products', 'product-components', 'addons'];
$tab_titles = [
  'products' => ['title' => 'Manage Products', 'desc' => 'View and manage your store products.'],
  'flavors' => ['title' => 'Manage Flavors', 'desc' => 'Customize the available flavors for your products.'],
  'addons' => ['title' => 'Manage Add-ons', 'desc' => 'Add optional extras or toppings to your products.'],
  'product-components' => ['title' => 'Product Components', 'desc' => 'Organize and manage ingredients or components used in products.'],
  'cup-sizes' => ['title' => 'Cup Sizes', 'desc' => 'Define the cup sizes available for your beverages.']
];

$current_tab_title = $tab_titles[$tab]['title'];
$current_tab_desc = $tab_titles[$tab]['desc'];

if (!in_array($tab, $allowed_tabs)) {
  $tab = 'products'; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Products - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-admin.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/manage-products.css"/>

  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<div class="main-container">
  <?php include '../../includes/admin-sidebar.php'; ?>
  <div class="content-wrapper">
    <main>
      <h1><?= htmlspecialchars($current_tab_title) ?></h1>
      <p><?= htmlspecialchars($current_tab_desc) ?></p>

      <!--Tab Navigation -->
<div class="md-tabs">
  <a href="?tab=products" class="md-tab <?= $tab == 'products' ? 'active' : '' ?>">Products</a>
  <a href="?tab=flavors" class="md-tab <?= $tab == 'flavors' ? 'active' : '' ?>">Flavors</a>
  <a href="?tab=addons" class="md-tab <?= $tab == 'addons' ? 'active' : '' ?>">Add-ons</a>
  <a href="?tab=cup-sizes" class="md-tab <?= $tab == 'cup-sizes' ? 'active' : '' ?>">Cup Sizes</a>
  <a href="?tab=product-components" class="md-tab <?= $tab == 'product-components' ? 'active' : '' ?>">Product Components</a>
</div>

      <!-- Dynamic Tab Content -->
      <div class="tab-content">
        <?php include "tabs/$tab.php"; ?>
      </div>

    </main>
  </div>
</div>
</body>
</html>
