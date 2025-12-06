<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Реалист — Регистрация</title>
</head>
<body>
<?php
session_start();
require "block/header.php";
?>

<main class="container">
    <div class="form-card">
        <h2>Регистрация</h2>

        <?php if (isset($_SESSION['reg_error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php
                echo $_SESSION['reg_error'];
                unset($_SESSION['reg_error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="post" action="lib/reg.php">
            <label>Имя
                <input type="text" name="name" required minlength="2">
            </label>
            <label>Email
                <input type="email" name="email" required>
            </label>
            <label>Пароль
                <input type="password" name="password" required minlength="6">
            </label>
            <div class="modal-actions">
                <button type="submit" class="btn">Зарегистрироваться</button>
            </div>
        </form>
    </div>
</main>
<?php require "block/footer.php" ?>
<script src="js/script.js"></script>
</body>
</html>