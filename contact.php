<?php
session_start();

$success_message = '';
$error_message = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(htmlspecialchars($_POST['email']));
    $phone = trim(htmlspecialchars($_POST['phone']));
    $message = trim(htmlspecialchars($_POST['message']));

    // Валидация
    $errors = [];

    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Имя должно содержать минимум 2 символа';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email адрес';
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Сообщение должно содержать минимум 10 символов';
    }

    if (empty($errors)) {
        require_once 'lib/db.php';

        try {
            $sql = 'INSERT INTO contact_requests (name, email, phone, message) VALUES (?, ?, ?, ?)';
            $query = $pdo->prepare($sql);
            $query->execute([$name, $email, $phone, $message]);

            $success_message = 'Ваше сообщение успешно отправлено! Мы свяжемся с вами в ближайшее время.';

            // Очищаем поля формы после успешной отправки
            $_POST = [];

        } catch (PDOException $e) {
            error_log("Contact form error: " . $e->getMessage());
            $error_message = 'Произошла ошибка при отправке сообщения. Пожалуйста, попробуйте позже.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Контакты</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .char-counter {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 4px;
        }

        .char-counter.warning {
            color: #dc3545;
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <h1>Связаться с нами</h1>
    <p>Есть вопрос по заказу или хочешь сделать оптовый заказ? Напиши нам, мы поможем!</p>

    <?php if ($success_message): ?>
        <div class="success-message">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="error-message">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form id="contactForm" class="form-card" method="post">
        <label>Имя *
            <input type="text" name="name" required minlength="2" maxlength="100"
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            <div class="char-counter" id="name_counter">0/100</div>
        </label>

        <label>Email *
            <input type="email" name="email" required maxlength="255"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <div class="char-counter" id="email_counter">0/255</div>
        </label>

        <label>Телефон
            <input type="tel" name="phone" maxlength="20"
                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            <div class="char-counter" id="phone_counter">0/20</div>
        </label>

        <label>Сообщение *
            <textarea name="message" rows="6" required minlength="10" maxlength="1000"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            <div class="char-counter" id="message_counter">0/1000</div>
        </label>

        <div class="form-actions">
            <button class="btn" type="submit">Отправить</button>
            <button class="btn ghost" type="reset">Очистить</button>
        </div>
    </form>

    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <h3>Контактная информация</h3>
        <p><strong>Телефон:</strong> +7 (812) 123-45-67</p>
        <p><strong>Email:</strong> spb@realist-coffee.ru</p>
        <p><strong>Адрес:</strong> Санкт-Петербург, Исаакиевская площадь, 4</p>
        <p><strong>Часы работы:</strong> Пн-Пт: 8:00-22:00, Сб-Вс: 9:00-23:00</p>
    </div>
</main>

<?php require "block/footer.php" ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Функция для счетчика символов
        function setupCharCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);

            if (!input || !counter) return;

            function updateCounter() {
                const length = input.value.length;
                counter.textContent = `${length}/${maxLength}`;

                if (length > maxLength * 0.9) {
                    counter.classList.add('warning');
                } else {
                    counter.classList.remove('warning');
                }

                // Автоматическое обрезание при превышении лимита
                if (length > maxLength) {
                    input.value = input.value.substring(0, maxLength);
                    updateCounter();
                }
            }

            input.addEventListener('input', updateCounter);
            input.addEventListener('change', updateCounter);
            updateCounter(); // Инициализация при загрузке
        }

        // Настройка счетчиков для всех полей
        setupCharCounter('name', 'name_counter', 100);
        setupCharCounter('email', 'email_counter', 255);
        setupCharCounter('phone', 'phone_counter', 20);
        setupCharCounter('message', 'message_counter', 1000);

        // Валидация формы перед отправкой
        const form = document.getElementById('contactForm');
        form.addEventListener('submit', function(e) {
            let valid = true;
            const fields = form.querySelectorAll('input, textarea');

            fields.forEach(field => {
                const maxLength = parseInt(field.getAttribute('maxlength'));
                if (maxLength && field.value.length > maxLength) {
                    valid = false;
                    field.style.borderColor = '#dc3545';
                    const counter = document.getElementById(field.name + '_counter');
                    if (counter) counter.classList.add('warning');
                } else {
                    field.style.borderColor = '';
                }

                if (field.hasAttribute('required') && !field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#dc3545';
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Пожалуйста, проверьте заполнение всех полей.');
            }
        });

        // Сброс стилей при очистке формы
        const resetBtn = form.querySelector('button[type="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                const fields = form.querySelectorAll('input, textarea');
                fields.forEach(field => {
                    field.style.borderColor = '';
                });

                const counters = form.querySelectorAll('.char-counter');
                counters.forEach(counter => {
                    counter.classList.remove('warning');
                });
            });
        }
    });
</script>
</body>
</html>