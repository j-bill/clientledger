<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\Project;
use RuntimeException;

use function Laravel\Ai\agent;

class AiDescriptionService
{
    public const DEFAULT_PROMPT = 'You are an assistant that rewrites rough time-tracking notes into a polished work log description. Write in first person, past tense, professional but plain language. Keep it concise (1-4 sentences), factual, and suitable for a client-facing invoice. Do not invent work that is not mentioned in the notes. Write the description and feedback in the same language as the notes.';

    public const FORMAT_PROMPT = 'Respond only with a JSON object containing exactly two keys: "description" (the rewritten description) and "feedback" (1-3 short sentences summarizing what you changed compared to the original notes and why). Do not wrap the JSON in markdown code fences.';

    public const DEFAULT_MODEL = 'gpt-4o-mini';

    /**
     * Check whether AI work log description generation is enabled.
     */
    public static function isEnabled(): bool
    {
        return SettingsHelper::get('ai_worklog_enabled') === '1';
    }

    /**
     * Rewrite rough time-tracking notes into a polished description.
     *
     * @return array{description: string, feedback: string}
     *
     * @throws RuntimeException when no OpenAI API key is configured
     */
    public function generate(string $notes, ?Project $project = null): array
    {
        $apiKey = SettingsHelper::get('openai_api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured');
        }

        config(['ai.providers.openai.key' => $apiKey]);

        $instructions = SettingsHelper::get('ai_worklog_prompt');
        $instructions = is_string($instructions) && $instructions !== '' ? $instructions : self::DEFAULT_PROMPT;

        $model = SettingsHelper::get('openai_model');
        $model = is_string($model) && $model !== '' ? $model : self::DEFAULT_MODEL;

        $prompt = "Notes:\n".$notes;

        if ($project) {
            $prompt .= "\n\nProject: ".$project->name;

            if ($project->customer) {
                $prompt .= "\nCustomer: ".$project->customer->name;
            }
        }

        $response = agent(instructions: $instructions."\n\n".self::FORMAT_PROMPT)->prompt($prompt, model: $model);

        return self::parseResponse($response->text);
    }

    /**
     * Parse the model response, tolerating markdown fences and plain-text
     * replies from models that ignore the JSON format instructions.
     *
     * @return array{description: string, feedback: string}
     */
    private static function parseResponse(string $text): array
    {
        $text = trim($text);
        $json = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;

        $decoded = json_decode($json, true);

        if (is_array($decoded) && is_string($decoded['description'] ?? null)) {
            $description = $decoded['description'];
            $feedback = is_string($decoded['feedback'] ?? null) ? $decoded['feedback'] : '';
        } else {
            $description = $text;
            $feedback = '';
        }

        return [
            'description' => mb_substr(trim($description), 0, 1500),
            'feedback' => mb_substr(trim($feedback), 0, 1500),
        ];
    }
}
