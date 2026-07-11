<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AiDescriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiController extends Controller
{
    /**
     * Rewrite rough work log notes into a polished description using AI.
     */
    public function generateWorkLogDescription(Request $request, AiDescriptionService $service)
    {
        if (! AiDescriptionService::isEnabled()) {
            return response()->json([
                'message' => 'AI description generation is disabled',
            ], 403);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1500',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $project = isset($validated['project_id'])
            ? Project::with('customer')->find($validated['project_id'])
            : null;

        try {
            $description = $service->generate($validated['notes'], $project);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'OpenAI API key is not configured',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('AI description generation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'AI description generation failed',
            ], 502);
        }

        return response()->json([
            'description' => $description,
        ]);
    }
}
