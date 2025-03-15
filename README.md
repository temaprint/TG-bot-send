# **Review & Promo Code Submission System**

This system allows users to submit **promo codes** and **reviews for streaming services**. The form sends data to a PHP backend, which processes the request, logs it, and sends a notification to a **Telegram bot**.

## 📌 **Table of Contents**
- Features
- Frontend (Form Submission)
- Backend (PHP API)
- How the System Works
- Installation & Setup

---

## **🚀 Features**
✅ Supports **two types of submissions**:
1. **Promo Code Submission** – Users can share discount codes.
2. **Service Reviews** – Users can rate and comment on streaming services.

✅ Uses **CORS protection** to accept requests only from allowed domains.  
✅ Logs every request to a file for debugging.  
✅ Sends structured messages to **Telegram bot**.  
✅ Saves all received data in JSON format.

---

## **🖥️ Frontend (Form Submission)**

The **HTML form** is designed to collect either **promo codes** or **user reviews**. The form is submitted using **JavaScript `fetch()`** as a `POST` request with `JSON` data.

### **📌 Code Example (Frontend)**
```html
<h2>Submit a Review</h2>
<form id="reviewForm" class="space-y-6">
    <label for="service">Service</label>
    <select id="service" name="service">
        <option value="">Select a service</option>
        <option value="kinopoisk">Kinopoisk</option>
        <option value="netflix">Netflix</option>
        <option value="disney+">Disney+</option>
    </select>

    <label>Rating</label>
    <div>
        <input type="radio" name="rating" value="1"> ★
        <input type="radio" name="rating" value="2"> ★★
        <input type="radio" name="rating" value="3"> ★★★
        <input type="radio" name="rating" value="4"> ★★★★
        <input type="radio" name="rating" value="5"> ★★★★★
    </div>

    <label for="comment">Comment</label>
    <textarea id="comment" name="comment" rows="4"></textarea>

    <button type="submit">Submit</button>
</form>

<script>
document.getElementById('reviewForm').addEventListener('submit', async function(event) {
    event.preventDefault();

    const service = document.getElementById('service').value;
    const rating = document.querySelector('input[name="rating"]:checked')?.value;
    const comment = document.getElementById('comment').value;

    if (!service || !rating || !comment) {
        alert('Please fill out all fields.');
        return;
    }

    const requestData = {
        serviceName: service,
        rating: rating,
        comment: comment
    };

    try {
        const response = await fetch('https://your_domen/telegram_bot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();
        alert(result.message);
    } catch (error) {
        alert('Network error. Please try again later.');
    }
});
</script>
```
### 📌 How it Works (Frontend)

1. The user selects a streaming service, rates it, and adds a comment.
2. When the form is submitted, fetch() sends the data as JSON to the PHP backend.
3. The backend processes and sends the review to Telegram.

## 🖥️ Backend (PHP API)

The PHP backend is responsible for processing form submissions and sending them to a Telegram bot.

### 📌 Code Structure

- CORS Setup – Allows requests only from trusted domains.
- Reads JSON Input – Parses form data from frontend.
- Detects Data Type – Determines if the request is a promo code or review.
- Formats Message – Creates a Telegram message based on request type.
- Sends to Telegram – Uses cURL to send data to a Telegram bot.
- Logs All Requests – Saves every request to a log file.

### 📌 How the PHP Backend Works

1. Receives JSON Data

Reads the request body (php://input).
Decodes JSON and validates input.
2. Identifies Request Type

If promoCode, discount, etc., exist → Promo Code Submission.
If rating, comment, etc., exist → Service Review Submission.
3. Formats the Telegram Message

Adds relevant emojis for clarity.
Uses Markdown for better formatting.
4. Sends Data to Telegram

Uses cURL to call Telegram API.
5. Logs the Data

Stores the request in a log file.
Saves the JSON data in a database directory.

```
$input = file_get_contents("php://input");
$data = json_decode($input, true);

$is_promo = isset($data['promoCode']) || isset($data['discount']);
$is_review = isset($data['rating']) || isset($data['comment']);

if ($is_promo) {
    $message = "🔔 *New Promo Code!* 🔔\n\n";
    if (!empty($data['serviceName'])) $message .= "📺 *Service:* " . htmlspecialchars($data['serviceName']) . "\n";
    if (!empty($data['promoCode'])) $message .= "🎟 *Promo Code:* " . htmlspecialchars($data['promoCode']) . "\n";
    if (!empty($data['discount'])) $message .= "💰 *Discount:* " . htmlspecialchars($data['discount']) . "\n";
} elseif ($is_review) {
    $message = "📝 *New Service Review!* 📝\n\n";
    if (!empty($data['serviceName'])) $message .= "📺 *Service:* " . htmlspecialchars($data['serviceName']) . "\n";
    if (!empty($data['rating'])) $message .= "⭐ *Rating:* " . htmlspecialchars($data['rating']) . "/5\n";
    if (!empty($data['comment'])) $message .= "💬 *Comment:* " . htmlspecialchars($data['comment']) . "\n";
} else {
    die(json_encode(["status" => "error", "message" => "Invalid data"]));
}

// Send to Telegram
$telegram_url = "https://api.telegram.org/bot$telegram_token/sendMessage";
$post_fields = ["chat_id" => $chat_id, "text" => $message, "parse_mode" => "Markdown"];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegram_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

```

## ⚙️ Installation & Setup

1. Set up PHP server (Apache or Nginx).
2. Create .env file with your Telegram bot credentials:
   TELEGRAM_TOKEN=your_bot_token
   TELEGRAM_CHAT_ID=your_chat_id
3. Ensure correct file permissions for logging:
  chmod -R 750 /
4. Deploy frontend and test the form submission.