<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Каталог</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <h1>Каталог кофе</h1>
    <p style="text-align: center; color: var(--muted); margin-bottom: 32px;">Отборные сорта со всего мира</p>

    <div class="grid">
        <?php
        require "lib/db.php";

        try {
            $sql = 'SELECT * FROM photo ORDER BY price DESC';
            $query = $pdo->prepare($sql);
            $query->execute();
            $products = $query->fetchAll(PDO::FETCH_OBJ);

            foreach($products as $product) {
                echo(
                        '<div class="card">
                        <img src="image/'.$product->image.'" alt="'.$product->text.'">
                        <h4>'.$product->text.'</h4>
                        <p class="price">₽'.$product->price.' / 1000 г</p>
                        <a href="product.php?id='.$product->id.'" class="btn small">Подробнее</a>
                    </div>'
                );
            }
        } catch (PDOException $e) {
            echo '<div class="error-message">
                    <h3>Ошибка загрузки каталога</h3>
                    <p>Пожалуйста, попробуйте позже.</p>
                  </div>';
            error_log("Products catalog error: " . $e->getMessage());
        }
        ?>
    </div>
</main>
<?php require "block/footer.php" ?>
</body>
</html>