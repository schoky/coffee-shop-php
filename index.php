<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Главная</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
<?php require "block/header.php" ?>

<main>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-content">
                <h1>Кофе, который расслабляет</h1>
                <p>Коллекция отборного и повседневного зерна — от терпкого робуста до нежной арабики.</p>
                <a class="btn" href="products.php">Просмотреть каталог</a>
            </div>
            <div class="hero-image">
                <img src="image/coffee-cup.png" alt="Кофейная чашка">
            </div>
        </div>
    </section>

    <section class="container">
        <div class="features">
            <article class="feature">
                <h3>Натурально</h3>
                <p>Только зерно. Никаких ароматизаторов.</p>
            </article>

            <article class="feature">
                <h3>Присутствуем в десятках городов России.</h3>
                <p>Быстрая доставка по стране — от пары дней.</p>
            </article>

            <article class="feature">
                <h3>Гарантия качества от обжарщика.</h3>
                <p>Вернем деньги, если кофе не оправдает ваших ожиданий.</p>
            </article>
        </div>
    </section>

    <section class="products-preview container">
        <div class="section-header">
            <h2>Популярные сорта</h2>
        </div>
        <div class="grid">
            <?php
            require_once 'lib/db.php';

            $sql = 'SELECT * FROM photo ORDER BY RAND() LIMIT 3';
            $query = $pdo->prepare($sql);
            $query->execute();
            $products = $query->fetchAll(PDO::FETCH_OBJ);

            foreach ($products as $product)
                echo (
                        '<div class="card">
                        <img src="image/' . $product->image . '" alt="' . $product->text . '">
                        <h4>' . $product->text . '</h4>
                        <p class="price">₽' . $product->price . ' / 1000 г</p>
                        <a href="product.php?id=' . $product->id . '" class="btn small">Подробнее</a>
                    </div>'
                );
            ?>
        </div>
    </section>
</main>
<?php require "block/footer.php" ?>
<script src="js/script.js"></script>
</body>
</html>