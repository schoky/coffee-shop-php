<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

$validation_errors = [];
if (isset($_SESSION['checkout_errors'])) {
    $validation_errors = $_SESSION['checkout_errors'];
    unset($_SESSION['checkout_errors']);
}

$form_data = [];
if (isset($_SESSION['checkout_form_data'])) {
    $form_data = $_SESSION['checkout_form_data'];
    unset($_SESSION['checkout_form_data']);
}

$bonus_balance = 0;
$is_bonus_program = false;
$max_bonus_use = 0;

require_once 'lib/db.php';
try {
    $sql = 'SELECT bonus_balance, is_bonus_program FROM users WHERE name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$_COOKIE['name']]);
    $user = $query->fetch(PDO::FETCH_OBJ);

    if ($user) {
        $bonus_balance = $user->bonus_balance;
        $is_bonus_program = $user->is_bonus_program;
    }
} catch (PDOException $e) {
    error_log("Bonus balance error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Оформление заказа</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 24px;
        }
        .order-summary {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .order-items {
            margin-bottom: 20px;
        }
        .order-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-total {
            font-size: 20px;
            font-weight: 600;
            color: var(--accent);
            text-align: center;
            padding: 16px 0;
            border-top: 2px solid var(--accent);
            margin-top: 16px;
        }
        .checkout-form {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--muted);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .empty-cart-message {
            text-align: center;
            padding: 60px 20px;
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
        .payment-methods {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }
        .payment-method {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid #e6e6e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: var(--accent);
        }
        .payment-method.selected {
            border-color: var(--accent);
            background-color: rgba(107, 79, 43, 0.05);
        }
        .payment-method input {
            display: none;
        }
        .char-counter {
            font-size: 12px;
            color: var(--muted);
            text-align: right;
            margin-top: 4px;
        }
        .char-counter.warning {
            color: #dc3545;
        }
        .field-error {
            border-color: #dc3545 !important;
        }
        .error-list {
            margin: 0;
            padding-left: 20px;
        }

        /* Bonus section styles */
        .bonus-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .bonus-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .bonus-balance {
            font-size: 18px;
            font-weight: 600;
            color: #28a745;
        }
        .bonus-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .bonus-input input {
            width: 120px;
            padding: 8px;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
        }
        .bonus-hint {
            font-size: 14px;
            color: var(--muted);
            margin-top: 5px;
        }
        .join-bonus-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .join-bonus-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .order-summary-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-summary-line:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <div class="checkout-container">
        <h1>Оформление заказа</h1>

        <?php if (!empty($validation_errors)): ?>
            <div class="error-message">
                <h4>Пожалуйста, исправьте следующие ошибки:</h4>
                <ul class="error-list">
                    <?php foreach ($validation_errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php
        require_once 'lib/db.php';

        try {
            $sql = 'SELECT c.quantity, p.id as product_id, p.text, p.price, p.image 
                    FROM cart c 
                    JOIN photo p ON c.product_id = p.id 
                    WHERE c.user_id = (SELECT id FROM users WHERE name = ?) 
                    ORDER BY c.created_at DESC';
            $query = $pdo->prepare($sql);
            $query->execute([$_COOKIE['name']]);
            $cart_items = $query->fetchAll(PDO::FETCH_OBJ);

            if (empty($cart_items)) {
                echo '<div class="empty-cart-message">
                        <h2>Ваша корзина пуста</h2>
                        <p>Добавьте товары из каталога, чтобы оформить заказ</p>
                        <a href="products.php" class="btn">Перейти в каталог</a>
                      </div>';
            } else {
                $total = 0;
                foreach ($cart_items as $item) {
                    $item_total = $item->price * $item->quantity;
                    $total += $item_total;
                }

                $delivery_cost = 0;
                $delivery_method = $form_data['delivery_method'] ?? '';
                switch ($delivery_method) {
                    case 'courier':
                        $delivery_cost = 300;
                        break;
                    case 'post':
                        $delivery_cost = 200;
                        break;
                    case 'pickup':
                        $delivery_cost = 0;
                        break;
                }
                $total_with_delivery = $total + $delivery_cost;
                $max_bonus_use = min($bonus_balance, $total_with_delivery * 0.5);
                ?>

                <div class="checkout-grid">
                    <div class="checkout-form">
                        <h3>Данные для доставки</h3>

                        <?php if ($is_bonus_program && $bonus_balance > 0): ?>
                            <div class="bonus-section">
                                <div class="bonus-info">
                                    <div>
                                        <strong>Ваш бонусный баланс:</strong>
                                        <span class="bonus-balance"><?php echo number_format($bonus_balance, 2); ?> бонусов</span>
                                    </div>
                                </div>
                                <div class="bonus-input">
                                    <label for="use_bonus">Использовать бонусы:</label>
                                    <input type="number" id="use_bonus" name="use_bonus"
                                           min="0" max="<?php echo $max_bonus_use; ?>"
                                           step="1" value="<?php echo $form_data['use_bonus'] ?? 0; ?>">
                                    <span>из max <?php echo number_format($max_bonus_use, 2); ?></span>
                                </div>
                                <div class="bonus-hint">
                                    Можно оплатить до 50% стоимости заказа бонусами
                                </div>
                            </div>
                        <?php elseif (!$is_bonus_program): ?>
                            <div class="bonus-section">
                                <div class="bonus-info">
                                    <div>
                                        <strong>Бонусная программа</strong>
                                        <p>Присоединяйтесь к бонусной программе и получайте 5% от суммы заказа бонусами!</p>
                                    </div>
                                    <a href="Userpage.php" class="join-bonus-btn">Присоединиться</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="lib/process-order.php" method="post" id="checkoutForm">
                            <!-- ДОБАВЛЕНО ПОЛЕ ФИО -->
                            <div class="form-group">
                                <label for="full_name">ФИО *</label>
                                <input type="text" id="full_name" name="full_name" required
                                       maxlength="100" placeholder="Введите ваше полное имя"
                                       value="<?php echo isset($form_data['full_name']) ? htmlspecialchars($form_data['full_name']) : ''; ?>">
                                <div class="char-counter" id="full_name_counter">0/100</div>
                            </div>

                            <div class="form-group">
                                <label for="phone">Телефон *</label>
                                <input type="tel" id="phone" name="phone" required
                                       maxlength="20" placeholder="Введите номер телефона"
                                       value="<?php echo isset($form_data['phone']) ? htmlspecialchars($form_data['phone']) : ''; ?>">
                                <div class="char-counter" id="phone_counter">0/20</div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required
                                       maxlength="255" placeholder="your@email.com"
                                       value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>">
                                <div class="char-counter" id="email_counter">0/255</div>
                            </div>

                            <div class="form-group">
                                <label for="address">Адрес доставки *</label>
                                <textarea id="address" name="address" rows="3" required
                                          maxlength="500" placeholder="Улица, дом, квартира"><?php echo isset($form_data['address']) ? htmlspecialchars($form_data['address']) : ''; ?></textarea>
                                <div class="char-counter" id="address_counter">0/500</div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">Город *</label>
                                    <input type="text" id="city" name="city" required
                                           maxlength="50" placeholder="Москва"
                                           value="<?php echo isset($form_data['city']) ? htmlspecialchars($form_data['city']) : ''; ?>">
                                    <div class="char-counter" id="city_counter">0/50</div>
                                </div>
                                <div class="form-group">
                                    <label for="postal_code">Почтовый индекс</label>
                                    <input type="text" id="postal_code" name="postal_code"
                                           maxlength="10" placeholder="123456"
                                           value="<?php echo isset($form_data['postal_code']) ? htmlspecialchars($form_data['postal_code']) : ''; ?>">
                                    <div class="char-counter" id="postal_code_counter">0/10</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Способ доставки *</label>
                                <select name="delivery_method" required id="delivery_method">
                                    <option value="">Выберите способ доставки</option>
                                    <option value="courier" <?php echo (isset($form_data['delivery_method']) && $form_data['delivery_method'] == 'courier') ? 'selected' : ''; ?>>Курьерская доставка (300 ₽)</option>
                                    <option value="pickup" <?php echo (isset($form_data['delivery_method']) && $form_data['delivery_method'] == 'pickup') ? 'selected' : ''; ?>>Самовывоз (бесплатно)</option>
                                    <option value="post" <?php echo (isset($form_data['delivery_method']) && $form_data['delivery_method'] == 'post') ? 'selected' : ''; ?>>Почта России (200 ₽)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Способ оплаты *</label>
                                <div class="payment-methods">
                                    <label class="payment-method <?php echo (isset($form_data['payment_method']) && $form_data['payment_method'] == 'card') ? 'selected' : ''; ?>">
                                        <input type="radio" name="payment_method" value="card" required <?php echo (isset($form_data['payment_method']) && $form_data['payment_method'] == 'card') ? 'checked' : ''; ?>>
                                        <div>💳 Картой онлайн</div>
                                        <small>Безопасная оплата через платежную систему</small>
                                    </label>
                                    <label class="payment-method <?php echo (isset($form_data['payment_method']) && $form_data['payment_method'] == 'cash') ? 'selected' : ''; ?>">
                                        <input type="radio" name="payment_method" value="cash" <?php echo (isset($form_data['payment_method']) && $form_data['payment_method'] == 'cash') ? 'checked' : ''; ?>>
                                        <div>💵 Наличными при получении</div>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="comments">Комментарий к заказу</label>
                                <textarea id="comments" name="comments" rows="3"
                                          maxlength="1000" placeholder="Дополнительные пожелания к заказу"><?php echo isset($form_data['comments']) ? htmlspecialchars($form_data['comments']) : ''; ?></textarea>
                                <div class="char-counter" id="comments_counter">0/1000</div>
                            </div>

                            <button type="submit" class="btn" style="width: 100%; padding: 16px; font-size: 18px;">
                                Подтвердить заказ
                            </button>
                        </form>
                    </div>

                    <div class="order-summary">
                        <h3>Ваш заказ</h3>
                        <div class="order-items">
                            <?php foreach ($cart_items as $item):
                                $item_total = $item->price * $item->quantity;
                                ?>
                                <div class="order-item">
                                    <div>
                                        <strong><?php echo $item->text; ?></strong>
                                        <div>Количество: <?php echo $item->quantity; ?> × ₽<?php echo $item->price; ?></div>
                                    </div>
                                    <div>₽<?php echo $item_total; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-summary-line">
                            <span>Товары:</span>
                            <span>₽<?php echo $total; ?></span>
                        </div>
                        <div class="order-summary-line" id="delivery_cost_line">
                            <span>Доставка:</span>
                            <span>₽<?php echo $delivery_cost; ?></span>
                        </div>
                        <?php if ($is_bonus_program && $bonus_balance > 0): ?>
                            <div class="order-summary-line" id="bonus_use_line">
                                <span>Оплата бонусами:</span>
                                <span>-₽<span id="bonus_use_value">0</span></span>
                            </div>
                        <?php endif; ?>
                        <div class="order-total" id="order_total">
                            Итого: ₽<?php echo $total_with_delivery; ?>
                        </div>
                        <?php if ($is_bonus_program): ?>
                            <div class="bonus-hint" style="text-align: center; margin-top: 10px;">
                                После оплаты вы получите: <span id="bonus_accrual"><?php echo number_format($total_with_delivery * 0.05, 2); ?></span> бонусов
                            </div>
                        <?php endif; ?>
                        <div style="text-align: center; margin-top: 16px;">
                            <a href="cart.php" class="btn ghost">Вернуться в корзину</a>
                        </div>
                    </div>
                </div>

                <?php
            }
        } catch (PDOException $e) {
            echo '<div class="error-message">
                    <h3>Ошибка загрузки данных</h3>
                    <p>Не удалось загрузить данные корзины. Пожалуйста, попробуйте позже.</p>
                  </div>';
            error_log("Checkout error: " . $e->getMessage());
        }
        ?>
    </div>
</main>
<?php require "block/footer.php" ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const totalProducts = <?php echo $total; ?>;
        let deliveryCost = <?php echo $delivery_cost; ?>;
        const maxBonusUse = <?php echo $max_bonus_use; ?>;
        const isBonusProgram = <?php echo $is_bonus_program ? 'true' : 'false'; ?>;

        const useBonusInput = document.getElementById('use_bonus');
        const deliveryMethodSelect = document.getElementById('delivery_method');
        const bonusUseValue = document.getElementById('bonus_use_value');
        const orderTotal = document.getElementById('order_total');
        const deliveryCostLine = document.getElementById('delivery_cost_line');
        const bonusAccrual = document.getElementById('bonus_accrual');

        function updateOrderSummary() {
            let totalAmount = totalProducts + deliveryCost;
            let usedBonus = 0;

            if (isBonusProgram && useBonusInput) {
                usedBonus = parseFloat(useBonusInput.value) || 0;
                if (usedBonus > maxBonusUse) {
                    usedBonus = maxBonusUse;
                    useBonusInput.value = maxBonusUse;
                }
                totalAmount -= usedBonus;
                if (totalAmount < 0) totalAmount = 0;

                if (bonusUseValue) {
                    bonusUseValue.textContent = usedBonus.toFixed(2);
                }
            }

            if (orderTotal) {
                orderTotal.textContent = 'Итого: ₽' + totalAmount.toFixed(2);
            }

            if (bonusAccrual) {
                const newBonusAccrual = totalAmount * 0.05;
                bonusAccrual.textContent = newBonusAccrual.toFixed(2);
            }
        }

        if (deliveryMethodSelect) {
            deliveryMethodSelect.addEventListener('change', function() {
                switch(this.value) {
                    case 'courier':
                        deliveryCost = 300;
                        break;
                    case 'post':
                        deliveryCost = 200;
                        break;
                    case 'pickup':
                        deliveryCost = 0;
                        break;
                    default:
                        deliveryCost = 0;
                }

                if (deliveryCostLine) {
                    deliveryCostLine.innerHTML = '<span>Доставка:</span><span>₽' + deliveryCost + '</span>';
                }

                updateOrderSummary();
            });
        }

        if (useBonusInput) {
            useBonusInput.addEventListener('input', updateOrderSummary);
            useBonusInput.addEventListener('change', updateOrderSummary);
        }

        const paymentMethods = document.querySelectorAll('.payment-method');
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                paymentMethods.forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });

        function setupCharCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);

            function updateCounter() {
                const length = input.value.length;
                counter.textContent = `${length}/${maxLength}`;

                if (length > maxLength * 0.9) {
                    counter.classList.add('warning');
                } else {
                    counter.classList.remove('warning');
                }

                if (length > maxLength) {
                    input.value = input.value.substring(0, maxLength);
                    updateCounter();
                }
            }

            input.addEventListener('input', updateCounter);
            input.addEventListener('change', updateCounter);
            updateCounter();
        }

        // Настройка счетчиков символов для всех полей
        setupCharCounter('full_name', 'full_name_counter', 100);
        setupCharCounter('phone', 'phone_counter', 20);
        setupCharCounter('email', 'email_counter', 255);
        setupCharCounter('address', 'address_counter', 500);
        setupCharCounter('city', 'city_counter', 50);
        setupCharCounter('postal_code', 'postal_code_counter', 10);
        setupCharCounter('comments', 'comments_counter', 1000);

        const form = document.getElementById('checkoutForm');
        form.addEventListener('submit', function(e) {
            let valid = true;

            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#e6e6e6';
                }
            });

            const textFields = form.querySelectorAll('input, textarea');
            textFields.forEach(field => {
                const maxLength = parseInt(field.getAttribute('maxlength'));
                if (maxLength && field.value.length > maxLength) {
                    valid = false;
                    field.style.borderColor = '#dc3545';
                    const counter = document.getElementById(field.id + '_counter');
                    if (counter) counter.classList.add('warning');
                }
            });

            if (useBonusInput) {
                const usedBonus = parseFloat(useBonusInput.value) || 0;
                if (usedBonus > maxBonusUse) {
                    valid = false;
                    useBonusInput.style.borderColor = '#dc3545';
                    alert('Нельзя использовать больше ' + maxBonusUse + ' бонусов');
                }
            }

            if (!valid) {
                e.preventDefault();
                alert('Пожалуйста, проверьте заполнение всех полей.');
            }
        });
    });
</script>
</body>
</html>