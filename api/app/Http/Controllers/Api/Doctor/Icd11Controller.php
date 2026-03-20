<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Icd11Controller extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'terms' => ['required', 'string', 'min:2', 'max:120'],
            'maxList' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $terms = (string) $validated['terms'];
        $maxList = (int) ($validated['maxList'] ?? 7);
        $cacheKey = 'icd11:search:' . mb_strtolower(trim($terms)) . ':' . $maxList;

        $payload = Cache::remember($cacheKey, now()->addDays(30), function () use ($terms, $maxList) {
            $url = 'https://clinicaltables.nlm.nih.gov/api/icd11_codes/v3/search';

            try {
                $response = Http::timeout(15)
                    ->retry(2, 500)
                    ->get($url, [
                        'terms' => $terms,
                        'maxList' => $maxList,
                        'df' => 'code,title',
                    ]);

                if (! $response->successful()) {
                    Log::warning('ICD-11 search failed', [
                        'terms' => $terms,
                        'status' => $response->status(),
                    ]);

                    return [
                        'total' => 0,
                        'results' => [],
                    ];
                }

                $data = $response->json();
                if (! is_array($data)) {
                    return [
                        'total' => 0,
                        'results' => [],
                    ];
                }

                $total = is_numeric($data[0] ?? null) ? (int) $data[0] : 0;
                $display = $data[3] ?? [];

                $results = [];
                if (is_array($display)) {
                    foreach ($display as $row) {
                        if (! is_array($row) || count($row) < 2) continue;
                        $code = (string) ($row[0] ?? '');
                        $title = (string) ($row[1] ?? '');
                        if ($code === '' || $title === '') continue;
                        $results[] = [
                            'code' => $code,
                            'title' => $title,
                        ];
                    }
                }

                return [
                    'total' => $total,
                    'results' => $results,
                ];
            } catch (\Throwable $e) {
                Log::warning('ICD-11 search exception', [
                    'terms' => $terms,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'total' => 0,
                    'results' => [],
                ];
            }
        });

        return response()->json($payload);
    }
}

