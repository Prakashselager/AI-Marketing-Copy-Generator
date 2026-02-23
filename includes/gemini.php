<?php
class GeminiClient {
    private $api_key;
    
    public function __construct($api_key = null) {
        // Replace with your actual API key from Google AI Studio
        $this->api_key = $api_key ?: 'AIzaSyDoLWjZi1C09h2hUPjz-1AabTtKPJoWQRQ'; // Paste your key here
    }
    
    public function generateMarketingCopy($params) {
        // Build a detailed prompt for better results
        $prompt = $this->buildPrompt($params);
        
        // Prepare the request data for Gemini API
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.9,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 500,
            ]
        ];
        
        // Gemini API endpoint (using the free model)
        $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $this->api_key;
        // Make the API call
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        // Check for cURL errors
        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'Connection error: ' . $curl_error
            ];
        }
        
        // Process the response
        if ($http_code == 200) {
            $result = json_decode($response, true);
            
            // Extract the generated text from Gemini response
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $generated_copy = $result['candidates'][0]['content']['parts'][0]['text'];
                
                // Format the copy nicely
                $formatted_copy = $this->formatCopy($generated_copy, $params['platform']);
                
                return [
                    'success' => true,
                    'copy' => $formatted_copy,
                    'raw_response' => $generated_copy
                ];
            } else {
                // Log the unexpected response for debugging
                error_log('Unexpected Gemini response: ' . json_encode($result));
                return [
                    'success' => false,
                    'error' => 'Unexpected API response format'
                ];
            }
        } else {
            // Parse error message from response
            $error_data = json_decode($response, true);
            $error_message = isset($error_data['error']['message']) 
                           ? $error_data['error']['message'] 
                           : 'Unknown error (HTTP ' . $http_code . ')';
            
            return [
                'success' => false,
                'error' => 'API error: ' . $error_message
            ];
        }
    }
    
    private function buildPrompt($params) {
        $platform = $params['platform'];
        $product = $params['product_name'];
        $audience = $params['target_audience'];
        $benefits = $params['key_benefits'];
        $tone = $params['tone_style'] ?? 'professional';
        
        $prompt = "You are an expert social media marketing copywriter. ";
        $prompt .= "Write a {$tone} marketing copy for {$platform} about '{$product}'. ";
        $prompt .= "Target audience: {$audience}. ";
        $prompt .= "Key benefits: {$benefits}. ";
        $prompt .= "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "1. Make it engaging and persuasive\n";
        $prompt .= "2. Include relevant emojis where appropriate\n";
        $prompt .= "3. Add relevant hashtags at the end\n";
        $prompt .= "4. Optimize for {$platform}'s best practices\n";
        $prompt .= "5. Keep it within {$platform}'s character limits\n";
        $prompt .= "\nMarketing Copy:\n";
        
        return $prompt;
    }
    
    private function formatCopy($copy, $platform) {
        // Clean up the copy
        $copy = trim($copy);
        
        // Remove any "Marketing Copy:" prefix if present
        $copy = preg_replace('/^Marketing Copy:\s*/i', '', $copy);
        
        // Add appropriate line breaks based on platform
        if ($platform == 'Instagram' || $platform == 'Facebook') {
            // Add line breaks for better readability
            $copy = wordwrap($copy, 60, "\n");
        }
        
        // Ensure it ends with proper punctuation
        if (!in_array(substr($copy, -1), ['.', '!', '?', '#'])) {
            $copy .= '.';
        }
        
        return $copy;
    }
    
    // Fallback method in case API fails
    public function generateFallbackCopy($params) {
        $platform = $params['platform'];
        $product = $params['product_name'];
        $audience = $params['target_audience'];
        $benefits = explode(',', $params['key_benefits']);
        $tone = $params['tone_style'] ?? 'professional';
        
        $emojis = ['🚀', '✨', '🔥', '💫', '⭐', '🎯', '💥', '⚡'];
        $emoji = $emojis[array_rand($emojis)];
        
        $copy = "{$emoji} Introducing {$product}! {$emoji}\n\n";
        $copy .= "Perfect for {$audience}!\n\n";
        $copy .= "✨ Why you'll love it:\n";
        
        foreach ($benefits as $benefit) {
            $copy .= "✓ " . trim($benefit) . "\n";
        }
        
        $copy .= "\n";
        
        switch ($platform) {
            case 'Instagram':
                $copy .= "👇 Link in bio to shop now!\n";
                $copy .= "#" . preg_replace('/[^a-zA-Z0-9]/', '', $product) . " #NewArrival";
                break;
            case 'Facebook':
                $copy .= "Click the link below to learn more! ⬇️\n";
                $copy .= "Share with someone who needs this!";
                break;
            case 'Twitter':
                $copy = substr($copy, 0, 240) . "... #" . preg_replace('/[^a-zA-Z0-9]/', '', $product);
                break;
            case 'LinkedIn':
                $copy = "Excited to announce {$product}! A game-changer for {$audience}.\n\n" . $copy;
                $copy .= "\n\n#Innovation #Marketing #NewProduct";
                break;
            case 'TikTok':
                $copy = "🚨 NEW DROP ALERT! 🚨\n\n{$copy}\n\n#fyp #new #trending";
                break;
        }
        
        return [
            'success' => true,
            'copy' => $copy,
            'fallback' => true
        ];
    }
}
?>