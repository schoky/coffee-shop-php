<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Страница товара</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require "block/header.php" ?>

<main class="container product-page">
    <?php
    require_once 'lib/db.php';

    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

    try {
        $sql = 'SELECT * FROM photo WHERE id = ? LIMIT 1';
        $query = $pdo->prepare($sql);
        $query->execute([$product_id]);
        $product = $query->fetch(PDO::FETCH_OBJ);

        if (!$product) {
            echo '<div class="error-message">
                    <h2>Товар не найден</h2>
                    <p>Запрошенный товар не существует.</p>
                    <a href="products.php" class="btn">Вернуться в каталог</a>
                  </div>';
            require "block/footer.php";
            exit;
        }
    } catch (PDOException $e) {
        error_log("Product page error: " . $e->getMessage());
        echo '<div class="error-message">
                <h2>Ошибка загрузки товара</h2>
                <p>Пожалуйста, попробуйте позже.</p>
              </div>';
        require "block/footer.php";
        exit;
    }
    ?>

    <div class="product-grid">
        <div class="product-media">
            <img src="image/<?php echo $product->image; ?>" alt="<?php echo $product->text; ?>">
        </div>
        <div class="product-info">
            <h1><?php echo $product->text; ?></h1>
            <p class="price">₽<?php echo $product->price; ?> / 1000 г</p>
            <p><?php echo $product->description ?: 'Премиальный кофе с насыщенным вкусом и ароматом. Идеально подходит для приготовления в различных кофемашинах и турке.'; ?></p>

            <form action="lib/add-to-cart.php" method="post">
                <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
                <label>Количество
                    <input type="number" name="quantity" value="1" min="1" max="10">
                </label>
                <div style="margin-top:12px;">
                    <button type="submit" class="btn">Добавить в корзину</button>
                    <a class="btn ghost" href="products.php">Назад в каталог</a>
                </div>
            </form>
        </div>
    </div>
</main>
<?php require "block/footer.php" ?>
</body>
</html>