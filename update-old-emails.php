<?php

$emails_dir = __DIR__ . '/order_emails';
if (!file_exists($emails_dir)) {
    echo "Папка order_emails не существует";
    exit;
}

$files = glob($emails_dir . "/*.html");
$updated_count = 0;

foreach ($files as $file) {
    $filename = basename($file);

    // Извлекаем order_id из имени файла
    if (preg_match('/order_(\d+)_/', $filename, $matches)) {
        $order_id = $matches[1];

        // Добавляем информацию о заказе в файл
        $content = file_get_contents($file);

        // Ищем ссылку на заказ и обновляем ее
        $new_content = preg_replace(
            '/(<a href=")[^"]*?order-details\.php\?id=\d+("[^>]*>)/',
            '$1https://coffee-shop/order-details.php?id=' . $order_id . '$2',
            $content
        );

        if ($new_content !== $content) {
            file_put_contents($file, $new_content);
            $updated_count++;
            echo "Обновлен файл: $filename<br>";
        }
    }
}

echo "Готово! Обновлено файлов: $updated_count";
?>