<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: ../auth.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(htmlspecialchars($_POST['email']));
    $current_user = $_COOKIE['name'];

    require_once 'db.php';

    try {
        // Проверяем, не занято ли новое имя другим пользователем
        if ($name !== $current_user) {
            $sql = 'SELECT id FROM users WHERE name = ? AND name != ?';
            $query = $pdo->prepare($sql);
            $query->execute([$name, $current_user]);

            if ($query->rowCount() > 0) {
                $_SESSION['profile_error'] = 'Это имя пользователя уже занято';
                header('Location: ../Userpage.php');
                exit;
            }
        }

        // Проверяем, не занят ли email другим пользователем
        $sql = 'SELECT id FROM users WHERE email = ? AND name != ?';
        $query = $pdo->prepare($sql);
        $query->execute([$email, $current_user]);

        if ($query->rowCount() > 0) {
            $_SESSION['profile_error'] = 'Этот email уже используется другим пользователем';
            header('Location: ../Userpage.php');
            exit;
        }

        // Обновляем данные пользователя
        $sql = 'UPDATE users SET name = ?, email = ? WHERE name = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$name, $email, $current_user]);

        // Если имя изменилось, обновляем куки
        if ($name !== $current_user) {
            setcookie('name', $name, time() + 3600 * 24 * 7, '/');
        }

        $_SESSION['profile_success'] = 'Профиль успешно обновлен';
        header('Location: ../Userpage.php');
        exit;

    } catch (PDOException $e) {
        error_log("Update profile error: " . $e->getMessage());
        $_SESSION['profile_error'] = 'Ошибка при обновлении профиля';
        header('Location: ../Userpage.php');
        exit;
    }
}
?>