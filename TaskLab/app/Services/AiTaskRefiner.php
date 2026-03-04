<?php

namespace App\Services;

class AiTaskRefiner
{
    /**
     * For now this is a fake implementation so the flow works end-to-end.
     * Later this can call a real AI API (OpenAI, Claude, etc.).
     */
    public function refine(string $rawDescription): array
    {
        // A very naive fake "AI" just to have something to display
        return [
            'title' => mb_substr($rawDescription, 0, 60) . (mb_strlen($rawDescription) > 60 ? '…' : ''),
            'summary' => 'Auto-generated summary of the request based on the user description.',
            'requirements' => [
                'Clarify the current behavior and reproduce the issue in a test environment.',
                'Implement the expected behavior described by the requester.',
                'Ensure no regressions on related pages or flows.',
            ],
            'behavior' => "Current: behavior described by the user in the raw description.\nDesired: updated behavior matching the refined requirements.",
            'test_cases' => [
                'Open the affected page and reproduce the scenario described by the user.',
                'Verify that the new behavior matches the expected outcome.',
                'Check edge cases and different devices/browsers if applicable.',
            ],
        ];
    }
}
