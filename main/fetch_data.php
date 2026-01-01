<?php
/**
 * Enhanced data fetching with improved fundamental data support
 * Using multiple endpoints for better reliability
 */

function fetchYahooFinanceData($ticker, $months = 12) {
    $period2 = time();
    $period1 = strtotime("-$months months");
    
    $url = "https://query2.finance.yahoo.com/v8/finance/chart/{$ticker}?period1={$period1}&period2={$period2}&interval=1d";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: application/json',
                'Accept-Language: en-US,en;q=0.9'
            ],
            'timeout' => 15
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        error_log("Failed to fetch data for ticker: $ticker");
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['chart']['result'][0])) {
        error_log("Invalid response structure for ticker: $ticker");
        return false;
    }
    
    $result = $data['chart']['result'][0];
    $timestamps = $result['timestamp'];
    $quotes = $result['indicators']['quote'][0];
    
    $historicalData = [];
    
    for ($i = 0; $i < count($timestamps); $i++) {
        if (is_null($quotes['open'][$i]) || is_null($quotes['close'][$i])) {
            continue;
        }
        
        $historicalData[] = [
            'date' => date('Y-m-d', $timestamps[$i]),
            'timestamp' => $timestamps[$i],
            'open' => floatval($quotes['open'][$i]),
            'high' => floatval($quotes['high'][$i]),
            'low' => floatval($quotes['low'][$i]),
            'close' => floatval($quotes['close'][$i]),
            'volume' => intval($quotes['volume'][$i])
        ];
    }
    
    return $historicalData;
}

/**
 * Fetch fundamental data using multiple endpoints for reliability
 */
function fetchFundamentalData($ticker) {
    // Try primary endpoint first
    $fundamentals = fetchFromQuoteSummary($ticker);
    
    if (!$fundamentals || $fundamentals['marketCap'] === 'N/A') {
        // Fallback: try statistics endpoint
        $fundamentals = fetchFromStatistics($ticker);
    }
    
    return $fundamentals;
}

/**
 * Primary method: quoteSummary endpoint [web:31][web:33]
 */
function fetchFromQuoteSummary($ticker) {
    $modules = [
        'price',
        'summaryDetail',
        'financialData',
        'defaultKeyStatistics',
        'quoteType',
        'assetProfile'
    ];
    
    $moduleString = implode(',', $modules);
    $url = "https://query2.finance.yahoo.com/v10/finance/quoteSummary/{$ticker}?modules={$moduleString}";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json'
            ],
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        return getDefaultFundamentals();
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['quoteSummary']['result'][0])) {
        return getDefaultFundamentals();
    }
    
    $result = $data['quoteSummary']['result'][0];
    
    // Extract data with fallbacks [web:37]
    $price = $result['price'] ?? [];
    $summary = $result['summaryDetail'] ?? [];
    $financial = $result['financialData'] ?? [];
    $keyStats = $result['defaultKeyStatistics'] ?? [];
    $profile = $result['assetProfile'] ?? [];
    
    $fundamentals = [
        'longName' => $price['longName'] ?? $price['shortName'] ?? 'N/A',
        'sector' => $profile['sector'] ?? 'N/A',
        'marketCap' => formatLargeNumber($price['marketCap']['raw'] ?? $keyStats['marketCap']['raw'] ?? 0),
        'pe' => round($summary['trailingPE']['raw'] ?? $keyStats['trailingPE']['raw'] ?? 0, 2),
        'pb' => round($keyStats['priceToBook']['raw'] ?? 0, 2),
        'eps' => round($keyStats['trailingEps']['raw'] ?? $financial['currentPrice']['raw'] ?? 0, 2),
        'roe' => round(($financial['returnOnEquity']['raw'] ?? 0) * 100, 2) . '%',
        'roa' => round(($financial['returnOnAssets']['raw'] ?? 0) * 100, 2) . '%',
        'profitMargin' => round(($financial['profitMargins']['raw'] ?? 0) * 100, 2) . '%',
        'divYield' => round(($summary['dividendYield']['raw'] ?? 0) * 100, 2) . '%',
        'currentRatio' => round($financial['currentRatio']['raw'] ?? 0, 2),
        'debtToEquity' => round($financial['debtToEquity']['raw'] ?? 0, 2),
        'bookValue' => 'Rp ' . number_format($keyStats['bookValue']['raw'] ?? 0, 0, ',', '.'),
        'beta' => round($keyStats['beta']['raw'] ?? $summary['beta']['raw'] ?? 1, 2),
        'targetPrice' => 'Rp ' . number_format($financial['targetMeanPrice']['raw'] ?? 0, 0, ',', '.'),
        'recommendation' => ucfirst($financial['recommendationKey'] ?? 'hold'),
        'fiftyTwoWeekHigh' => 'Rp ' . number_format($summary['fiftyTwoWeekHigh']['raw'] ?? 0, 0, ',', '.'),
        'fiftyTwoWeekLow' => 'Rp ' . number_format($summary['fiftyTwoWeekLow']['raw'] ?? 0, 0, ',', '.')
    ];
    
    return $fundamentals;
}

/**
 * Fallback method: statistics page scraping [web:35]
 */
function fetchFromStatistics($ticker) {
    $url = "https://finance.yahoo.com/quote/{$ticker}/key-statistics";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);
    
    if ($html === FALSE) {
        return getDefaultFundamentals();
    }
    
    // Parse JSON data from script tags
    preg_match('/root\.App\.main = ({.*?});/', $html, $matches);
    
    if (!isset($matches[1])) {
        return getDefaultFundamentals();
    }
    
    $jsonData = json_decode($matches[1], true);
    $stores = $jsonData['context']['dispatcher']['stores'] ?? [];
    
    $quoteData = null;
    foreach ($stores as $store => $data) {
        if (strpos($store, 'QuoteSummaryStore') !== false) {
            $quoteData = $data;
            break;
        }
    }
    
    if (!$quoteData) {
        return getDefaultFundamentals();
    }
    
    $price = $quoteData['price'] ?? [];
    $summary = $quoteData['summaryDetail'] ?? [];
    $financial = $quoteData['financialData'] ?? [];
    $keyStats = $quoteData['defaultKeyStatistics'] ?? [];
    
    $fundamentals = [
        'longName' => $price['longName'] ?? $price['shortName'] ?? 'N/A',
        'sector' => $quoteData['assetProfile']['sector'] ?? 'N/A',
        'marketCap' => formatLargeNumber($price['marketCap']['raw'] ?? 0),
        'pe' => round($summary['trailingPE']['raw'] ?? 0, 2),
        'pb' => round($keyStats['priceToBook']['raw'] ?? 0, 2),
        'eps' => round($keyStats['trailingEps']['raw'] ?? 0, 2),
        'roe' => round(($financial['returnOnEquity']['raw'] ?? 0) * 100, 2) . '%',
        'roa' => round(($financial['returnOnAssets']['raw'] ?? 0) * 100, 2) . '%',
        'profitMargin' => round(($financial['profitMargins']['raw'] ?? 0) * 100, 2) . '%',
        'divYield' => round(($summary['dividendYield']['raw'] ?? 0) * 100, 2) . '%',
        'currentRatio' => round($financial['currentRatio']['raw'] ?? 0, 2),
        'debtToEquity' => round($financial['debtToEquity']['raw'] ?? 0, 2),
        'bookValue' => 'Rp ' . number_format($keyStats['bookValue']['raw'] ?? 0, 0, ',', '.'),
        'beta' => round($keyStats['beta']['raw'] ?? 1, 2),
        'targetPrice' => 'Rp ' . number_format($financial['targetMeanPrice']['raw'] ?? 0, 0, ',', '.'),
        'recommendation' => ucfirst($financial['recommendationKey'] ?? 'hold'),
        'fiftyTwoWeekHigh' => 'Rp ' . number_format($summary['fiftyTwoWeekHigh']['raw'] ?? 0, 0, ',', '.'),
        'fiftyTwoWeekLow' => 'Rp ' . number_format($summary['fiftyTwoWeekLow']['raw'] ?? 0, 0, ',', '.')
    ];
    
    return $fundamentals;
}

/**
 * Alternative: Use Alpha Vantage as backup (free API) [web:32]
 */
function fetchFromAlphaVantage($ticker) {
    // Remove .JK suffix for Alpha Vantage
    $symbol = str_replace('.JK', '', $ticker);
    
    // Free API key (limited to 5 calls per minute, 500 per day)
    $apiKey = 'demo'; // Replace with your free key from https://www.alphavantage.co/support/#api-key
    
    $url = "https://www.alphavantage.co/query?function=OVERVIEW&symbol={$symbol}&apikey={$apiKey}";
    
    $response = @file_get_contents($url);
    
    if ($response === FALSE) {
        return getDefaultFundamentals();
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['Symbol'])) {
        return getDefaultFundamentals();
    }
    
    $fundamentals = [
        'longName' => $data['Name'] ?? 'N/A',
        'sector' => $data['Sector'] ?? 'N/A',
        'marketCap' => formatLargeNumber(floatval($data['MarketCapitalization'] ?? 0)),
        'pe' => round(floatval($data['TrailingPE'] ?? 0), 2),
        'pb' => round(floatval($data['PriceToBookRatio'] ?? 0), 2),
        'eps' => round(floatval($data['EPS'] ?? 0), 2),
        'roe' => round(floatval($data['ReturnOnEquityTTM'] ?? 0) * 100, 2) . '%',
        'roa' => round(floatval($data['ReturnOnAssetsTTM'] ?? 0) * 100, 2) . '%',
        'profitMargin' => round(floatval($data['ProfitMargin'] ?? 0) * 100, 2) . '%',
        'divYield' => round(floatval($data['DividendYield'] ?? 0) * 100, 2) . '%',
        'currentRatio' => round(floatval($data['CurrentRatio'] ?? 0), 2),
        'debtToEquity' => 'N/A',
        'bookValue' => 'Rp ' . number_format(floatval($data['BookValue'] ?? 0), 0, ',', '.'),
        'beta' => round(floatval($data['Beta'] ?? 1), 2),
        'targetPrice' => 'Rp ' . number_format(floatval($data['AnalystTargetPrice'] ?? 0), 0, ',', '.'),
        'recommendation' => 'N/A',
        'fiftyTwoWeekHigh' => 'Rp ' . number_format(floatval($data['52WeekHigh'] ?? 0), 0, ',', '.'),
        'fiftyTwoWeekLow' => 'Rp ' . number_format(floatval($data['52WeekLow'] ?? 0), 0, ',', '.')
    ];
    
    return $fundamentals;
}

function getDefaultFundamentals() {
    return [
        'longName' => 'N/A',
        'sector' => 'N/A',
        'marketCap' => 'N/A',
        'pe' => 'N/A',
        'pb' => 'N/A',
        'eps' => 'N/A',
        'roe' => 'N/A',
        'roa' => 'N/A',
        'profitMargin' => 'N/A',
        'divYield' => 'N/A',
        'currentRatio' => 'N/A',
        'debtToEquity' => 'N/A',
        'bookValue' => 'N/A',
        'beta' => 'N/A',
        'targetPrice' => 'N/A',
        'recommendation' => 'N/A',
        'fiftyTwoWeekHigh' => 'N/A',
        'fiftyTwoWeekLow' => 'N/A'
    ];
}

function formatLargeNumber($num) {
    if (!is_numeric($num) || $num == 0) {
        return 'N/A';
    }
    
    if ($num >= 1000000000000) {
        return 'Rp ' . round($num / 1000000000000, 2) . 'T';
    } elseif ($num >= 1000000000) {
        return 'Rp ' . round($num / 1000000000, 2) . 'M';
    } elseif ($num >= 1000000) {
        return 'Rp ' . round($num / 1000000, 2) . 'Jt';
    }
    return 'Rp ' . number_format($num, 0, ',', '.');
}

function calculateFundamentalScore($fund) {
    $score = 0;
    
    // PE Ratio (lower is better, ideal < 15)
    if (is_numeric($fund['pe']) && $fund['pe'] > 0) {
        if ($fund['pe'] < 15) $score += 1.5;
        elseif ($fund['pe'] < 20) $score += 1;
        elseif ($fund['pe'] < 30) $score += 0.5;
    }
    
    // PB Ratio (lower is better, ideal < 2)
    if (is_numeric($fund['pb']) && $fund['pb'] > 0) {
        if ($fund['pb'] < 2) $score += 1.5;
        elseif ($fund['pb'] < 3) $score += 1;
        elseif ($fund['pb'] < 5) $score += 0.5;
    }
    
    // ROE (higher is better, ideal > 15%)
    $roe = floatval(str_replace('%', '', $fund['roe']));
    if ($roe > 15) $score += 2;
    elseif ($roe > 10) $score += 1.5;
    elseif ($roe > 5) $score += 1;
    
    // ROA (higher is better, ideal > 5%)
    $roa = floatval(str_replace('%', '', $fund['roa']));
    if ($roa > 5) $score += 1.5;
    elseif ($roa > 3) $score += 1;
    elseif ($roa > 1) $score += 0.5;
    
    // Current Ratio (ideal > 1.5)
    if (is_numeric($fund['currentRatio'])) {
        if ($fund['currentRatio'] > 1.5) $score += 1.5;
        elseif ($fund['currentRatio'] > 1) $score += 1;
        elseif ($fund['currentRatio'] > 0.5) $score += 0.5;
    }
    
    // Debt to Equity (lower is better, ideal < 1)
    if (is_numeric($fund['debtToEquity'])) {
        if ($fund['debtToEquity'] < 1) $score += 1.5;
        elseif ($fund['debtToEquity'] < 2) $score += 1;
        elseif ($fund['debtToEquity'] < 3) $score += 0.5;
    }
    
    // Dividend Yield (ideal > 2%)
    $divYield = floatval(str_replace('%', '', $fund['divYield']));
    if ($divYield > 2) $score += 1;
    elseif ($divYield > 1) $score += 0.5;
    
    return round($score, 1);
}
?>
