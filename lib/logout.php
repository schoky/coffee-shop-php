<?php
session_start();
// Удаляем куки
setcookie('name', '', time() - 3600, '/');
// Уничтожаем сессию
session_destroy();
// Перенаправляем на главную
header('Location: /index.php');
exit;
?>