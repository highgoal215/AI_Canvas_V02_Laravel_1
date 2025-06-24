<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Current OpenAI API Key...\n";
echo "================================\n\n";

// Get the current API key from config
$apiKey = config('openai.api_key');

if (!$apiKey) {
    echo "❌ No API key found in configuration\n";
    exit(1);
}

echo "Current API Key: " . substr($apiKey, 0, 20) . "...\n";

// Check if it's a project-specific key
if (strpos($apiKey, 'sk-proj-') === 0) {
    echo "❌ ISSUE DETECTED: You're using a PROJECT-SPECIFIC key (sk-proj-)\n";
    echo "   This key has limited permissions and won't work for all OpenAI services.\n\n";
    
    echo "🔧 SOLUTION:\n";
    echo "1. Go to https://platform.openai.com/account/api-keys\n";
    echo "2. Click 'Create new secret key'\n";
    echo "3. Give it a name (e.g., 'AI Canvas App')\n";
    echo "4. Copy the new key (it should start with 'sk-' not 'sk-proj-')\n";
    echo "5. Update your .env file with the new key\n\n";
    
    echo "Current key format: " . substr($apiKey, 0, 10) . "...\n";
    echo "Required format: sk-...\n\n";
    
    echo "Testing current key with OpenAI API...\n";
    testOpenAIKey($apiKey);
    
} else {
    echo "✅ You're using a standard API key (sk-)\n";
    echo "Testing the key...\n";
    testOpenAIKey($apiKey);
}

function testOpenAIKey($apiKey) {
    echo "Making test request to OpenAI API...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/models');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: $error\n";
        return;
    }
    
    echo "HTTP Status Code: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "✅ API key is valid and working!\n";
        $data = json_decode($response, true);
        if (isset($data['data'])) {
            echo "Available models: " . count($data['data']) . "\n";
        }
    } elseif ($httpCode === 401) {
        echo "❌ API key is invalid or expired\n";
        echo "Error: " . $response . "\n";
    } elseif ($httpCode === 403) {
        echo "❌ API key doesn't have required permissions\n";
        echo "This confirms the project-specific key limitation.\n";
        echo "Error: " . $response . "\n";
    } else {
        echo "⚠️  Unexpected response (HTTP $httpCode)\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
}

echo "\nTest completed.\n";
echo "\nTo fix the issue, run: php update_openai_key.php\n"; 