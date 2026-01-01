<?php
/**
 * GOLD (XAUUSD) SCALPING SCANNER
 * High Accuracy Scalping Strategy for M1/M5 Timeframe
 * Risk/Reward: SL 50 PIPS | TP 50-100 PIPS
 * Author: SahamAnalisa Pro Trading System
 * Date: December 2025
 */

require_once 'db_config.php';
require_once 'fetch_data.php';
require_once 'indicators.php';

header('Content-Type: application/json');

// ==================== GOLD-SPECIFIC CONFIGURATION ====================
define('GOLD_TICKER', 'GC=F'); // Yahoo Finance Gold Futures ticker
define('GOLD_SL_PIPS', 50);     // Stop Loss 50 pips
define('GOLD_TP1_PIPS', 50);    // Take Profit 1: 50 pips (1:1)
define('GOLD_TP2_PIPS', 100);   // Take Profit 2: 100 pips (1:2)
define('GOLD_PIP_VALUE', 0.10); // 1 pip = $0.10 for XAUUSD

// ==================== FETCH GOLD DATA ====================
function fetchGoldData($interval = '5m', $range = '1d') {
    $ticker = 'GC=F'; // Gold Futures
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?interval={$interval}&range={$range}";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json'
            ],
            'timeout' => 15
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['chart']['result'][0])) {
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
            'date' => date('Y-m-d H:i:s', $timestamps[$i]),
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

// ==================== GOLD SCALPING INDICATORS ====================

/**
 * EMA Crossover Strategy (50 EMA vs 200 EMA)
 * Bullish: EMA50 > EMA200 | Bearish: EMA50 < EMA200
 */
function calculateGoldEMA($data, $period) {
    $closes = array_column($data, 'close');
    $multiplier = 2 / ($period + 1);
    $ema = [];
    
    // Start with SMA
    $sma = array_sum(array_slice($closes, 0, $period)) / $period;
    $ema[] = $sma;
    
    for ($i = 1; $i < count($closes); $i++) {
        if ($i < $period - 1) {
            $ema[] = null;
        } else if ($i == $period - 1) {
            $ema[] = $sma;
        } else {
            $ema[] = ($closes[$i] - $ema[$i - 1]) * $multiplier + $ema[$i - 1];
        }
    }
    
    return $ema;
}

/**
 * Stochastic Oscillator for Scalping (14,3,3)
 * Buy: Stochastic crosses above 20 (oversold)
 * Sell: Stochastic crosses below 80 (overbought)
 */
function calculateGoldStochastic($data, $kPeriod = 14, $dPeriod = 3) {
    $highs = array_column($data, 'high');
    $lows = array_column($data, 'low');
    $closes = array_column($data, 'close');
    
    $kValues = [];
    
    for ($i = 0; $i < count($closes); $i++) {
        if ($i < $kPeriod - 1) {
            $kValues[] = null;
        } else {
            $highestHigh = max(array_slice($highs, $i - $kPeriod + 1, $kPeriod));
            $lowestLow = min(array_slice($lows, $i - $kPeriod + 1, $kPeriod));
            
            if ($highestHigh == $lowestLow) {
                $kValues[] = 50;
            } else {
                $kValues[] = (($closes[$i] - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
            }
        }
    }
    
    // Calculate %D (SMA of %K)
    $dValues = [];
    for ($i = 0; $i < count($kValues); $i++) {
        if ($i < $kPeriod + $dPeriod - 2 || is_null($kValues[$i])) {
            $dValues[] = null;
        } else {
            $validK = array_filter(array_slice($kValues, $i - $dPeriod + 1, $dPeriod), function($v) {
                return !is_null($v);
            });
            $dValues[] = count($validK) > 0 ? array_sum($validK) / count($validK) : null;
        }
    }
    
    return ['k' => $kValues, 'd' => $dValues];
}

/**
 * ATR (Average True Range) for Volatility-Based Stop Loss
 */
function calculateGoldATR($data, $period = 14) {
    $highs = array_column($data, 'high');
    $lows = array_column($data, 'low');
    $closes = array_column($data, 'close');
    
    $tr = [];
    for ($i = 1; $i < count($closes); $i++) {
        $tr1 = $highs[$i] - $lows[$i];
        $tr2 = abs($highs[$i] - $closes[$i - 1]);
        $tr3 = abs($lows[$i] - $closes[$i - 1]);
        $tr[] = max($tr1, $tr2, $tr3);
    }
    
    if (count($tr) < $period) {
        return null;
    }
    
    // Simple ATR using SMA
    $atr = array_sum(array_slice($tr, -$period)) / $period;
    return $atr;
}

/**
 * Bollinger Bands Squeeze Detection
 * Entry saat price bounce dari lower/upper band
 */
function calculateGoldBollingerBands($data, $period = 20, $stdDev = 2) {
    $closes = array_column($data, 'close');
    
    if (count($closes) < $period) {
        return null;
    }
    
    // Calculate SMA
    $recentCloses = array_slice($closes, -$period);
    $sma = array_sum($recentCloses) / $period;
    
    // Calculate Standard Deviation
    $variance = 0;
    foreach ($recentCloses as $close) {
        $variance += pow($close - $sma, 2);
    }
    $std = sqrt($variance / $period);
    
    return [
        'upper' => $sma + ($stdDev * $std),
        'middle' => $sma,
        'lower' => $sma - ($stdDev * $std),
        'bandwidth' => (($sma + ($stdDev * $std)) - ($sma - ($stdDev * $std))) / $sma * 100
    ];
}

/**
 * Support & Resistance for Gold (Dynamic Pivot Points)
 */
function calculateGoldPivotPoints($data) {
    if (count($data) < 2) {
        return null;
    }
    
    // Use yesterday's data for pivot calculation
    $yesterday = $data[count($data) - 2];
    
    $high = $yesterday['high'];
    $low = $yesterday['low'];
    $close = $yesterday['close'];
    
    $pivot = ($high + $low + $close) / 3;
    $r1 = (2 * $pivot) - $low;
    $s1 = (2 * $pivot) - $high;
    $r2 = $pivot + ($high - $low);
    $s2 = $pivot - ($high - $low);
    
    return [
        'pivot' => round($pivot, 2),
        'r1' => round($r1, 2),
        'r2' => round($r2, 2),
        's1' => round($s1, 2),
        's2' => round($s2, 2)
    ];
}

// ==================== GOLD SCALPING SIGNAL GENERATOR ====================

/**
 * HIGH ACCURACY GOLD SCALPING STRATEGY
 * 
 * BUY CONDITIONS:
 * 1. EMA50 > EMA200 (Uptrend)
 * 2. Stochastic %K crosses above %D in oversold zone (< 30)
 * 3. Price near lower Bollinger Band OR Support level
 * 4. RSI between 30-45 (oversold recovery)
 * 5. Volume > Average Volume
 * 
 * SELL CONDITIONS:
 * 1. EMA50 < EMA200 (Downtrend)
 * 2. Stochastic %K crosses below %D in overbought zone (> 70)
 * 3. Price near upper Bollinger Band OR Resistance level
 * 4. RSI between 55-70 (overbought)
 * 5. Volume > Average Volume
 */
function generateGoldScalpingSignals($data) {
    if (count($data) < 200) {
        return [
            'status' => 'error',
            'message' => 'Insufficient data for analysis. Need at least 200 candles.'
        ];
    }
    
    // Calculate all indicators
    $ema50 = calculateGoldEMA($data, 50);
    $ema200 = calculateGoldEMA($data, 200);
    $rsi = calculateRSI($data, 14);
    $stoch = calculateGoldStochastic($data, 14, 3);
    $bb = calculateGoldBollingerBands($data, 20, 2);
    $atr = calculateGoldATR($data, 14);
    $pivots = calculateGoldPivotPoints($data);
    
    // Get current and previous values
    $currentPrice = end($data)['close'];
    $currentEMA50 = end($ema50);
    $currentEMA200 = end($ema200);
    $currentRSI = end($rsi);
    $currentStochK = end($stoch['k']);
    $currentStochD = end($stoch['d']);
    
    // Previous values for crossover detection
    $prevStochK = $stoch['k'][count($stoch['k']) - 2];
    $prevStochD = $stoch['d'][count($stoch['d']) - 2];
    
    // Volume analysis
    $volumes = array_column($data, 'volume');
    $avgVolume = array_sum(array_slice($volumes, -20)) / 20;
    $currentVolume = end($volumes);
    
    // Signal initialization
    $signal = 'HOLD';
    $signalStrength = 0;
    $reasons = [];
    $entryPrice = $currentPrice;
    $stopLoss = 0;
    $takeProfit1 = 0;
    $takeProfit2 = 0;
    
    // ========== BUY SIGNAL DETECTION ==========
    $buyScore = 0;
    
    // 1. EMA Trend (Weight: 2)
    if ($currentEMA50 > $currentEMA200) {
        $buyScore += 2;
        $reasons[] = "✅ Uptrend: EMA50 (" . round($currentEMA50, 2) . ") > EMA200 (" . round($currentEMA200, 2) . ")";
    }
    
    // 2. Stochastic Golden Cross in Oversold (Weight: 3)
    if ($currentStochK > $currentStochD && $prevStochK <= $prevStochD && $currentStochK < 30) {
        $buyScore += 3;
        $reasons[] = "🔥 GOLDEN CROSS: Stochastic %K crossed above %D in oversold zone!";
    } elseif ($currentStochK < 20 && $currentStochD < 20) {
        $buyScore += 2;
        $reasons[] = "✅ Stochastic oversold: %K=" . round($currentStochK, 1) . ", %D=" . round($currentStochD, 1);
    }
    
    // 3. RSI Oversold Recovery (Weight: 2)
    if ($currentRSI >= 30 && $currentRSI <= 45) {
        $buyScore += 2;
        $reasons[] = "✅ RSI recovery zone: " . round($currentRSI, 1);
    }
    
    // 4. Bollinger Bands (Weight: 2)
    if ($bb && $currentPrice <= $bb['lower'] * 1.002) { // Within 0.2% of lower band
        $buyScore += 2;
        $reasons[] = "✅ Price near BB Lower: " . round($bb['lower'], 2);
    }
    
    // 5. Support Level (Weight: 1)
    if ($pivots) {
        if (abs($currentPrice - $pivots['s1']) / $currentPrice < 0.005) {
            $buyScore += 1;
            $reasons[] = "✅ Near Support S1: " . $pivots['s1'];
        }
        if (abs($currentPrice - $pivots['s2']) / $currentPrice < 0.005) {
            $buyScore += 1;
            $reasons[] = "✅ Near Support S2: " . $pivots['s2'];
        }
    }
    
    // 6. Volume Confirmation (Weight: 1)
    if ($currentVolume > $avgVolume * 1.2) {
        $buyScore += 1;
        $reasons[] = "✅ High volume: " . number_format($currentVolume);
    }
    
    // ========== SELL SIGNAL DETECTION ==========
    $sellScore = 0;
    
    // 1. EMA Trend (Weight: 2)
    if ($currentEMA50 < $currentEMA200) {
        $sellScore += 2;
        $reasons[] = "🔻 Downtrend: EMA50 (" . round($currentEMA50, 2) . ") < EMA200 (" . round($currentEMA200, 2) . ")";
    }
    
    // 2. Stochastic Death Cross in Overbought (Weight: 3)
    if ($currentStochK < $currentStochD && $prevStochK >= $prevStochD && $currentStochK > 70) {
        $sellScore += 3;
        $reasons[] = "🚨 DEATH CROSS: Stochastic %K crossed below %D in overbought zone!";
    } elseif ($currentStochK > 80 && $currentStochD > 80) {
        $sellScore += 2;
        $reasons[] = "⚠️ Stochastic overbought: %K=" . round($currentStochK, 1) . ", %D=" . round($currentStochD, 1);
    }
    
    // 3. RSI Overbought (Weight: 2)
    if ($currentRSI >= 55 && $currentRSI <= 70) {
        $sellScore += 2;
        $reasons[] = "⚠️ RSI overbought zone: " . round($currentRSI, 1);
    }
    
    // 4. Bollinger Bands (Weight: 2)
    if ($bb && $currentPrice >= $bb['upper'] * 0.998) { // Within 0.2% of upper band
        $sellScore += 2;
        $reasons[] = "⚠️ Price near BB Upper: " . round($bb['upper'], 2);
    }
    
    // 5. Resistance Level (Weight: 1)
    if ($pivots) {
        if (abs($currentPrice - $pivots['r1']) / $currentPrice < 0.005) {
            $sellScore += 1;
            $reasons[] = "⚠️ Near Resistance R1: " . $pivots['r1'];
        }
        if (abs($currentPrice - $pivots['r2']) / $currentPrice < 0.005) {
            $sellScore += 1;
            $reasons[] = "⚠️ Near Resistance R2: " . $pivots['r2'];
        }
    }
    
    // 6. Volume Confirmation (Weight: 1)
    if ($currentVolume > $avgVolume * 1.2) {
        $sellScore += 1;
        $reasons[] = "⚠️ High volume: " . number_format($currentVolume);
    }
    
    // ========== FINAL SIGNAL DETERMINATION ==========
    if ($buyScore >= 7) {
        $signal = 'STRONG BUY';
        $signalStrength = min(100, $buyScore * 10);
        
        // Calculate entry points (BUY)
        $stopLoss = $currentPrice - (GOLD_SL_PIPS * GOLD_PIP_VALUE);
        $takeProfit1 = $currentPrice + (GOLD_TP1_PIPS * GOLD_PIP_VALUE);
        $takeProfit2 = $currentPrice + (GOLD_TP2_PIPS * GOLD_PIP_VALUE);
        
    } elseif ($buyScore >= 5) {
        $signal = 'BUY';
        $signalStrength = min(80, $buyScore * 10);
        
        $stopLoss = $currentPrice - (GOLD_SL_PIPS * GOLD_PIP_VALUE);
        $takeProfit1 = $currentPrice + (GOLD_TP1_PIPS * GOLD_PIP_VALUE);
        $takeProfit2 = $currentPrice + (GOLD_TP2_PIPS * GOLD_PIP_VALUE);
        
    } elseif ($sellScore >= 7) {
        $signal = 'STRONG SELL';
        $signalStrength = min(100, $sellScore * 10);
        
        // Calculate entry points (SELL)
        $stopLoss = $currentPrice + (GOLD_SL_PIPS * GOLD_PIP_VALUE);
        $takeProfit1 = $currentPrice - (GOLD_TP1_PIPS * GOLD_PIP_VALUE);
        $takeProfit2 = $currentPrice - (GOLD_TP2_PIPS * GOLD_PIP_VALUE);
        
    } elseif ($sellScore >= 5) {
        $signal = 'SELL';
        $signalStrength = min(80, $sellScore * 10);
        
        $stopLoss = $currentPrice + (GOLD_SL_PIPS * GOLD_PIP_VALUE);
        $takeProfit1 = $currentPrice - (GOLD_TP1_PIPS * GOLD_PIP_VALUE);
        $takeProfit2 = $currentPrice - (GOLD_TP2_PIPS * GOLD_PIP_VALUE);
    }
    
    // Calculate Risk/Reward Ratio
    $riskAmount = abs($currentPrice - $stopLoss);
    $rewardAmount1 = abs($takeProfit1 - $currentPrice);
    $rewardAmount2 = abs($takeProfit2 - $currentPrice);
    
    $riskRewardRatio1 = $riskAmount > 0 ? round($rewardAmount1 / $riskAmount, 2) : 0;
    $riskRewardRatio2 = $riskAmount > 0 ? round($rewardAmount2 / $riskAmount, 2) : 0;
    
    return [
        'status' => 'success',
        'signal' => $signal,
        'signalStrength' => $signalStrength,
        'currentPrice' => round($currentPrice, 2),
        'entryPrice' => round($entryPrice, 2),
        'stopLoss' => round($stopLoss, 2),
        'takeProfit1' => round($takeProfit1, 2),
        'takeProfit2' => round($takeProfit2, 2),
        'riskRewardRatio1' => $riskRewardRatio1,
        'riskRewardRatio2' => $riskRewardRatio2,
        'slPips' => GOLD_SL_PIPS,
        'tp1Pips' => GOLD_TP1_PIPS,
        'tp2Pips' => GOLD_TP2_PIPS,
        'atr' => round($atr, 2),
        'indicators' => [
            'ema50' => round($currentEMA50, 2),
            'ema200' => round($currentEMA200, 2),
            'rsi' => round($currentRSI, 1),
            'stochK' => round($currentStochK, 1),
            'stochD' => round($currentStochD, 1),
            'bbUpper' => $bb ? round($bb['upper'], 2) : null,
            'bbMiddle' => $bb ? round($bb['middle'], 2) : null,
            'bbLower' => $bb ? round($bb['lower'], 2) : null,
        ],
        'pivotPoints' => $pivots,
        'volume' => [
            'current' => $currentVolume,
            'average' => round($avgVolume, 0)
        ],
        'buyScore' => $buyScore,
        'sellScore' => $sellScore,
        'reasons' => $reasons,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// ==================== MAIN EXECUTION ====================

try {
    // Fetch M5 data (last 1 day = ~288 candles)
    $goldDataM5 = fetchGoldData('5m', '1d');
    
    if (!$goldDataM5 || count($goldDataM5) < 200) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch sufficient Gold data. Please try again later.'
        ]);
        exit;
    }
    
    // Generate scalping signals
    $signals = generateGoldScalpingSignals($goldDataM5);
    
    // Return JSON response
    echo json_encode($signals, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

?>
