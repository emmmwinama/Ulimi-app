<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Repositories\MarketPriceRepository;
use App\Repositories\MarketPriceUpdateRepository;
use Throwable;

/**
 * Fetches Malawi retail crop prices from WFP's open Food Prices dataset
 * (CC-BY-IGO, published via data.humdata.org / HDX) and proposes them as
 * pending updates for an admin to approve — nothing here ever writes
 * directly to `market_prices`.
 *
 * Only covers the staple food crops WFP's retail-market monitoring actually
 * tracks. Cash/export crops (Tobacco, Cotton, Sunflower, Sugarcane) have no
 * equivalent open live source (checked: the Tobacco Commission publishes no
 * structured price data, and AHCX Malawi's exchange page has been frozen
 * since 2020) — those stay on manual admin entry.
 *
 * No cron on the target host, so like Weather/Ai this checks lazily: call
 * `checkForUpdates()` from the admin market page and it no-ops unless the
 * last check was more than a day ago.
 */
final class MarketPriceFeed
{
    private const CSV_URL = 'https://data.humdata.org/dataset/75ecb747-717e-4ce5-b7af-0e13cc95ba63/'
        . 'resource/da3ed5da-d6ad-4a86-b4bc-b77e74d01ab7/download/wfp_food_prices_mwi.csv';
    private const CHECK_INTERVAL_SECONDS = 24 * 3600;
    private const TIMEOUT_SECONDS = 25;
    private const SOURCE = 'WFP';

    /** WFP commodity name => this app's canonical crop name. Anything not
     *  listed here is a crop WFP doesn't track or one we leave to manual entry. */
    private const CROP_MAP = [
        'Maize' => 'Maize',
        'Rice' => 'Rice',
        'Groundnuts (shelled)' => 'Groundnut',
        'Soybeans' => 'Soybean',
        'Beans' => 'Beans',
        'Cowpeas' => 'Cowpea',
        'Pigeon peas' => 'Pigeon Pea',
        'Sorghum' => 'Sorghum',
        'Millet' => 'Millet',
        'Sweet potatoes' => 'Sweet Potato',
        'Cassava' => 'Cassava',
    ];

    public function __construct(
        private readonly MarketPriceUpdateRepository $updates = new MarketPriceUpdateRepository(),
        private readonly MarketPriceRepository $prices = new MarketPriceRepository(),
    ) {
    }

    /** @return int number of pending proposals created/refreshed, or -1 on fetch failure */
    public function checkForUpdates(bool $force = false): int
    {
        if (!$force) {
            $last = $this->updates->lastCheckedAt();
            if ($last !== null && (time() - strtotime($last . ' UTC')) < self::CHECK_INTERVAL_SECONDS) {
                return 0;
            }
        }

        $csv = $this->download();
        if ($csv === null) {
            return -1;
        }

        $latest = $this->latestRows($csv);
        $byRegionCrop = $this->aggregate($latest);

        foreach ($byRegionCrop as $key => $agg) {
            [$cropName, $region] = explode('|', $key, 2);
            $existing = $this->prices->findBySourceCropRegion(self::SOURCE, $cropName, $region);

            $this->updates->upsertPending([
                'existing_price_id' => $existing['id'] ?? null,
                'crop_name' => $cropName,
                'variety' => null,
                'unit' => 'kg',
                'price_min' => round($agg['min'], 2),
                'price_max' => round($agg['max'], 2),
                'price_avg' => round($agg['sum'] / $agg['count'], 2),
                'market' => $region . ' markets (WFP average of ' . $agg['count'] . ')',
                'region' => $region,
                'currency' => 'MWK',
                'recorded_at' => $agg['date'] . ' 00:00:00',
                'source' => self::SOURCE,
            ]);
        }

        $this->updates->markChecked();
        return count($byRegionCrop);
    }

    private function download(): ?string
    {
        try {
            $ch = curl_init(self::CSV_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_USERAGENT => 'AgriVault/1.0 (+market price feed)',
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $status !== 200 || $body === '') {
                Logger::instance()->warning('Market price feed download failed: status {status}', ['status' => $status]);
                return null;
            }
            return $body;
        } catch (Throwable $e) {
            Logger::instance()->warning('Market price feed download failed: {msg}', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * The CSV is sorted by date ascending; keep only rows from the most
     * recent date encountered (a single forward pass, small memory footprint
     * regardless of the file covering 35+ years of history).
     *
     * @return list<array{date:string,region:string,commodity:string,price:float}>
     */
    private function latestRows(string $csv): array
    {
        $latestDate = '';
        $rows = [];

        foreach (explode("\n", $csv) as $i => $line) {
            if ($i === 0 || $line === '') {
                continue; // header row (and the units/hxl row some HDX CSVs include)
            }
            $cols = str_getcsv($line);
            $date = $cols[0] ?? '';
            if ($date === '' || $date === 'date' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $commodity = $cols[8] ?? '';
            if (!isset(self::CROP_MAP[$commodity])) {
                continue;
            }
            $region = (string) preg_replace('/\s+Region$/i', '', trim((string) ($cols[1] ?? '')));
            $currency = $cols[13] ?? '';
            $price = (float) ($cols[14] ?? 0);
            if ($region === '' || $price <= 0 || $currency !== 'MWK') {
                continue;
            }

            if ($date > $latestDate) {
                $latestDate = $date;
                $rows = [];
            }
            if ($date === $latestDate) {
                $rows[] = ['date' => $date, 'region' => $region, 'commodity' => self::CROP_MAP[$commodity], 'price' => $price];
            }
        }

        return $rows;
    }

    /**
     * @param list<array{date:string,region:string,commodity:string,price:float}> $rows
     * @return array<string,array{date:string,min:float,max:float,sum:float,count:int}>
     */
    private function aggregate(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $key = $r['commodity'] . '|' . $r['region'];
            $out[$key] ??= ['date' => $r['date'], 'min' => $r['price'], 'max' => $r['price'], 'sum' => 0.0, 'count' => 0];
            $out[$key]['min'] = min($out[$key]['min'], $r['price']);
            $out[$key]['max'] = max($out[$key]['max'], $r['price']);
            $out[$key]['sum'] += $r['price'];
            $out[$key]['count']++;
        }
        return $out;
    }
}
