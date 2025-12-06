<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: ../auth.php');
    exit;
}

if (isset($_GET['id'])) {
    $cart_id = intval($_GET['id']);

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
            $sql = 'DELETE FROM cart WHERE id = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$cart_id]);
            header('Location: ../cart.php?success=removed');
        } else {
            header('Location: ../cart.php?error=not_found');
        }
    } catch (PDOException $e) {
        error_log("Remove from cart error: " . $e->getMessage());
        header('Location: ../cart.php?error=server_error');
    }
    exit;
}
?>