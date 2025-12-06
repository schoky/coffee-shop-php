<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Корзина</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: var(--white);
            border-radius: var(--radius);
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #e6e6e6;
            border-radius: 4px;
            text-align: center;
        }
        .remove-btn {
            color: #dc3545;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        .remove-btn:hover {
            background: #f8f9fa;
        }
        .cart-summary {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 24px;
            text-align: center;
        }
        .cart-empty {
            text-align: center;
            padding: 60px 20px;
        }
        .cart-empty h2 {
            color: var(--muted);
            margin-bottom: 16px;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .cart-total {
            font-size: 24px;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 20px;
        }
        .continue-shopping {
            display: inline-block;
            margin-top: 16px;
            color: var(--accent);
            text-decoration: none;
        }
        .continue-shopping:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>
<?php
session_start();
if (!isset($_COOKIE['name'])) {
    echo '<main class="container">
            <div class="cart-empty">
                <h2>Войдите в систему, чтобы просмотреть корзину</h2>
                <a href="auth.php" class="btn">Войти</a>
            </div>
          </main>';
    require "block/footer.php";
    exit;
}
?>
<main class="container">
    <div class="cart-container">
        <h1>Корзина</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                ✅ Товар успешно добавлен в корзину!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                ❌ Произошла ошибка. Пожалуйста, попробуйте снова.
            </div>
        <?php endif; ?>

        <?php
        require_once 'lib/db.php';

        try {
            // Получаем товары в корзине для текущего пользователя
            $sql = 'SELECT c.id as cart_id, c.quantity, p.id as product_id, p.text, p.price, p.image 
            FROM cart c 
            JOIN photo p ON c.product_id = p.id 
            WHERE c.user_id = (SELECT id FROM users WHERE name = ?) 
            ORDER BY c.created_at DESC';
            $query = $pdo->prepare($sql);
            $query->execute([$_COOKIE['name']]);
            $cart_items = $query->fetchAll(PDO::FETCH_OBJ);

            if (empty($cart_items)) {
                echo '<div class="cart-empty">
                        <h2>Ваша корзина пуста</h2>
                        <p>Добавьте товары из каталога</p>
                        <a href="products.php" class="btn">Перейти в каталог</a>
                        <br>
                        <a href="index.php" class="continue-shopping">Вернуться на главную</a>
                      </div>';
            } else {
                $total = 0;
                echo '<div class="cart-items">';

                foreach ($cart_items as $item) {
                    $item_total = $item->price * $item->quantity;
                    $total += $item_total;

                    echo '<div class="cart-item">
                            <img src="image/'.$item->image.'" alt="'.$item->text.'">
                            <div class="cart-item-info">
                                <h3>'.$item->text.'</h3>
                                <p class="price">₽'.$item->price.' за шт.</p>
                                <p>Количество: '.$item->quantity.'</p>
                                <p><strong>Стоимость: ₽'.$item_total.'</strong></p>
                            </div>
                            <div class="cart-item-actions">
                                <form action="lib/update-cart.php" method="post" style="display: flex; align-items: center; gap: 8px;">
                                    <input type="hidden" name="cart_id" value="'.$item->cart_id.'">
                                    <input type="number" name="quantity" value="'.$item->quantity.'" min="1" max="99" class="quantity-input">
                                    <button type="submit" class="btn small">Обновить</button>
                                </form>
                                <a href="lib/remove-from-cart.php?id='.$item->cart_id.'" class="remove-btn" onclick="return confirm(\'Вы уверены, что хотите удалить этот товар из корзины?\')">Удалить</a>
                            </div>
                          </div>';
                }

                echo '</div>';

                echo '<div class="cart-summary">
                        <div class="cart-total">Общая сумма: ₽'.$total.'</div>
                        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                            <a href="checkout.php" class="btn" style="padding: 12px 24px;">Оформить заказ</a>
                            <a href="products.php" class="btn ghost">Продолжить покупки</a>
                        </div>
                      </div>';
            }
        } catch (PDOException $e) {
            error_log("Cart error: " . $e->getMessage());
            echo '<div class="error-message">
            <h3>Ошибка загрузки корзины</h3>
            <p>Не удалось загрузить данные корзины. Пожалуйста, попробуйте позже.</p>
            <p><small>Ошибка: ' . $e->getMessage() . '</small></p>
          </div>';
        }
        ?>
    </div>
</main>
<?php require "block/footer.php" ?>
<script>
    // Добавляем подтверждение при удалении товара
    document.addEventListener('DOMContentLoaded', function() {
        const removeLinks = document.querySelectorAll('.remove-btn');
        removeLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Вы уверены, что хотите удалить этот товар из корзины?')) {
                    e.preventDefault();
                }
            });
        });

        // Добавляем валидацию для полей количества
        const quantityInputs = document.querySelectorAll('.quantity-input');
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
                if (this.value > 99) {
                    this.value = 99;
                }
            });
        });
    });
</script>
</body>
</html>