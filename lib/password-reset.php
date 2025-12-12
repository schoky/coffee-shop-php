<?php
class PasswordReset {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* Генерация токена для сброса пароля */
    public function generateResetToken($email, $type = 'user') {
        try {
            // Определяем таблицу в зависимости от типа пользователя
            $table = $type === 'admin' ? 'admin_users' : 'users';
            $emailField = $type === 'admin' ? 'email' : 'email';

            // Проверяем, существует ли пользователь с таким email
            $sql = "SELECT id FROM {$table} WHERE {$emailField} = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$email]);

            if ($query->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => $type === 'admin'
                        ? 'Администратор с таким email не найден'
                        : 'Пользователь с таким email не найден'
                ];
            }

            // Генерируем уникальный токен
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Сохраняем токен в БД
            $sql = "UPDATE {$table} SET reset_token = ?, reset_token_expires = ? WHERE {$emailField} = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$token, $expires, $email]);

            return [
                'success' => true,
                'token' => $token,
                'email' => $email
            ];

        } catch (PDOException $e) {
            error_log("Generate reset token error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при генерации токена'
            ];
        }
    }

    /* Валидация токена */
    public function validateToken($token, $type = 'user') {
        try {
            if (empty($token)) {
                return [
                    'success' => false,
                    'message' => 'Токен не предоставлен'
                ];
            }

            $table = $type === 'admin' ? 'admin_users' : 'users';

            // Проверяем токен и срок его действия
            $sql = "SELECT id, email, reset_token_expires FROM {$table} WHERE reset_token = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$token]);
            $user = $query->fetch(PDO::FETCH_OBJ);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Неверный токен'
                ];
            }

            // Проверяем срок действия токена
            if (strtotime($user->reset_token_expires) < time()) {
                return [
                    'success' => false,
                    'message' => 'Срок действия токена истек'
                ];
            }

            return [
                'success' => true,
                'user_id' => $user->id,
                'email' => $user->email
            ];

        } catch (PDOException $e) {
            error_log("Validate token error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при проверке токена'
            ];
        }
    }

    /* Сброс пароля по токену */
    public function resetPassword($token, $newPassword, $type = 'user') {
        try {
            // Сначала проверяем токен
            $validation = $this->validateToken($token, $type);
            if (!$validation['success']) {
                return $validation;
            }

            $table = $type === 'admin' ? 'admin_users' : 'users';
            $userId = $validation['user_id'];

            // Хэшируем новый пароль
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Обновляем пароль и очищаем токен
            $sql = "UPDATE {$table} SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$hashedPassword, $userId]);

            return [
                'success' => true,
                'message' => 'Пароль успешно изменен. Теперь вы можете войти с новым паролем.'
            ];

        } catch (PDOException $e) {
            error_log("Reset password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при смене пароля'
            ];
        }
    }

    /* Смена пароля с проверкой текущего пароля */
    public function changePassword($userId, $currentPassword, $newPassword, $type = 'user') {
        try {
            $table = $type === 'admin' ? 'admin_users' : 'users';

            // Получаем текущий пароль из базы
            $sql = "SELECT password FROM {$table} WHERE id = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$userId]);
            $user = $query->fetch(PDO::FETCH_OBJ);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ];
            }

            // Проверяем текущий пароль
            if (!password_verify($currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Текущий пароль неверен'
                ];
            }

            // Хэшируем новый пароль
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Обновляем пароль и очищаем токены сброса (если есть)
            $sql = "UPDATE {$table} SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$hashedPassword, $userId]);

            return [
                'success' => true,
                'message' => 'Пароль успешно изменен'
            ];

        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при смене пароля'
            ];
        }
    }

    /* Получить пользователя по имени */
    public function getUserByName($username, $type = 'user') {
        try {
            $table = $type === 'admin' ? 'admin_users' : 'users';
            $field = $type === 'admin' ? 'username' : 'name';

            $sql = "SELECT id, email FROM {$table} WHERE {$field} = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$username]);

            return $query->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            error_log("Get user by name error: " . $e->getMessage());
            return null;
        }
    }

    /* Получить пользователя по ID */
    public function getUserById($userId, $type = 'user') {
        try {
            $table = $type === 'admin' ? 'admin_users' : 'users';

            $sql = "SELECT * FROM {$table} WHERE id = ?";
            $query = $this->pdo->prepare($sql);
            $query->execute([$userId]);

            return $query->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }

    /* Очистка просроченных токенов */
    public function cleanupExpiredTokens($type = 'user') {
        try {
            $table = $type === 'admin' ? 'admin_users' : 'users';

            $sql = "UPDATE {$table} SET reset_token = NULL, reset_token_expires = NULL WHERE reset_token_expires < NOW()";
            $query = $this->pdo->prepare($sql);
            $query->execute();

            return $query->rowCount();

        } catch (PDOException $e) {
            error_log("Cleanup expired tokens error: " . $e->getMessage());
            return 0;
        }
    }

    /* Отправка email с ссылкой для сброса */
    public function sendResetEmail($email, $resetLink, $type = 'user') {
        $subject = $type === 'admin'
            ? 'Сброс пароля администратора - Реалист'
            : 'Сброс пароля - Реалист';

        $message = "Для сброса пароля перейдите по ссылке: {$resetLink}\n\n";
        $message .= "Ссылка действительна 1 час.\n\n";
        $message .= "Если вы не запрашивали сброс пароля, проигнорируйте это письмо.";

        // Логирование для отладки
        error_log("Reset email would be sent to: {$email}");
        error_log("Reset link: {$resetLink}");

        // Раскомментировать если буду выкладывать на хостинг и настроить отправку email:
        /*
        $headers = "From: no-reply@realist.ru\r\n";
        $headers .= "Reply-To: no-reply@realist.ru\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

        return mail($email, $subject, $message, $headers);
        */

        return true; // Заглушка
    }
}
?>