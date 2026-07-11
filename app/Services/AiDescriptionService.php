<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\Project;
use RuntimeException;

use function Laravel\Ai\agent;

class AiDescriptionService
{
    public const DEFAULT_PROMPT = 'You are an assistant that rewrites rough time-tracking notes into a polished work log description. Write in first person, past tense, professional but plain language. Keep it concise (1-4 sentences), factual, and suitable for a client-facing invoice. Do not invent work that is not mentioned in the notes. Respond only with the rewritten description, in the same language as the notes.';

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
     * @throws RuntimeException when no OpenAI API key is configured
     */
    public function generate(string $notes, ?Project $project = null): string
    {
        $apiKey = SettingsHelper::get('openai_api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured');
        }

        config(['ai.providers.openai.key' => $apiKey]);

        $instructions = SettingsHelper::get('ai_worklog_prompt') ?: self::DEFAULT_PROMPT;
        $model = SettingsHelper::get('openai_model') ?: self::DEFAULT_MODEL;

        $prompt = "Notes:\n".$notes;

        if ($project) {
            $prompt .= "\n\nProject: ".$project->name;

            if ($project->customer) {
                $prompt .= "\nCustomer: ".$project->customer->name;
            }
        }

        $response = agent(instructions: $instructions)->prompt($prompt, model: $model);

        return mb_substr(trim($response->text), 0, 1500);
    }
}
