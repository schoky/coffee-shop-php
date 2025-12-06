<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — О нас</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <h1>О магазине</h1>
    <p>Реалист - кофе, в котором нет места случайностям. Только продуманный выбор от зерна до чашки.</p>

    <h2>Наша философия</h2>
    <p>«Реалист» — это больше, чем просто кофейный магазин. Это наш взгляд на то, каким должен быть настоящий кофе.</p>

    <h2>Наш адрес</h2>

    <div class="location-container">
        <div class="map-wrapper">
            <div class="map-controls">
                <button class="btn small map-expand-btn">Увеличить карту</button>
            </div>
            <div class="map-container">
                <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1998.599892054263!2d30.30866531586324!3d59.93428078187614!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4696310fca5ba0a7%3A0x5c5e5e5e5e5e5e5e!2z0JjRgdC_0LDQvdC40Y8g0KHQsNC90YLQsA!5e0!3m2!1sru!2sru!4v1759832366125!5m2!1sru!2sru"
                        width="100%"
                        height="280"
                        style="border:0; border-radius: 12px;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        class="main-map">
                </iframe>
            </div>
        </div>

        <div class="coffee-shop-info">
            <h3>Кофейня "Реалист"</h3>
            <div class="info-details">
                <div class="info-item">
                    <strong>Адрес:</strong>
                    <p>Санкт-Петербург, Исаакиевская площадь, 4</p>
                </div>
                <div class="info-item">
                    <strong>Часы работы:</strong>
                    <p>Пн-Пт: 8:00-22:00<br>Сб-Вс: 9:00-23:00</p>
                </div>
                <div class="info-item">
                    <strong>Телефон:</strong>
                    <p>+7 (812) 123-45-67</p>
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    <p>spb@realist-coffee.ru</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для увеличенной карты -->
    <div id="mapModal" class="modal-map">
        <div class="modal-map-content">
            <span class="close-map-modal">&times;</span>
            <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1998.599892054263!2d30.30866531586324!3d59.93428078187614!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4696310fca5ba0a7%3A0x5c5e5e5e5e5e5e5e!2z0JjRgdC_0LDQvdC40Y8g0KHQsNC90YLQsA!5e0!3m2!1sru!2sru!4v1759832366125!5m2!1sru!2sru"
                    width="100%"
                    height="100%"
                    style="border:0; border-radius: 12px;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</main>

<?php require "block/footer.php" ?>
<script src="js/script.js"></script>
</body>
</html>