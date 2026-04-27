<?php
/**
 * AI Gift Stylist Backend Handler
 * Communicates with OpenAI to provide product recommendations.
 */
header('Content-Type: application/json');

require_once 'db.php';
require_once 'config_ai.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

try {
    // 1. Fetch products to give AI context
    $stmt = $pdo->query("SELECT name, description, price, category FROM products WHERE stock > 0");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productContext = "Available Products at YardHandicraft:\n";
    foreach ($products as $p) {
        $productContext .= "- {$p['name']}: {$p['description']} (Price: ₱" . number_format($p['price'], 0) . ", Category: {$p['category']})\n";
    }

    // 2. Prepare OpenAI API request
    $apiUrl = 'https://api.openai.com/v1/chat/completions';
    
    $systemPrompt = "You are the 'YardHandicraft AI Gift Stylist', a helpful and friendly virtual assistant for an e-commerce shop based in Davao Region, Philippines that sells handcrafted satin flowers and pots. 

Your goal is to help users find the perfect gift based on their needs, occasions, or preferences.

Rules:
1. Use the following product list to make recommendations:
$productContext

2. If a user asks for something we don't have, politely explain we specialize in handcrafted flowers and suggest the closest alternative.
3. Be professional, warm, and creative. Mention that products are handmade with care in Davao.
4. Keep responses concise (2-4 sentences).
5. If the user mentions an occasion (graduation, wedding, birthday), explain why your recommendation fits that occasion.";

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ],
        'temperature' => 0.7
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("OpenAI API returned error code $httpCode: $response");
    }

    $result = json_decode($response, true);
    $aiResponse = $result['choices'][0]['message']['content'] ?? "I'm sorry, I'm having trouble thinking right now. Please try again later!";

    echo json_encode(['reply' => $aiResponse]);

} catch (Exception $e) {
    error_log("AI Assistant Error: " . $e->getMessage());
    echo json_encode(['error' => 'Internal Server Error', 'message' => $e->getMessage()]);
}
?>
