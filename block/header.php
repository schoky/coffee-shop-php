<?php
session_start();
$cart_count = 0;
$cart_total_items = 0; // Общее количество товаров
$is_logged_in = false;
$bonus_balance = 0;
$is_admin = isset($_SESSION['admin']); // Проверяем, авторизован ли администратор

if (isset($_COOKIE['name'])) {
    $db_path = __DIR__ . '/../lib/db.php';
    if (file_exists($db_path)) {
        require_once $db_path;
    } else {
        // Альтернативный путь для админ-панели
        $db_path = __DIR__ . '/../../lib/db.php';
        if (file_exists($db_path)) {
            require_once $db_path;
        } else {
            error_log("DB file not found: " . $db_path);
        }
    }

    // Считаем общее количество товаров (сумма quantity)
    $sql = 'SELECT SUM(quantity) as total FROM cart WHERE user_id = (SELECT id FROM users WHERE name = ?)';
    $query = $pdo->prepare($sql);
    $query->execute([$_COOKIE['name']]);
    $result = $query->fetch(PDO::FETCH_OBJ);
    $cart_total_items = $result ? (int)$result->total : 0;

    // Количество позиций
    $sql_count = 'SELECT COUNT(*) as count FROM cart WHERE user_id = (SELECT id FROM users WHERE name = ?)';
    $query_count = $pdo->prepare($sql_count);
    $query_count->execute([$_COOKIE['name']]);
    $result_count = $query_count->fetch(PDO::FETCH_OBJ);
    $cart_count = $result_count ? $result_count->count : 0;

    $is_logged_in = true;
    $sql_bonus = 'SELECT bonus_balance FROM users WHERE name = ?';
    $query_bonus = $pdo->prepare($sql_bonus);
    $query_bonus->execute([$_COOKIE['name']]);
    $user_bonus = $query_bonus->fetch(PDO::FETCH_OBJ);
    if ($user_bonus) {
        $bonus_balance = $user_bonus->bonus_balance;
    }
}
?>
<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">Реалист</a>

        <!-- Основная навигация для десктопа -->
        <nav class="main-nav desktop-nav">
            <a href="products.php">Каталог</a>
            <a href="about.php">О нас</a>
            <a href="contact.php">Контакты</a>

            <?php if ($is_logged_in): ?>
                <a href="order-history.php">История заказов</a>
                <a href="order-emails.php">Мои письма</a>
            <?php else: ?>
                <a href="reagpage.php">Регистрация</a>
                <a href="auth.php">Вход</a>
            <?php endif; ?>
        </nav>

        <!-- Контейнер для корзины, профиля, админки, выхода и бургер-меню -->
        <div class="header-right">
            <?php if ($is_logged_in): ?>
                <div class="desktop-controls">
                    <!-- Кнопка корзины -->
                    <a href="cart.php" class="cart-btn-desktop">
                        <span class="cart-icon-desktop">🛒</span>
                        <span class="cart-text-desktop">Корзина</span>
                        <?php if ($cart_total_items > 0): ?>
                            <span class="cart-count-badge"><?php echo $cart_total_items; ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Кнопка профиля -->
                    <a href="Userpage.php" class="profile-btn">
                        <span class="profile-icon">👤</span>
                        <span class="profile-text">Профиль</span>
                        <?php if ($bonus_balance > 0): ?>
                            <span class="profile-bonus-badge">
                                <span class="bonus-icon-small">🎁</span>
                                <span class="bonus-value-small"><?php echo number_format($bonus_balance, 0); ?></span>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Ссылка на админ-панель для ПК -->
<!--                    --><?php //if (isset($_SESSION['admin'])): ?>
<!--                        <a href="admin/index.php" class="admin-btn">-->
<!--                            <span class="admin-icon">⚙️</span>-->
<!--                            <span class="admin-text">Админка</span>-->
<!--                        </a>-->
<!--                    --><?php //else: ?>
<!--                        <a href="admin/login.php" class="admin-btn">-->
<!--                            <span class="admin-icon">⚙️</span>-->
<!--                            <span class="admin-text">Админка</span>-->
<!--                        </a>-->
<!--                    --><?php //endif; ?>

                    <a href="lib/logout.php" class="logout-btn">
                        <span class="logout-icon">🚪</span>
                        <span class="logout-text">Выход</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Бургер-кнопка -->
            <button class="burger-menu-btn" id="burgerMenuBtn" aria-label="Открыть меню">
                <span class="burger-line"></span>
                <span class="burger-line"></span>
                <span class="burger-line"></span>
            </button>
        </div>
    </div>
</header>

<!-- Бургер-меню -->
<div class="burger-menu-overlay" id="burgerMenuOverlay"></div>
<nav class="burger-menu" id="burgerMenu">
    <div class="burger-menu-header">
        <button class="burger-close-btn" id="burgerCloseBtn" aria-label="Закрыть меню">
            <span class="close-line"></span>
            <span class="close-line"></span>
        </button>
    </div>

    <div class="burger-menu-content">
        <?php if ($is_logged_in): ?>
            <div class="burger-user-section">
                <div class="burger-user-info">
                    <div class="burger-avatar">
                        👤
                    </div>
                    <div class="burger-user-details">
                        <span class="burger-username"><?php echo htmlspecialchars($_COOKIE['name']); ?></span>
                        <?php if ($bonus_balance > 0): ?>
                            <div class="burger-bonus-counter">
                                <span class="bonus-icon">🎁</span>
                                <span class="bonus-value"><?php echo number_format($bonus_balance, 0); ?> бонусов</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="burger-links">
            <a href="products.php" class="burger-link">
                <span class="burger-link-icon">📦</span>
                <span class="burger-link-text">Каталог</span>
            </a>
            <a href="about.php" class="burger-link">
                <span class="burger-link-icon">🏢</span>
                <span class="burger-link-text">О нас</span>
            </a>
            <a href="contact.php" class="burger-link">
                <span class="burger-link-icon">📞</span>
                <span class="burger-link-text">Контакты</span>
            </a>

            <?php if ($is_logged_in): ?>
                <div class="burger-divider"></div>

                <a href="cart.php" class="burger-link">
                    <span class="burger-link-icon">🛒</span>
                    <span class="burger-link-text">Корзина</span>
                    <?php if ($cart_total_items > 0): ?>
                        <span class="burger-cart-count"><?php echo $cart_total_items; ?></span>
                    <?php endif; ?>
                </a>

                <a href="Userpage.php" class="burger-link">
                    <span class="burger-link-icon">👤</span>
                    <span class="burger-link-text">Профиль</span>
                </a>

                <a href="order-history.php" class="burger-link">
                    <span class="burger-link-icon">📋</span>
                    <span class="burger-link-text">История заказов</span>
                </a>

                <a href="order-emails.php" class="burger-link">
                    <span class="burger-link-icon">📧</span>
                    <span class="burger-link-text">Мои письма</span>
                </a>

                <div class="burger-divider"></div>

                <!-- Ссылка на админ-панель в бургер-меню -->
<!--                --><?php //if (isset($_SESSION['admin'])): ?>
<!--                    <a href="admin/index.php" class="burger-link admin-link">-->
<!--                        <span class="burger-link-icon">⚙️</span>-->
<!--                        <span class="burger-link-text">Админ-панель</span>-->
<!--                    </a>-->
<!--                --><?php //else: ?>
<!--                    <a href="admin/login.php" class="burger-link admin-link">-->
<!--                        <span class="burger-link-icon">⚙️</span>-->
<!--                        <span class="burger-link-text">Админка</span>-->
<!--                    </a>-->
<!--                --><?php //endif; ?>

                <a href="lib/logout.php" class="burger-link logout-link">
                    <span class="burger-link-icon">🚪</span>
                    <span class="burger-link-text">Выход</span>
                </a>
            <?php else: ?>
                <div class="burger-divider"></div>

                <a href="reagpage.php" class="burger-link">
                    <span class="burger-link-icon">📝</span>
                    <span class="burger-link-text">Регистрация</span>
                </a>

                <a href="auth.php" class="burger-link">
                    <span class="burger-link-icon">🔑</span>
                    <span class="burger-link-text">Вход</span>
                </a>

                <div class="burger-divider"></div>

<!--                <a href="admin/login.php" class="burger-link admin-link">-->
<!--                    <span class="burger-link-icon">⚙️</span>-->
<!--                    <span class="burger-link-text">Админка</span>-->
<!--                </a>-->
            <?php endif; ?>
        </div>
    </div>
</nav>