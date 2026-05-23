<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LlmController extends Controller
{
    public function parse(Request $request)
    {
        $body = $request->all();

        $llmEndpoint = config('services.llm.endpoint');
        $llmKey = config('services.llm.key');

        if (! $llmEndpoint) {
            return response()->json(['error' => 'LLM endpoint not configured'], 500);
        }

        try {
            // Normalize payload for OpenRouter / chat completions if client sent `prompt`.
            $payload = $body;
            if (isset($body['prompt']) && ! isset($body['messages'])) {
                $payload = [
                    'model' => $body['model'] ?? 'meta-llama/llama-3.2-3b-instruct:free',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $body['prompt'],
                        ],
                    ],
                    // forward through any other common options
                    'max_tokens' => $body['max_tokens'] ?? 300,
                ];
            }

            $headers = [
                'Content-Type' => 'application/json',
            ];
            if ($llmKey) {
                $headers['Authorization'] = 'Bearer ' . $llmKey;
            }

            $resp = Http::withHeaders($headers)->post($llmEndpoint, $payload);

            if (! $resp->successful()) {
                return response()->json(['error' => 'LLM request failed', 'status' => $resp->status(), 'body' => $resp->body()], 502);
            }

            $json = $resp->json();

            // Try to extract assistant text from common chat completion shapes
            $content = null;
            if (isset($json['choices'][0]['message']['content'])) {
                $content = $json['choices'][0]['message']['content'];
            } elseif (isset($json['choices'][0]['message'])) {
                // some variants return message as string
                $content = $json['choices'][0]['message'];
            } elseif (isset($json['choices'][0]['text'])) {
                $content = $json['choices'][0]['text'];
            } elseif (isset($json['content'])) {
                $content = $json['content'];
            } else {
                // fallback to raw body string
                $content = $resp->body();
            }

            // If the provider already returned a structured `parsed` key, pass it through
            if (is_array($json) && isset($json['parsed'])) {
                return response()->json(['parsed' => $json['parsed']]);
            }

            // Try to parse the assistant content as JSON directly
            $maybe = null;
            try {
                $maybe = json_decode($content, true);
            } catch (\Throwable $e) {
                $maybe = null;
            }

            if (is_array($maybe) && isset($maybe['merchant']) && isset($maybe['amount'])) {
                return response()->json(['parsed' => $maybe]);
            }

            // Attempt to extract a JSON object substring from the text (greedy)
            if (is_string($content)) {
                if (preg_match('/(\{(?:.*)\})/s', $content, $m)) {
                    $candidate = $m[1];
                    $decodedCandidate = json_decode($candidate, true);
                    if (is_array($decodedCandidate) && isset($decodedCandidate['merchant']) && isset($decodedCandidate['amount'])) {
                        return response()->json(['parsed' => $decodedCandidate]);
                    }
                }
            }

            // Otherwise return raw assistant content under `content`
            return response()->json(['content' => $content]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'LLM proxy error', 'message' => $e->getMessage()], 502);
        }
    }
}
