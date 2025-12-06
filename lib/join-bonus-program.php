<?php
session_start();
if (!isset($_COOKIE['name'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

require_once 'db.php';

try {
    $user_name = $_COOKIE['name'];

    $sql = 'SELECT id, is_bonus_program FROM users WHERE name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$user_name]);
    $user = $query->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit;
    }

    if ($user->is_bonus_program) {
        echo json_encode(['success' => false, 'message' => 'Вы уже участвуете в бонусной программе']);
        exit;
    }

    $sql = 'UPDATE users SET is_bonus_program = 1 WHERE id = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$user->id]);

    echo json_encode(['success' => true, 'message' => 'Бонусная программа активирована']);

} catch (PDOException $e) {
    error_log("Join bonus program error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
}
?>