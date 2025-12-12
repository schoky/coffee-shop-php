<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../lib/db.php';

$success_message = '';
$error_message = '';

// Обработка добавления/редактирования товара
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $text = trim(htmlspecialchars($_POST['text']));
    $price = floatval($_POST['price']);
    $description = trim(htmlspecialchars($_POST['description']));
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Обработка загрузки изображения
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];

        // Проверка типа файла
        if (!in_array($file_type, $allowed_types)) {
            $error_message = 'Неверный формат изображения. Разрешены: JPG, PNG, GIF, WebP';
        }
        // Проверка размера файла (макс. 5MB)
        elseif ($file_size > 5 * 1024 * 1024) {
            $error_message = 'Размер изображения не должен превышать 5MB';
        }
        else {
            // Генерация уникального имени файла
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid() . '_' . time() . '.' . $file_extension;
            $upload_path = '../image/' . $image_name;

            // Перемещение файла
            if (!move_uploaded_file($file_tmp, $upload_path)) {
                $error_message = 'Ошибка при загрузке изображения';
            }
        }
    }

    // Если нет ошибок с изображением, сохраняем товар
    if (empty($error_message)) {
        try {
            if ($id > 0) {
                // Редактирование существующего товара
                if (!empty($image_name)) {
                    // Если загружено новое изображение
                    $sql = 'UPDATE photo SET image = ?, text = ?, price = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?';
                    $query = $pdo->prepare($sql);
                    $query->execute([$image_name, $text, $price, $description, $is_active, $id]);
                } else {
                    // Если изображение не менялось
                    $sql = 'UPDATE photo SET text = ?, price = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?';
                    $query = $pdo->prepare($sql);
                    $query->execute([$text, $price, $description, $is_active, $id]);
                }
                $success_message = 'Товар успешно обновлен';
            } else {
                // Добавление нового товара
                if (empty($image_name)) {
                    $image_name = 'default-coffee.jpg'; // Заглушка, если изображение не загружено
                }

                $sql = 'INSERT INTO photo (image, text, price, description, is_active) VALUES (?, ?, ?, ?, ?)';
                $query = $pdo->prepare($sql);
                $query->execute([$image_name, $text, $price, $description, $is_active]);
                $success_message = 'Товар успешно добавлен';
            }
        } catch (PDOException $e) {
            error_log("Product save error: " . $e->getMessage());
            $error_message = 'Ошибка при сохранении товара';

            // Удаляем загруженное изображение в случае ошибки
            if (!empty($image_name) && file_exists('../image/' . $image_name)) {
                unlink('../image/' . $image_name);
            }
        }
    }
}

// Обработка удаления товара
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        // Получаем информацию о товаре для удаления изображения
        $sql = 'SELECT image FROM photo WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$delete_id]);
        $product = $query->fetch(PDO::FETCH_OBJ);

        if ($product && $product->image !== 'default-coffee.jpg') {
            // Удаляем файл изображения, если это не дефолтное изображение
            $image_path = '../image/' . $product->image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        $sql = 'DELETE FROM photo WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$delete_id]);
        $success_message = 'Товар успешно удален';
    } catch (PDOException $e) {
        error_log("Product delete error: " . $e->getMessage());
        $error_message = 'Ошибка при удалении товара';
    }
}

// Получение товара для редактирования
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    try {
        $sql = 'SELECT * FROM photo WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$edit_id]);
        $edit_product = $query->fetch(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        error_log("Product edit error: " . $e->getMessage());
    }
}

// Получение списка товаров
try {
    $sql = 'SELECT * FROM photo ORDER BY created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute();
    $products = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Products list error: " . $e->getMessage());
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами — Реалист</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="container admin-container">
    <div class="admin-header">
        <h1>Управление товарами</h1>
        <div>
            <a href="index.php" class="btn ghost">Назад в панель</a>
            <a href="../index.php" class="btn" style="margin-left: 15px;">На сайт</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="success-message">✅ <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="error-message">❌ <?php echo $error_message; ?></div>
    <?php endif; ?>

    <section class="form-section">
        <h2><?php echo $edit_product ? 'Редактирование товара' : 'Добавление нового товара'; ?></h2>
        <form method="post" enctype="multipart/form-data">
            <?php if ($edit_product): ?>
                <input type="hidden" name="id" value="<?php echo $edit_product->id; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="text">Название товара *</label>
                    <input type="text" id="text" name="text" required
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product->text) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="price">Цена (руб) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required
                           value="<?php echo $edit_product ? $edit_product->price : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Описание товара</label>
                <textarea id="description" name="description"><?php echo $edit_product ? htmlspecialchars($edit_product->description) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="image">Изображение товара</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">

                <?php if ($edit_product && $edit_product->image): ?>
                    <div class="current-image">
                        <p><strong>Текущее изображение:</strong></p>
                        <img src="../image/<?php echo $edit_product->image; ?>" alt="<?php echo htmlspecialchars($edit_product->text); ?>">
                        <p><small>Файл: <?php echo $edit_product->image; ?></small></p>
                    </div>
                <?php endif; ?>

                <div class="image-preview" id="imagePreview" style="display: none;">
                    <p><strong>Предпросмотр нового изображения:</strong></p>
                    <img id="previewImage" src="#" alt="Предпросмотр">
                </div>
            </div>

            <div class="form-actions">
                <div class="active-checkbox">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" value="1"
                                <?php echo ($edit_product && $edit_product->is_active) || !$edit_product ? 'checked' : ''; ?>>
                        <span>Товар активен (отображается в каталоге)</span>
                    </label>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn">
                        <?php echo $edit_product ? 'Обновить товар' : 'Добавить товар'; ?>
                    </button>
                    <?php if ($edit_product): ?>
                        <a href="products.php" class="btn ghost">Отмена</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="list-section">
        <h2>Список товаров</h2>
        <?php if (empty($products)): ?>
            <p>Товаров пока нет.</p>
        <?php else: ?>
            <table class="products-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Статус</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product->id; ?></td>
                        <td>
                            <img src="../image/<?php echo $product->image; ?>" alt="<?php echo htmlspecialchars($product->text); ?>" class="product-image">
                        </td>
                        <td><?php echo htmlspecialchars($product->text); ?></td>
                        <td>₽<?php echo number_format($product->price, 2); ?></td>
                        <td>
                                    <span class="status-<?php echo $product->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $product->is_active ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($product->created_at)); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="?edit=<?php echo $product->id; ?>" class="btn btn-small">Редактировать</a>
                                <a href="?delete=<?php echo $product->id; ?>" class="btn btn-small"
                                   onclick="return confirm('Вы уверены, что хотите удалить этот товар?')"
                                   style="background: #dc3545;">Удалить</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<script>
    // Предпросмотр изображения перед загрузкой //
    document.getElementById('image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');

        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                previewImage.src = e.target.result;
                preview.style.display = 'block';
            }

            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
</script>
</body>
</html>