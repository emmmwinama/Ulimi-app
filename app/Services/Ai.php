<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use App\Support\Dates;
use App\Support\Ulid;
use Throwable;

/**
 * Narrative insights over a farm's own records, via Groq's free-tier,
 * OpenAI-compatible chat completions API (open-weight models — no local
 * runtime, which a free shared host couldn't provide anyway).
 *
 * Purely additive: with no API key configured, `insight()` returns null and
 * every call site just hides the panel — nothing else depends on this.
 * Results are cached per farm/kind in `ai_insights_cache` (no cron on shared
 * hosting, so refresh happens lazily on a TTL like Weather does).
 */
final class Ai
{
    private const CACHE_TTL_SECONDS = 12 * 3600;
    private const TIMEOUT_SECONDS = 12;
    private const MAX_PROMPT_CHARS = 6000;

    public static function enabled(): bool
    {
        return trim((string) Config::get('ai.api_key', '')) !== '';
    }

    /**
     * @param array<string,mixed> $facts Plain data points to summarise — never
     *   free text from the user, so there's nothing here for a record's notes
     *   field to inject into the instruction the model receives.
     */
    public function insight(string $farmId, string $kind, string $instruction, array $facts): ?string
    {
        if (!self::enabled()) {
            return null;
        }

        $db = Database::instance();
        $cached = $db->selectOne(
            'SELECT * FROM ai_insights_cache WHERE farm_id = :fid AND kind = :kind',
            ['fid' => $farmId, 'kind' => $kind],
        );
        $factsJson = json_encode($facts, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $factsHash = md5((string) $factsJson);

        if ($cached !== null) {
            $age = time() - strtotime((string) $cached['cached_at'] . ' UTC');
            $sameFacts = hash_equals((string) $cached['model'], self::cacheTag($factsHash));
            if ($age < self::CACHE_TTL_SECONDS && $sameFacts) {
                return (string) $cached['content'];
            }
        }

        $text = $this->complete($instruction, (string) $factsJson);
        if ($text === null) {
            // Generation failed (offline, rate-limited, bad key) — serve a
            // still-relevant stale cache rather than nothing.
            return $cached !== null ? (string) $cached['content'] : null;
        }

        $row = [
            'content'   => $text,
            'model'     => self::cacheTag($factsHash),
            'cached_at' => Dates::nowUtc(),
        ];
        if ($cached !== null) {
            $db->update('ai_insights_cache', $row, ['farm_id' => $farmId, 'kind' => $kind]);
        } else {
            $db->insert('ai_insights_cache', [
                'id' => Ulid::generate(), 'farm_id' => $farmId, 'kind' => $kind, ...$row,
            ]);
        }
        return $text;
    }

    private static function cacheTag(string $factsHash): string
    {
        return (string) Config::get('ai.model', '') . ':' . $factsHash;
    }

    private function complete(string $instruction, string $factsJson): ?string
    {
        $apiKey = (string) Config::get('ai.api_key', '');
        $baseUrl = rtrim((string) Config::get('ai.base_url', ''), '/');
        $model = (string) Config::get('ai.model', '');
        if ($apiKey === '' || $baseUrl === '' || $model === '') {
            return null;
        }

        $userContent = mb_substr(
            "Here are this farm's real records, as JSON — base your summary only on these numbers:\n" . $factsJson,
            0,
            self::MAX_PROMPT_CHARS,
        );

        $payload = json_encode([
            'model' => $model,
            'temperature' => 0.4,
            'max_tokens' => 400,
            // gpt-oss models spend part of the budget on hidden reasoning before
            // the visible answer; without capping that, it can eat the whole
            // token budget and leave the reply truncated mid-sentence.
            'reasoning_effort' => 'low',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a farm records analyst writing a short, plain-language summary for a '
                        . 'Malawian smallholder farm owner. Use MWK for money. Be concrete and specific to the '
                        . 'numbers given — never invent figures that are not in the data. Where the data supports '
                        . 'it, compute and cite a derived figure (a ratio, a percent of total, a per-hectare or '
                        . 'per-kg cost) rather than just repeating the raw numbers — that is what makes the '
                        . 'analysis useful instead of generic. Write 3-5 short sentences or bullet points, no '
                        . 'headings, no markdown.',
                ],
                ['role' => 'system', 'content' => $instruction],
                ['role' => 'user', 'content' => $userContent],
            ],
        ], JSON_UNESCAPED_SLASHES);

        try {
            $ch = curl_init($baseUrl . '/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_USERAGENT => 'AgriVault/1.0',
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $status !== 200) {
                Logger::instance()->warning('AI completion failed: status {status}', ['status' => $status]);
                return null;
            }
            $json = json_decode((string) $body, true);
            $content = $json['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || trim($content) === '') {
                return null;
            }
            return trim($content);
        } catch (Throwable $e) {
            Logger::instance()->warning('AI completion failed: {msg}', ['msg' => $e->getMessage()]);
            return null;
        }
    }
}
