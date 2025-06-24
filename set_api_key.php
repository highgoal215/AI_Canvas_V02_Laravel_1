<?php

echo "OpenAI API Key Setup\n";
echo "===================\n\n";

echo "Current API Key in .env file:\n";
$envContent = file_get_contents('.env');
if (preg_match('/OPENAI_API_KEY=(.*)/', $envContent, $matches)) {
    $currentKey = trim($matches[1]);
    echo "Current Key: " . substr($currentKey, 0, 20) . "...\n";
    
    if (strpos($currentKey, 'sk-proj-') === 0) {
        echo "❌ Currently using PROJECT-SPECIFIC key (sk-proj-)\n";
    } else {
        echo "✅ Currently using STANDARD key (sk-)\n";
    }
}

echo "\nPlease enter your OpenAI API key (should start with 'sk-'): ";
$handle = fopen("php://stdin", "r");
$newKey = trim(fgets($handle));
fclose($handle);

if (empty($newKey)) {
    echo "❌ No key provided. Exiting.\n";
    exit(1);
}

// Validate key format
if (strpos($newKey, 'sk-') !== 0) {
    echo "❌ Invalid API key format. It should start with 'sk-'\n";
    exit(1);
}

// Update the .env file
$newEnvContent = preg_replace(
    '/OPENAI_API_KEY=.*/',
    'OPENAI_API_KEY=' . $newKey,
    $envContent
);

if (file_put_contents('.env', $newEnvContent)) {
    echo "\n✅ API key updated successfully!\n";
    echo "New Key: " . substr($newKey, 0, 20) . "...\n\n";
    
    echo "Testing the new key...\n";
    testOpenAIKey($newKey);
} else {
    echo "\n❌ Failed to update .env file\n";
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
    curl_close($ch);
    
    echo "HTTP Status Code: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "✅ API key is valid and working!\n";
        echo "You can now use TextToImage, TextToSpeech, and TextToVideo services.\n";
        
        $data = json_decode($response, true);
        if (isset($data['data'])) {
            $models = array_column($data['data'], 'id');
            $imageModels = array_filter($models, function($model) {
                return strpos($model, 'dall-e') !== false;
            });
            $audioModels = array_filter($models, function($model) {
                return strpos($model, 'tts') !== false || strpos($model, 'whisper') !== false;
            });
            
            echo "\nAvailable services:\n";
            echo "- Image generation: " . (count($imageModels) > 0 ? "✅ Available" : "❌ Not available") . "\n";
            echo "- Audio services: " . (count($audioModels) > 0 ? "✅ Available" : "❌ Not available") . "\n";
        }
    } elseif ($httpCode === 401) {
        echo "❌ API key is invalid or expired\n";
        echo "Please check your API key at https://platform.openai.com/account/api-keys\n";
    } else {
        echo "⚠️  API test failed with HTTP code: $httpCode\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
}

echo "\nSetup completed!\n"; 