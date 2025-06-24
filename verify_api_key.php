<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Verifying OpenAI API Key...\n";

try {
    $apiKey = config('openai.api_key');
    
    if (empty($apiKey)) {
        echo "❌ API key not configured\n";
        exit(1);
    }
    
    echo "API Key Type: " . (str_starts_with($apiKey, 'sk-proj-') ? 'Project Key' : 'Standard Key') . "\n";
    
    // Test API connection
    $response = OpenAI::models()->list();
    echo "✅ API connection successful!\n";
    echo "Available models: " . count($response->data) . "\n";
    
    echo "\n🎉 Your API key is working correctly!\n";
    echo "TextToImage and TextToSpeech services should now work.\n";
    
} catch (Exception $e) {
    echo "❌ API Error: " . $e->getMessage() . "\n";
    exit(1);
} 