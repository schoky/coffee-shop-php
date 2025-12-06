<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: ../auth.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $user_name = $_COOKIE['name'];

    // Валидация
    if ($quantity < 1 || $quantity > 99) {
        header('Location: ../cart.php?error=invalid_quantity');
        exit;
    }

    require_once 'db.php';

    try {
        // Получаем user_id
        $sql = 'SELECT id FROM users WHERE name = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$user_name]);
        $user = $query->fetch(PDO::FETCH_OBJ);

        if (!$user) {
            header('Location: ../auth.php');
            exit;
        }

        // Проверяем существование товара в таблице photo
        $sql = 'SELECT id FROM photo WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$product_id]);
        $product = $query->fetch(PDO::FETCH_OBJ);

        if (!$product) {
            header('Location: ../cart.php?error=product_not_found');
            exit;
        }

        // Проверяем, есть ли уже товар в корзине
        $sql = 'SELECT * FROM cart WHERE user_id = ? AND product_id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$user->id, $product_id]);
        $existing_item = $query->fetch(PDO::FETCH_OBJ);

        if ($existing_item) {
            // Обновляем количество
            $new_quantity = $existing_item->quantity + $quantity;
            if ($new_quantity > 99) $new_quantity = 99;

            $sql = 'UPDATE cart SET quantity = ? WHERE id = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$new_quantity, $existing_item->id]);
        } else {
            // Добавляем новый товар
            $sql = 'INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)';
            $query = $pdo->prepare($sql);
            $query->execute([$user->id, $product_id, $quantity]);
        }

        header('Location: ../cart.php?success=added');
        exit;
    } catch (PDOException $e) {
        error_log("Add to cart error: " . $e->getMessage());
        header('Location: ../cart.php?error=server_error');
        exit;
    }
}
?>