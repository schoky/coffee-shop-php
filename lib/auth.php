<?php
session_start();

$name = trim(htmlspecialchars($_POST["name"]));
$password = trim(htmlspecialchars($_POST["password"]));

include "db.php";

try {
    $sql = 'SELECT * FROM users WHERE name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$name]);
    $user = $query->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        $_SESSION['auth_error'] = 'Неверное имя пользователя или пароль';
        header("Location: ../auth.php");
        exit;
    }

    // Проверка пароля с помощью password_verify
    if (password_verify($password, $user->password)) {
        setcookie('name', $name, time() + 3600 * 24 * 7, '/');
        $_SESSION['user_name'] = $name;
        header("Location: ../index.php");
        exit;
    } else {
        // Для обратной совместимости со старыми MD5 паролями (опционально)
        // Можно удалить этот блок после полного перехода
        $score = 'epiulskejmcofiuhvr';
        $old_hashed_password = md5($score . $password);

        if ($old_hashed_password === $user->password) {
            // Обновляем пароль на новый формат
            $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_update = 'UPDATE users SET password = ? WHERE id = ?';
            $query_update = $pdo->prepare($sql_update);
            $query_update->execute([$new_hashed_password, $user->id]);

            setcookie('name', $name, time() + 3600 * 24 * 7, '/');
            $_SESSION['user_name'] = $name;
            header("Location: ../index.php");
            exit;
        }

        $_SESSION['auth_error'] = 'Неверное имя пользователя или пароль';
        header("Location: ../auth.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Authentication error: " . $e->getMessage());
    $_SESSION['auth_error'] = 'Ошибка сервера при авторизации';
    header("Location: ../auth.php");
    exit;
}
?>