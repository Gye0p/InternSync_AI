<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiLogReviewerService
{
    private const SYSTEM_PROMPT = 'You are an OJT log reviewer. Given a student\'s daily log entry, return ONLY valid JSON: {"grammar_suggestions": "string or empty", "skill_tags": ["array", "of", "tags"], "clarity_score": 1-5}';
    private const TIMEOUT = 5;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $geminiApiKey,
        private readonly string $geminiModel,
    ) {
    }

    /**
     * Sends log content to Google Gemini API for AI review.
     * Returns parsed JSON array or null on any failure.
     */
    public function review(string $logContent): ?array
    {
        if (trim($logContent) === '' || $this->geminiApiKey === '') {
            return null;
        }

        try {
            $url = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                $this->geminiModel,
                $this->geminiApiKey
            );

            $response = $this->httpClient->request('POST', $url, [
                'timeout' => self::TIMEOUT,
                'json' => [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => self::SYSTEM_PROMPT],
                        ],
                    ],
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $logContent],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 512,
                    ],
                ],
            ]);

            $data = $response->toArray();

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($text === null) {
                return null;
            }

            // Strip markdown code fences defensively
            $text = trim($text);
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/i', '', $text);
            $text = trim($text);

            $parsed = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($parsed)) {
                return null;
            }

            // Normalize output structure
            return [
                'grammar_suggestions' => (string) ($parsed['grammar_suggestions'] ?? ''),
                'skill_tags' => is_array($parsed['skill_tags'] ?? null) ? $parsed['skill_tags'] : [],
                'clarity_score' => is_int($parsed['clarity_score'] ?? null)
                    ? max(1, min(5, $parsed['clarity_score']))
                    : null,
            ];
        } catch (\Throwable) {
            // Never block log submission on AI failure
            return null;
        }
    }
}
