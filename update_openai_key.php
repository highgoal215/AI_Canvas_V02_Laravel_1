<?php

echo "OpenAI API Key Update Script\n";
echo "============================\n\n";

echo "Current API Key in .env file:\n";
$envContent = file_get_contents('.env');
if (preg_match('/OPENAI_API_KEY=(.*)/', $envContent, $matches)) {
    $currentKey = trim($matches[1]);
    echo "Current Key: " . substr($currentKey, 0, 20) . "...\n";
    
    if (strpos($currentKey, 'sk-proj-') === 0) {
        echo "❌ You're using a PROJECT-SPECIFIC key (sk-proj-)\n";
        echo "   This key has limited permissions and won't work for all OpenAI services.\n\n";
        
        echo "To fix this:\n";
        echo "1. Go to https://platform.openai.com/account/api-keys\n";
        echo "2. Create a new secret key (it should start with 'sk-')\n";
        echo "3. Copy the new key\n";
        echo "4. Run this script again with your new key\n\n";
        
        echo "Enter your new standard OpenAI API key (starts with 'sk-'): ";
        $handle = fopen("php://stdin", "r");
        $newKey = trim(fgets($handle));
        fclose($handle);
        
        if (strpos($newKey, 'sk-') === 0 && strpos($newKey, 'sk-proj-') !== 0) {
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
        } else {
            echo "\n❌ Invalid API key format. It should start with 'sk-' (not 'sk-proj-')\n";
        }
    } else {
        echo "✅ You're using a standard API key (sk-)\n";
        echo "The key should work properly.\n\n";
        
        echo "Testing the current key...\n";
        testOpenAIKey($currentKey);
    }
} else {
    echo "❌ OPENAI_API_KEY not found in .env file\n";
}

function testOpenAIKey($apiKey) {
    echo "Testing API key with OpenAI...\n";
    
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
    
    if ($httpCode === 200) {
        echo "✅ API key is valid and working!\n";
        echo "You can now use the TextToImage and other AI services.\n";
    } elseif ($httpCode === 401) {
        echo "❌ API key is invalid or expired\n";
        echo "Please check your API key at https://platform.openai.com/account/api-keys\n";
    } else {
        echo "⚠️  API test failed with HTTP code: $httpCode\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
}

echo "\nScript completed.\n"; 