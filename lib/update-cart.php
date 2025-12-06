<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: ../auth.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_id = $_POST['cart_id'];
    $quantity = intval($_POST['quantity']);

    // Валидация количества
    if ($quantity < 1 || $quantity > 99) {
        header('Location: ../cart.php?error=invalid_quantity');
        exit;
    }

    require_once 'db.php';

    try {
        // Проверяем, принадлежит ли элемент корзины текущему пользователю
        $sql = 'SELECT c.* FROM cart c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = ? AND u.name = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$cart_id, $_COOKIE['name']]);
        $item = $query->fetch(PDO::FETCH_OBJ);

        if ($item) {
            $sql = 'UPDATE cart SET quantity = ? WHERE id = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$quantity, $cart_id]);
            header('Location: ../cart.php?success=updated');
        } else {
            header('Location: ../cart.php?error=not_found');
        }
    } catch (PDOException $e) {
        error_log("Update cart error: " . $e->getMessage());
        header('Location: ../cart.php?error=server_error');
    }
    exit;
}
?>