<?php
header('Content-Type: application/json; charset=UTF-8');

// ==================== 🌍 РАЗРЕШЕНИЕ CORS 🌍 ====================
$allowed_domains = [
    "https://url1.com",
    "https://url2.com",
    "https://url3.com",
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_domains)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
}

// Если это preflight-запрос (OPTIONS), отправляем 200 OK и выходим
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Пути к файлам
$data_base_dir = "/your_dir/";
$log_file = $data_base_dir . "logs/debug.log";
$env_file = "/your_dir/.env";

// 🔹 Функция логирования
function log_message($message) {
    global $log_file;
    $log_entry = "[" . date("Y-m-d H:i:s") . "] " . $message . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// 🔹 Загружаем .env
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . "=" . trim($value));
    }
    log_message("✅ Файл .env загружен");
} else {
    log_message("❌ Ошибка: Файл .env отсутствует!");
    die(json_encode(["status" => "error", "message" => "Missing .env file"]));
}

// 🔹 Получаем переменные окружения
$telegram_token = getenv('TELEGRAM_TOKEN');
$chat_id = getenv('TELEGRAM_CHAT_ID');

if (!$telegram_token || !$chat_id) {
    log_message("❌ Ошибка: TELEGRAM_TOKEN или TELEGRAM_CHAT_ID отсутствуют!");
    die(json_encode(["status" => "error", "message" => "Telegram credentials missing"]));
}

log_message("🛠 TELEGRAM_TOKEN: " . substr($telegram_token, 0, 10) . "...");
log_message("🛠 TELEGRAM_CHAT_ID: " . $chat_id);

// 🔹 Получаем входные данные
$input = file_get_contents("php://input");
if (!$input) {
    log_message("❌ Ошибка: Пустой запрос");
    die(json_encode(["status" => "error", "message" => "Empty request"]));
}

log_message("📥 Входящие данные: " . $input);

// 🔹 Декодируем JSON
$data = json_decode($input, true);
if (!$data) {
    log_message("❌ Ошибка JSON-декодирования: " . json_last_error_msg());
    die(json_encode(["status" => "error", "message" => "Invalid JSON"]));
}

log_message("✅ JSON успешно декодирован");

// 🔹 Определяем, что пришло: отзыв или промокод
$promo_keys = ['serviceName', 'promoCode', 'discount', 'validUntil', 'terms'];
$review_keys = ['serviceName', 'rating', 'comment'];

$is_promo = !empty($data['promoCode']) || !empty($data['discount']);
$is_review = !empty($data['rating']) || !empty($data['comment']);

// Если нет валидных данных, отклоняем запрос
if (!$is_promo && !$is_review) {
    log_message("⚠ Нет валидных данных");
    die(json_encode(["status" => "error", "message" => "No valid data received"]));
}

// 🔹 Обрабатываем промокод
if ($is_promo) {
    $filtered_promo = array_intersect_key($data, array_flip($promo_keys));

    $message = "🔔 *Новый промокод!* 🔔\n\n";
    if (!empty($filtered_promo['serviceName'])) {
        $message .= "📺 *Сервис:* " . htmlspecialchars($filtered_promo['serviceName'], ENT_QUOTES, 'UTF-8') . "\n";
    }
    if (!empty($filtered_promo['promoCode'])) {
        $message .= "🎟 *Промокод:* " . htmlspecialchars($filtered_promo['promoCode'], ENT_QUOTES, 'UTF-8') . "\n";
    }
    if (!empty($filtered_promo['discount'])) {
        $message .= "💰 *Скидка:* " . htmlspecialchars($filtered_promo['discount'], ENT_QUOTES, 'UTF-8') . "\n";
    }
    if (!empty($filtered_promo['validUntil'])) {
        $message .= "📅 *Действителен до:* " . htmlspecialchars($filtered_promo['validUntil'], ENT_QUOTES, 'UTF-8') . "\n";
    }
    if (!empty($filtered_promo['terms'])) {
        $message .= "📜 *Условия:* " . htmlspecialchars($filtered_promo['terms'], ENT_QUOTES, 'UTF-8') . "\n";
    }

    log_message("📌 Обработан промокод: " . json_encode($filtered_promo, JSON_UNESCAPED_UNICODE));
}

// 🔹 Обрабатываем отзыв
if ($is_review) {
    $filtered_review = array_intersect_key($data, array_flip($review_keys));

    $message = "📝 *Новый отзыв о сервисе!* 📝\n\n";
    if (!empty($filtered_review['serviceName'])) {
        $message .= "📺 *Сервис:* " . htmlspecialchars($filtered_review['serviceName'], ENT_QUOTES, 'UTF-8') . "\n";
    }
    if (!empty($filtered_review['rating'])) {
        $message .= "⭐ *Оценка:* " . htmlspecialchars($filtered_review['rating'], ENT_QUOTES, 'UTF-8') . "/5\n";
    }
    if (!empty($filtered_review['comment'])) {
        $message .= "💬 *Комментарий:* " . htmlspecialchars($filtered_review['comment'], ENT_QUOTES, 'UTF-8') . "\n";
    }

    log_message("📌 Обработан отзыв: " . json_encode($filtered_review, JSON_UNESCAPED_UNICODE));
}

// 🔹 Отправляем сообщение в Telegram
$telegram_url = "https://api.telegram.org/bot$telegram_token/sendMessage";
$post_fields = [
    "chat_id" => $chat_id,
    "text" => $message,
    "parse_mode" => "Markdown"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegram_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 🔹 Логируем ответ от Telegram
log_message("📤 Ответ Telegram (HTTP $http_code): " . $response);

if ($http_code !== 200) {
    log_message("❌ Ошибка отправки в Telegram. Код ответа: $http_code");
    die(json_encode(["status" => "error", "message" => "Failed to send message to Telegram"]));
}

// 🔹 Финальный ответ
$response = ["status" => "ok", "message" => "Сообщение отправлено"];
echo json_encode($response, JSON_UNESCAPED_UNICODE);
log_message("📤 Финальный ответ клиенту: " . json_encode($response, JSON_UNESCAPED_UNICODE));

?>
