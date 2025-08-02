<?php
/**
 * Plugin Name: OpenAI Streaming Chat
 * Description: Ejemplo de streaming desde OpenAI usando EventSource.
 * Version: 1.0.0
 * Author: OpenAI Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_stream_chat', 'openai_stream_chat');
add_action('wp_ajax_nopriv_stream_chat', 'openai_stream_chat');

function openai_stream_chat() {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => wp_json_encode([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente.'],
                ['role' => 'user', 'content' => 'Hola']
            ],
            'stream' => true
        ]),
        CURLOPT_WRITEFUNCTION => function ($curl, $data) {
            echo "data: $data\n\n";
            @ob_flush();
            flush();
            return strlen($data);
        },
        CURLOPT_RETURNTRANSFER => false,
    ]);

    curl_exec($ch);
    curl_close($ch);
    exit;
}

add_action('wp_enqueue_scripts', 'openai_stream_enqueue_script');
function openai_stream_enqueue_script() {
    wp_enqueue_script(
        'openai-stream',
        plugin_dir_url(__FILE__) . 'openai-stream.js',
        [],
        '1.0.0',
        true
    );
}
