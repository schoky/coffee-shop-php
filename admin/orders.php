<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../lib/db.php';

// Обновление статуса заказа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $status = trim(htmlspecialchars($_POST['status']));
    $payment_status = trim(htmlspecialchars($_POST['payment_status']));

    try {
        $sql = 'UPDATE orders SET status = ?, payment_status = ? WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$status, $payment_status, $order_id]);
        header('Location: orders.php?success=updated');
        exit;
    } catch (PDOException $e) {
        error_log("Order update error: " . $e->getMessage());
        header('Location: orders.php?error=update_failed');
        exit;
    }
}

// Получение списка заказов
try {
    $sql = 'SELECT o.*, u.name as user_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute();
    $orders = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Orders list error: " . $e->getMessage());
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами — Реалист</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="container admin-container">
    <div class="admin-header">
        <h1>Управление заказами</h1>
        <div>
            <a href="index.php" class="btn ghost">Назад в панель</a>
            <a href="../index.php" class="btn" style="margin-left: 15px;">На сайт</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            ✅ Статус заказа успешно обновлен
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            ❌ Ошибка при обновлении статуса
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p>Заказов пока нет.</p>
    <?php else: ?>
        <table class="orders-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Оплата</th>
                <th>Доставка</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order->id; ?></td>
                    <td><?php echo htmlspecialchars($order->user_name ?: 'Гость'); ?></td>
                    <td><?php echo htmlspecialchars($order->full_name); ?></td>
                    <td><?php echo htmlspecialchars($order->phone); ?></td>
                    <td>₽<?php echo number_format($order->total_amount, 2); ?></td>
                    <td>
                                <span class="status-<?php echo $order->status; ?>">
                                    <?php
                                    $status_names = [
                                            'pending' => 'Ожидание',
                                            'processing' => 'В обработке',
                                            'completed' => 'Завершен',
                                            'cancelled' => 'Отменен'
                                    ];
                                    echo $status_names[$order->status] ?? $order->status;
                                    ?>
                                </span>
                    </td>
                    <td>
                                <span class="payment-<?php echo $order->payment_status; ?>">
                                    <?php
                                    $payment_status_names = [
                                            'pending' => 'Ожидание',
                                            'pending_cash' => 'Ожидание наличных',
                                            'paid' => 'Оплачено',
                                            'failed' => 'Ошибка'
                                    ];
                                    echo $payment_status_names[$order->payment_status] ?? $order->payment_status;
                                    ?>
                                </span>
                    </td>
                    <td>
                        <?php
                        $delivery_names = [
                                'courier' => 'Курьер',
                                'pickup' => 'Самовывоз',
                                'post' => 'Почта'
                        ];
                        echo $delivery_names[$order->delivery_method] ?? $order->delivery_method;
                        ?>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?></td>
                    <td>
                        <form class="action-form" method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                            <select name="status">
                                <option value="pending" <?php echo $order->status == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                <option value="processing" <?php echo $order->status == 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                <option value="completed" <?php echo $order->status == 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                <option value="cancelled" <?php echo $order->status == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                            </select>
                            <select name="payment_status">
                                <option value="pending" <?php echo $order->payment_status == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                <option value="pending_cash" <?php echo $order->payment_status == 'pending_cash' ? 'selected' : ''; ?>>Ожидание наличных</option>
                                <option value="paid" <?php echo $order->payment_status == 'paid' ? 'selected' : ''; ?>>Оплачено</option>
                                <option value="failed" <?php echo $order->payment_status == 'failed' ? 'selected' : ''; ?>>Ошибка</option>
                            </select>
                            <button type="submit" class="btn btn-small">Обновить</button>
                        </form>
                        <a href="../order-details.php?id=<?php echo $order->id; ?>" class="order-details-btn" target="_blank">Подробнее</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>