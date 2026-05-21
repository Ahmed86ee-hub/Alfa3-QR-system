<?php
// menu.php
$restaurant_name = "مطعم الذواقة";
$theme_color = "theme-dark-gold";

// بيانات المنيو (تأتي من JSON في قاعدة البيانات)
$menu_categories = [
    'المشويات' => [
        ['name' => 'كباب لحم نعيمي', 'price' => '45 ريال', 'desc' => 'كباب طازج مع الخلطة السرية'],
        ['name' => 'شيش طاووق', 'price' => '35 ريال', 'desc' => 'قطع دجاج متبلة مشوية على الفحم']
    ],
    'المشروبات' => [
        ['name' => 'عصير برتقال فريش', 'price' => '15 ريال', 'desc' => 'عصرة طازجة بدون سكر مضاف']
    ]
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منيو | <?php echo $restaurant_name; ?></title>
    <link rel="stylesheet" href="products_style.css">
</head>
<body class="menu-page <?php echo $theme_color; ?>">

    <div class="menu-header">
        <h1><?php echo $restaurant_name; ?></h1>
        <p>قائمة الطعام الرقمية</p>
    </div>

    <div class="menu-container">
        <?php foreach ($menu_categories as $category_name => $items): ?>
            <h2 class="category-title"><?php echo $category_name; ?></h2>
            
            <div class="items-list">
                <?php foreach ($items as $item): ?>
                    <div class="menu-item">
                        <div class="item-details">
                            <h3><?php echo $item['name']; ?></h3>
                            <p><?php echo $item['desc']; ?></p>
                        </div>
                        <div class="item-price"><?php echo $item['price']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>