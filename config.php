<?php
class StockAnalyzer {
    private $ticker;
    private $data;
    
    public function __construct($ticker) {
        $this->ticker = $ticker;
    }
    
    public function fetchData($period = '6mo', $interval = '1d') {
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$this->ticker}?period1=0&period2=9999999999&interval={$interval}&range={$period}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $this->data = json_decode($response, true);
        return $this;
    }
    
    public function getOHLCV() {
        $result = $this->data['chart']['result'][0];
        $timestamps = $result['timestamp'];
        $quote = $result['indicators']['quote'][0];
        
        $ohlcv = [];
        for ($i = 0; $i < count($timestamps); $i++) {
            $ohlcv[] = [
                'date' => date('Y-m-d', $timestamps[$i]),
                'timestamp' => $timestamps[$i] * 1000,
                'open' => $quote['open'][$i] ?? 0,
                'high' => $quote['high'][$i] ?? 0,
                'low' => $quote['low'][$i] ?? 0,
                'close' => $quote['close'][$i] ?? 0,
                'volume' => $quote['volume'][$i] ?? 0
            ];
        }
        return $ohlcv;
    }
    
    public function calculateSMA($data, $period) {
        $sma = [];
        for ($i = $period - 1; $i < count($data); $i++) {
            $sum = 0;
            for ($j = 0; $j < $period; $j++) {
                $sum += $data[$i - $j]['close'];
            }
            $sma[$i] = $sum / $period;
        }
        return $sma;
    }
    
    public function calculateEMA($data, $period) {
        $k = 2 / ($period + 1);
        $ema = [];
        $ema[$period - 1] = array_sum(array_column(array_slice($data, 0, $period), 'close')) / $period;
        
        for ($i = $period; $i < count($data); $i++) {
            $ema[$i] = $data[$i]['close'] * $k + $ema[$i - 1] * (1 - $k);
        }
        return $ema;
    }
    
    public function calculateBollingerBands($data, $period = 20, $stdDev = 2) {
        $sma = $this->calculateSMA($data, $period);
        $upper = [];
        $lower = [];
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $closes = [];
            for ($j = 0; $j < $period; $j++) {
                $closes[] = $data[$i - $j]['close'];
            }
            
            $mean = $sma[$i];
            $variance = 0;
            foreach ($closes as $val) {
                $variance += pow($val - $mean, 2);
            }
            $std = sqrt($variance / $period);
            
            $upper[$i] = $mean + ($stdDev * $std);
            $lower[$i] = $mean - ($stdDev * $std);
        }
        
        return ['upper' => $upper, 'middle' => $sma, 'lower' => $lower];
    }
    
    public function calculateRSI($data, $period = 14) {
        $gains = [];
        $losses = [];
        
        for ($i = 1; $i < count($data); $i++) {
            $change = $data[$i]['close'] - $data[$i - 1]['close'];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }
        
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;
        
        for ($i = $period; $i < count($gains); $i++) {
            $avgGain = ($avgGain * ($period - 1) + $gains[$i]) / $period;
            $avgLoss = ($avgLoss * ($period - 1) + $losses[$i]) / $period;
        }
        
        if ($avgLoss == 0) return 100;
        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }
    
    public function calculateMACD($data) {
        $ema12 = $this->calculateEMA($data, 12);
        $ema26 = $this->calculateEMA($data, 26);
        
        $macd = [];
        $signal = [];
        
        foreach ($ema12 as $i => $val) {
            if (isset($ema26[$i])) {
                $macd[$i] = $val - $ema26[$i];
            }
        }
        
        $macdData = [];
        foreach ($macd as $i => $val) {
            $macdData[] = ['close' => $val];
        }
        
        if (count($macdData) >= 9) {
            $signal = $this->calculateEMA($macdData, 9);
        }
        
        $histogram = [];
        foreach ($macd as $i => $val) {
            if (isset($signal[$i])) {
                $histogram[$i] = $val - $signal[$i];
            }
        }
        
        return ['macd' => $macd, 'signal' => $signal, 'histogram' => $histogram];
    }
    
    public function calculateStochastic($data, $period = 14) {
        $k = [];
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $highs = [];
            $lows = [];
            
            for ($j = 0; $j < $period; $j++) {
                $highs[] = $data[$i - $j]['high'];
                $lows[] = $data[$i - $j]['low'];
            }
            
            $highestHigh = max($highs);
            $lowestLow = min($lows);
            $currentClose = $data[$i]['close'];
            
            if ($highestHigh - $lowestLow != 0) {
                $k[$i] = (($currentClose - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
            } else {
                $k[$i] = 50;
            }
        }
        
        return $k;
    }
    
    public function findSupportResistance($data) {
        $closes = array_column($data, 'close');
        $highs = array_column($data, 'high');
        $lows = array_column($data, 'low');
        
        $resistances = [];
        for ($i = 2; $i < count($highs) - 2; $i++) {
            if ($highs[$i] > $highs[$i-1] && $highs[$i] > $highs[$i-2] && 
                $highs[$i] > $highs[$i+1] && $highs[$i] > $highs[$i+2]) {
                $resistances[] = $highs[$i];
            }
        }
        
        $supports = [];
        for ($i = 2; $i < count($lows) - 2; $i++) {
            if ($lows[$i] < $lows[$i-1] && $lows[$i] < $lows[$i-2] && 
                $lows[$i] < $lows[$i+1] && $lows[$i] < $lows[$i+2]) {
                $supports[] = $lows[$i];
            }
        }
        
        $currentPrice = end($closes);
        
        $closestSupport = 0;
        foreach ($supports as $support) {
            if ($support < $currentPrice && $support > $closestSupport) {
                $closestSupport = $support;
            }
        }
        
        $closestResistance = PHP_FLOAT_MAX;
        foreach ($resistances as $resistance) {
            if ($resistance > $currentPrice && $resistance < $closestResistance) {
                $closestResistance = $resistance;
            }
        }
        
        return [
            'support' => $closestSupport ?: min($lows),
            'resistance' => $closestResistance == PHP_FLOAT_MAX ? max($highs) : $closestResistance,
            'all_supports' => array_unique($supports),
            'all_resistances' => array_unique($resistances)
        ];
    }
    
    public function calculateATR($data, $period = 14) {
        $tr = [];
        
        for ($i = 1; $i < count($data); $i++) {
            $high = $data[$i]['high'];
            $low = $data[$i]['low'];
            $prevClose = $data[$i-1]['close'];
            
            $tr[] = max([
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            ]);
        }
        
        $atr = array_sum(array_slice($tr, 0, $period)) / $period;
        
        for ($i = $period; $i < count($tr); $i++) {
            $atr = (($atr * ($period - 1)) + $tr[$i]) / $period;
        }
        
        return $atr;
    }
    
    public function detectPatterns($data) {
        $patterns = [];
        $len = count($data);
        
        if ($len < 3) return $patterns;
        
        $last = $data[$len - 1];
        $bodySize = abs($last['close'] - $last['open']);
        $totalRange = $last['high'] - $last['low'];
        
        if ($bodySize / $totalRange < 0.1 && $totalRange > 0) {
            $patterns[] = "Doji - Indikasi pembalikan trend";
        }
        
        $lowerWick = min($last['open'], $last['close']) - $last['low'];
        $upperWick = $last['high'] - max($last['open'], $last['close']);
        
        if ($lowerWick > 2 * $bodySize && $upperWick < $bodySize) {
            if ($last['close'] < $data[$len-2]['close']) {
                $patterns[] = "Hammer - Bullish reversal signal";
            } else {
                $patterns[] = "Hanging Man - Bearish reversal signal";
            }
        }
        
        if ($len >= 2) {
            $prev = $data[$len - 2];
            if ($prev['close'] < $prev['open'] && 
                $last['close'] > $last['open'] && 
                $last['open'] < $prev['close'] &&
                $last['close'] > $prev['open']) {
                $patterns[] = "Bullish Engulfing - Strong buy signal";
            }
        }
        
        if ($len >= 2) {
            $prev = $data[$len - 2];
            if ($prev['close'] > $prev['open'] && 
                $last['close'] < $last['open'] && 
                $last['open'] > $prev['close'] &&
                $last['close'] < $prev['open']) {
                $patterns[] = "Bearish Engulfing - Strong sell signal";
            }
        }
        
        return $patterns;
    }
    
    public function generateTradingSignal() {
        $ohlcv = $this->getOHLCV();
        if (count($ohlcv) < 50) {
            return ['error' => 'Insufficient data'];
        }
        
        $currentPrice = end($ohlcv)['close'];
        $rsi = $this->calculateRSI($ohlcv);
        $macd = $this->calculateMACD($ohlcv);
        $sma20 = $this->calculateSMA($ohlcv, 20);
        $sma50 = $this->calculateSMA($ohlcv, 50);
        $sma200 = $this->calculateSMA($ohlcv, 200);
        $ema12 = $this->calculateEMA($ohlcv, 12);
        $ema26 = $this->calculateEMA($ohlcv, 26);
        $bb = $this->calculateBollingerBands($ohlcv);
        $sr = $this->findSupportResistance($ohlcv);
        $atr = $this->calculateATR($ohlcv);
        $stochastic = $this->calculateStochastic($ohlcv);
        $patterns = $this->detectPatterns($ohlcv);
        
        $signals = [];
        $score = 0;
        
        if ($rsi < 30) {
            $signals[] = "RSI Oversold (" . round($rsi, 2) . ") - Sinyal Bullish Kuat";
            $score += 2;
        } elseif ($rsi > 70) {
            $signals[] = "RSI Overbought (" . round($rsi, 2) . ") - Sinyal Bearish Kuat";
            $score -= 2;
        } else {
            $signals[] = "RSI Netral (" . round($rsi, 2) . ")";
        }
        
        $lastMacd = end($macd['macd']);
        $lastSignal = end($macd['signal']);
        $lastHistogram = end($macd['histogram']);
        
        if ($lastMacd > $lastSignal && $lastHistogram > 0) {
            $signals[] = "MACD Bullish Crossover - Momentum Positif";
            $score += 2;
        } elseif ($lastMacd < $lastSignal && $lastHistogram < 0) {
            $signals[] = "MACD Bearish Crossover - Momentum Negatif";
            $score -= 2;
        }
        
        $lastSma20 = end($sma20);
        $lastSma50 = end($sma50);
        $lastSma200 = count($sma200) > 0 ? end($sma200) : 0;
        
        if ($currentPrice > $lastSma20 && $lastSma20 > $lastSma50) {
            $signals[] = "Golden Cross Formation - Strong Uptrend";
            $score += 2;
        } elseif ($currentPrice < $lastSma20 && $lastSma20 < $lastSma50) {
            $signals[] = "Death Cross Formation - Strong Downtrend";
            $score -= 2;
        }
        
        $lastBbUpper = end($bb['upper']);
        $lastBbLower = end($bb['lower']);
        $lastBbMiddle = end($bb['middle']);
        
        if ($currentPrice < $lastBbLower) {
            $signals[] = "Price Below BB Lower - Oversold Condition";
            $score += 1;
        } elseif ($currentPrice > $lastBbUpper) {
            $signals[] = "Price Above BB Upper - Overbought Condition";
            $score -= 1;
        }
        
        $lastStoch = end($stochastic);
        if ($lastStoch < 20) {
            $signals[] = "Stochastic Oversold - Buy Signal";
            $score += 1;
        } elseif ($lastStoch > 80) {
            $signals[] = "Stochastic Overbought - Sell Signal";
            $score -= 1;
        }
        
        $distanceToSupport = (($currentPrice - $sr['support']) / $currentPrice) * 100;
        $distanceToResistance = (($sr['resistance'] - $currentPrice) / $currentPrice) * 100;
        
        if ($distanceToSupport < 2) {
            $signals[] = "Near Strong Support (" . round($sr['support'], 2) . ") - Buy Opportunity";
            $score += 1;
        }
        
        if ($distanceToResistance < 2) {
            $signals[] = "Near Resistance (" . round($sr['resistance'], 2) . ") - Take Profit Zone";
            $score -= 1;
        }
        
        foreach ($patterns as $pattern) {
            $signals[] = "Pattern: " . $pattern;
            if (strpos($pattern, 'Bullish') !== false || strpos($pattern, 'Hammer') !== false) {
                $score += 1;
            } elseif (strpos($pattern, 'Bearish') !== false || strpos($pattern, 'Hanging') !== false) {
                $score -= 1;
            }
        }
        
        $recentVolumes = array_slice(array_column($ohlcv, 'volume'), -20);
        $avgVolume = array_sum($recentVolumes) / count($recentVolumes);
        $currentVolume = end($ohlcv)['volume'];
        
        if ($currentVolume > $avgVolume * 1.5) {
            $signals[] = "High Volume (" . number_format($currentVolume) . ") - Strong Interest";
            $score += ($currentPrice > $ohlcv[count($ohlcv)-2]['close']) ? 1 : -1;
        }
        
        // Generate Recommendation dengan SL maksimal 5%
        $recommendation = '';
        $entry = 0;
        $stopLoss = 0;
        $takeProfit = 0;
        $reasoning = '';
        $maxSLPercent = 5;
        
        if ($score >= 4) {
            $recommendation = 'STRONG BUY';
            $entry = $currentPrice;
            
            $slBasedOnSupport = $sr['support'] * 0.98;
            $slBasedOnATR = $currentPrice - (2 * $atr);
            $slBasedOnPercent = $currentPrice * (1 - $maxSLPercent/100);
            
            $stopLoss = max($slBasedOnSupport, $slBasedOnATR, $slBasedOnPercent);
            
            $actualSLPercent = (($entry - $stopLoss) / $entry) * 100;
            if ($actualSLPercent > $maxSLPercent) {
                $stopLoss = $entry * (1 - $maxSLPercent/100);
            }
            
            $riskAmount = $entry - $stopLoss;
            $takeProfit = $entry + ($riskAmount * 2);
            
            if ($takeProfit > $sr['resistance'] * 1.05) {
                $takeProfit = $sr['resistance'];
            }
            
            $reasoning = "Sinyal bullish sangat kuat dengan multiple konfirmasi. Entry di harga sekarang Rp " . number_format($entry, 0) . ", Stop Loss di Rp " . number_format($stopLoss, 0) . " (maksimal 5% dari modal). Target profit di Rp " . number_format($takeProfit, 0) . " dengan risk/reward ratio 1:2. Jangan gunakan modal lebih dari 3% per trade.";
            
        } elseif ($score >= 2) {
            $recommendation = 'BUY';
            $entry = $currentPrice;
            
            $maxSLPercentModerate = 4;
            $slBasedOnSupport = $sr['support'] * 0.97;
            $slBasedOnATR = $currentPrice - (1.5 * $atr);
            $slBasedOnPercent = $currentPrice * (1 - $maxSLPercentModerate/100);
            
            $stopLoss = max($slBasedOnSupport, $slBasedOnATR, $slBasedOnPercent);
            
            $actualSLPercent = (($entry - $stopLoss) / $entry) * 100;
            if ($actualSLPercent > $maxSLPercentModerate) {
                $stopLoss = $entry * (1 - $maxSLPercentModerate/100);
            }
            
            $riskAmount = $entry - $stopLoss;
            $takeProfit = $entry + ($riskAmount * 2);
            
            if ($takeProfit > $sr['resistance']) {
                $takeProfit = $sr['resistance'];
                $actualRR = ($takeProfit - $entry) / ($entry - $stopLoss);
                if ($actualRR < 1.5) {
                    $recommendation = 'HOLD / WAIT';
                    $entry = 0;
                    $stopLoss = 0;
                    $takeProfit = 0;
                    $reasoning = "Sinyal bullish moderat, namun risk/reward ratio tidak cukup bagus karena resistance terlalu dekat. Tunggu setup yang lebih baik atau breakout di atas resistance.";
                }
            }
            
            if ($entry > 0) {
                $reasoning = "Sinyal bullish moderat. Entry di Rp " . number_format($entry, 0) . " dengan SL ketat di Rp " . number_format($stopLoss, 0) . " (maksimal 4%). Target di Rp " . number_format($takeProfit, 0) . ". Risk/reward 1:2. Tunggu konfirmasi breakout lebih dulu.";
            }
            
        } elseif ($score <= -4) {
            $recommendation = 'STRONG SELL';
            $entry = 0;
            $stopLoss = 0;
            $takeProfit = 0;
            $reasoning = "Sinyal bearish sangat kuat. JANGAN BUY dalam kondisi apapun! Jika holding, segera cut loss. Tunggu minimal 1-2 minggu untuk evaluasi ulang atau cari saham lain yang trending naik.";
            
        } elseif ($score <= -2) {
            $recommendation = 'SELL / HOLD';
            $entry = 0;
            $stopLoss = 0;
            $takeProfit = 0;
            $reasoning = "Sinyal bearish. Jika holding, pasang trailing stop atau cut loss di support Rp " . number_format($sr['support'], 0) . ". Jangan masuk posisi baru. Tunggu reversal dengan volume tinggi dan RSI < 30.";
            
        } else {
            $recommendation = 'HOLD / WAIT';
            $entry = 0;
            $stopLoss = 0;
            $takeProfit = 0;
            $reasoning = "Sinyal netral atau mixed. Harga sedang konsolidasi antara support Rp " . number_format($sr['support'], 0) . " dan resistance Rp " . number_format($sr['resistance'], 0) . ". Tunggu breakout/breakdown dengan volume konfirmasi sebelum entry. Jangan FOMO!";
        }
        
        return [
            'ticker' => $this->ticker,
            'current_price' => round($currentPrice, 2),
            'recommendation' => $recommendation,
            'entry' => round($entry, 2),
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($takeProfit, 2),
            'risk_reward' => $entry > 0 ? round(($takeProfit - $entry) / ($entry - $stopLoss), 2) : 0,
            'rsi' => round($rsi, 2),
            'macd' => round($lastMacd, 4),
            'signal' => round($lastSignal, 4),
            'histogram' => round($lastHistogram, 4),
            'sma20' => round($lastSma20, 2),
            'sma50' => round($lastSma50, 2),
            'sma200' => $lastSma200 > 0 ? round($lastSma200, 2) : 0,
            'ema12' => round(end($ema12), 2),
            'ema26' => round(end($ema26), 2),
            'bb_upper' => round($lastBbUpper, 2),
            'bb_middle' => round($lastBbMiddle, 2),
            'bb_lower' => round($lastBbLower, 2),
            'support' => round($sr['support'], 2),
            'resistance' => round($sr['resistance'], 2),
            'atr' => round($atr, 2),
            'stochastic' => round($lastStoch, 2),
            'volume' => end($ohlcv)['volume'],
            'avg_volume' => round($avgVolume),
            'signals' => $signals,
            'patterns' => $patterns,
            'reasoning' => $reasoning,
            'score' => $score,
            'ohlcv' => $ohlcv,
            'chart_data' => [
                'sma20' => $sma20,
                'sma50' => $sma50,
                'ema12' => $ema12,
                'ema26' => $ema26,
                'bb' => $bb,
                'macd' => $macd,
                'stochastic' => $stochastic
            ]
        ];
    }
    
    public function generateActionMessage($analysis) {
        $messages = [
            'status' => '',
            'action' => '',
            'alert_type' => '',
            'next_step' => '',
            'risk_level' => ''
        ];
        
        $score = $analysis['score'];
        $rsi = $analysis['rsi'];
        
        $slPercent = $analysis['entry'] > 0 ? round((($analysis['entry'] - $analysis['stop_loss']) / $analysis['entry']) * 100, 2) : 0;
        $tpPercent = $analysis['entry'] > 0 ? round((($analysis['take_profit'] - $analysis['entry']) / $analysis['entry']) * 100, 2) : 0;
        
        if ($score >= 4) {
            $messages['status'] = "Saham ini SEDANG SANGAT BULLISH dengan momentum kuat ke atas";
            $messages['alert_type'] = 'strong-bullish';
            $messages['risk_level'] = 'Low Risk - High Probability Setup';
            $messages['action'] = "✅ SIAP UNTUK TRADING - Entry Sekarang!";
            $messages['next_step'] = "• Masuk di harga: Rp " . number_format($analysis['entry'], 0) . 
                                    "\n• Stop Loss di: Rp " . number_format($analysis['stop_loss'], 0) . " (-{$slPercent}%)" .
                                    "\n• Take Profit di: Rp " . number_format($analysis['take_profit'], 0) . " (+{$tpPercent}%)" .
                                    "\n• Risk/Reward: 1:{$analysis['risk_reward']} (Excellent!)" .
                                    "\n• Gunakan MAKSIMAL 2-3% dari total modal Anda" .
                                    "\n• Set alert untuk trailing stop setiap profit +3%" .
                                    "\n• JANGAN SERAKAH! Ambil profit bertahap di target";
        } elseif ($score >= 2) {
            $messages['status'] = "Saham ini SEDANG DALAM TREN NAIK dengan konfirmasi moderat";
            $messages['alert_type'] = 'bullish';
            $messages['risk_level'] = 'Medium Risk - Good Setup';
            $messages['action'] = "⚠️ BOLEH TRADING DENGAN HATI-HATI - Konfirmasi Dulu!";
            $messages['next_step'] = "• Tunggu breakout di atas Rp " . number_format($analysis['resistance'], 0) . 
                                    "\n• Entry setelah candle close di atas resistance dengan volume tinggi" .
                                    "\n• Stop Loss KETAT di: Rp " . number_format($analysis['stop_loss'], 0) . " (-{$slPercent}%)" .
                                    "\n• Take Profit di: Rp " . number_format($analysis['take_profit'], 0) . " (+{$tpPercent}%)" .
                                    "\n• Gunakan MAKSIMAL 1-2% dari total modal" .
                                    "\n• Siap cut loss CEPAT jika harga breakdown ke bawah support";
        } elseif ($score >= 0) {
            $messages['status'] = "Saham ini SEDANG KONSOLIDASI dan menunggu arah yang jelas";
            $messages['alert_type'] = 'neutral';
            $messages['risk_level'] = 'High Risk - Uncertain Direction';
            $messages['action'] = "⏸️ JANGAN TRADE DULU - Tunggu Konfirmasi!";
            $messages['next_step'] = "• Pantau level support: Rp " . number_format($analysis['support'], 0) . 
                                    "\n• Pantau level resistance: Rp " . number_format($analysis['resistance'], 0) .
                                    "\n• Tunggu breakout ATAU breakdown dengan volume 2x lipat" .
                                    "\n• Set price alert di kedua level tersebut" .
                                    "\n• JANGAN masuk sebelum arah jelas" .
                                    "\n• Review kembali dalam 2-3 hari trading";
        } elseif ($score >= -2) {
            $messages['status'] = "Saham ini SEDANG LEMAH dengan tekanan jual moderat";
            $messages['alert_type'] = 'bearish';
            $messages['risk_level'] = 'Medium Risk - Weak Setup';
            $messages['action'] = "🛑 SEBAIKNYA TIDAK TRADING - Momentum Lemah!";
            $messages['next_step'] = "• Jika HOLDING: Pasang SL di Rp " . number_format($analysis['support'], 0) . 
                                    "\n• Pertimbangkan cut loss untuk protect modal" .
                                    "\n• JANGAN average down (beli lagi saat turun)" .
                                    "\n• Tunggu RSI turun ke < 30 untuk bottom fishing" .
                                    "\n• Cari saham lain dengan trend naik yang lebih jelas";
        } else {
            $messages['status'] = "Saham ini SEDANG SANGAT BEARISH dengan momentum turun kuat";
            $messages['alert_type'] = 'strong-bearish';
            $messages['risk_level'] = 'High Risk - Avoid Entry';
            $messages['action'] = "🚫 HINDARI TRADING - Saham Sangat Lemah!";
            $messages['next_step'] = "• JANGAN BUY dalam kondisi apapun!" .
                                    "\n• Jika HOLDING: JUAL SEKARANG di market price" .
                                    "\n• Cut loss lebih baik dari rugi terus" .
                                    "\n• Tunggu MINIMAL 1-2 minggu untuk re-evaluasi" .
                                    "\n• Pindah modal ke saham yang sedang uptrend" .
                                    "\n• Atau simpan cash dulu, better safe than sorry";
        }
        
        if ($rsi < 30 && $score >= 2) {
            $messages['action'] .= "\n\n💡 BONUS: RSI Oversold - Potensi Bounce Sangat Tinggi!";
        } elseif ($rsi > 70 && $score <= -2) {
            $messages['action'] .= "\n\n⚠️ WARNING: RSI Overbought - Risiko Koreksi Tinggi!";
        }
        
        return $messages;
    }
    
    public function getSimpleExplanation($analysis) {
        $explanations = [];
        
        if ($analysis['score'] >= 4) {
            $explanations['condition'] = "🟢 Saham ini sedang dalam kondisi SANGAT BAGUS! Harga sedang naik kuat dan banyak sinyal positif.";
            $explanations['analogy'] = "Ibaratnya: Mobil sedang ngebut di jalan tol yang mulus, gas pol!";
        } elseif ($analysis['score'] >= 2) {
            $explanations['condition'] = "🟢 Saham ini sedang dalam kondisi CUKUP BAGUS. Harga cenderung naik meski ada sedikit hambatan.";
            $explanations['analogy'] = "Ibaratnya: Mobil jalan pelan-pelan tapi tetap maju, hati-hati ada polisi tidur.";
        } elseif ($analysis['score'] >= 0) {
            $explanations['condition'] = "🟡 Saham ini sedang BINGUNG mau kemana. Harga naik-turun gak jelas arahnya.";
            $explanations['analogy'] = "Ibaratnya: Mobil parkir di perempatan, belum tau mau belok kiri atau kanan.";
        } elseif ($analysis['score'] >= -2) {
            $explanations['condition'] = "🔴 Saham ini sedang AGAK LEMAH. Harga cenderung turun, mulai ada masalah.";
            $explanations['analogy'] = "Ibaratnya: Mobil mulai mogok-mogok, mesin kurang sehat.";
        } else {
            $explanations['condition'] = "🔴 Saham ini sedang SANGAT LEMAH! Harga turun terus, bahaya!";
            $explanations['analogy'] = "Ibaratnya: Mobil lagi meluncur turun gunung tanpa rem, BAHAYA!";
        }
        
        if ($analysis['rsi'] < 30) {
            $explanations['rsi'] = "RSI {$analysis['rsi']} = Saham ini SUDAH TERLALU MURAH (oversold). Seperti barang diskon gede, banyak orang mau beli lagi.";
        } elseif ($analysis['rsi'] > 70) {
            $explanations['rsi'] = "RSI {$analysis['rsi']} = Saham ini SUDAH TERLALU MAHAL (overbought). Seperti harga naik terus, orang mulai males beli.";
        } else {
            $explanations['rsi'] = "RSI {$analysis['rsi']} = Harga saham masih NORMAL, tidak terlalu murah atau mahal.";
        }
        
        if ($analysis['macd'] > $analysis['signal']) {
            $explanations['macd'] = "MACD Positif = Momentum NAIK. Seperti mobil yang lagi nambah gas, semakin cepat!";
        } else {
            $explanations['macd'] = "MACD Negatif = Momentum TURUN. Seperti mobil yang lagi ngerem, semakin pelan.";
        }
        
        $explanations['support_resistance'] = "Support Rp " . number_format($analysis['support'], 0) . " = Harga TIDAK BOLEH turun di bawah ini (seperti lantai).\n" .
                                             "Resistance Rp " . number_format($analysis['resistance'], 0) . " = Harga SUSAH naik di atas ini (seperti plafon).";
        
        if ($analysis['score'] >= 4) {
            $explanations['action_simple'] = "✅ BISA BELI SEKARANG!\n" .
                                             "• Beli di harga sekarang: Rp " . number_format($analysis['entry'], 0) . "\n" .
                                             "• Jual kalau rugi sampai: Rp " . number_format($analysis['stop_loss'], 0) . " (cut loss)\n" .
                                             "• Jual kalau untung sampai: Rp " . number_format($analysis['take_profit'], 0) . " (ambil untung)\n" .
                                             "• Jangan pakai uang lebih dari 2-3% total modal Anda!";
        } elseif ($analysis['score'] >= 2) {
            $explanations['action_simple'] = "⚠️ BOLEH BELI, TAPI HATI-HATI!\n" .
                                             "• Tunggu harga tembus Rp " . number_format($analysis['resistance'], 0) . " dulu\n" .
                                             "• Kalau sudah tembus, baru beli\n" .
                                             "• Siap-siap jual kalau tiba-tiba turun\n" .
                                             "• Pakai uang maksimal 1-2% dari total modal";
        } elseif ($analysis['score'] >= 0) {
            $explanations['action_simple'] = "⏸️ JANGAN BELI DULU!\n" .
                                             "• Saham ini lagi bingung mau kemana\n" .
                                             "• Tunggu 2-3 hari sampai arah jelas\n" .
                                             "• Pantau terus, tapi jangan beli\n" .
                                             "• Cari saham lain yang lebih jelas sinyalnya";
        } elseif ($analysis['score'] >= -2) {
            $explanations['action_simple'] = "🛑 JANGAN BELI!\n" .
                                             "• Kalau sudah punya saham ini, pertimbangkan JUAL\n" .
                                             "• Harga lagi turun, belum ada tanda-tanda naik\n" .
                                             "• Tunggu minimal 5-7 hari\n" .
                                             "• Cari saham lain yang sedang naik";
        } else {
            $explanations['action_simple'] = "🚫 BAHAYA! JANGAN BELI!\n" .
                                             "• Kalau punya saham ini, JUAL SEKARANG!\n" .
                                             "• Harga turun terus, akan lebih rugi kalau ditahan\n" .
                                             "• Tunggu minimal 2 minggu sebelum lihat lagi\n" .
                                             "• Pindah ke saham yang lebih aman";
        }
        
        $riskPercent = $analysis['entry'] > 0 ? round((($analysis['entry'] - $analysis['stop_loss']) / $analysis['entry']) * 100, 1) : 0;
        $profitPercent = $analysis['entry'] > 0 ? round((($analysis['take_profit'] - $analysis['entry']) / $analysis['entry']) * 100, 1) : 0;
        
        if ($analysis['entry'] > 0) {
            $riskRupiah = $analysis['entry'] - $analysis['stop_loss'];
            $profitRupiah = $analysis['take_profit'] - $analysis['entry'];
            
            $explanations['risk_reward'] = "📊 Kalau beli SEKARANG dengan modal Rp 10 juta:\n\n" .
                                          "💸 Kemungkinan RUGI Maksimal:\n" .
                                          "   • {$riskPercent}% dari modal = Rp " . number_format($riskRupiah * 10000000 / $analysis['entry'], 0) . "\n" .
                                          "   • SL di Rp " . number_format($analysis['stop_loss'], 0) . "\n\n" .
                                          "💰 Kemungkinan UNTUNG Target:\n" .
                                          "   • {$profitPercent}% dari modal = Rp " . number_format($profitRupiah * 10000000 / $analysis['entry'], 0) . "\n" .
                                          "   • TP di Rp " . number_format($analysis['take_profit'], 0) . "\n\n" .
                                          "⚖️ Risk/Reward Ratio: 1:{$analysis['risk_reward']}\n" .
                                          "   Artinya: Risiko rugi Rp 1 untuk dapat untung Rp {$analysis['risk_reward']}\n\n" .
                                          "⚠️ PENTING:\n" .
                                          "• Stop Loss MAKSIMAL hanya {$riskPercent}% (sudah aman!)\n" .
                                          "• Jangan pakai modal lebih dari 3% total aset Anda\n" .
                                          "• Misalnya total uang Rp 100 juta → maksimal trade Rp 3 juta saja";
        } else {
            $explanations['risk_reward'] = "❌ Tidak disarankan untuk beli dalam kondisi saat ini.\n\n" .
                                          "Tunggu setup yang lebih baik dengan:\n" .
                                          "• Sinyal bullish lebih kuat (score minimal +2)\n" .
                                          "• Risk/reward ratio minimal 1:2\n" .
                                          "• Volume konfirmasi tinggi\n\n" .
                                          "💡 TIP: Sabar menunggu = Profit lebih aman!";
        }
        
        return $explanations;
    }
    
    public function fetchStockNews($ticker) {
        $cleanTicker = str_replace('.JK', '', $ticker);
        $news = [];
        
        $searchQuery = urlencode($cleanTicker . " saham IHSG");
        $rssUrl = "https://news.google.com/rss/search?q={$searchQuery}&hl=id&gl=ID&ceid=ID:id";
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $rssUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                $xml = @simplexml_load_string($response);
                if ($xml && isset($xml->channel->item)) {
                    $count = 0;
                    foreach ($xml->channel->item as $item) {
                        if ($count >= 5) break;
                        
                        $title = (string)$item->title;
                        $link = (string)$item->link;
                        $pubDate = (string)$item->pubDate;
                        $description = isset($item->description) ? strip_tags((string)$item->description) : '';
                        
                        $sentiment = $this->analyzeSentiment($title . ' ' . $description);
                        
                        $news[] = [
                            'title' => $title,
                            'link' => $link,
                            'date' => date('d M Y H:i', strtotime($pubDate)),
                            'source' => $this->extractSource($title),
                            'description' => substr($description, 0, 200),
                            'sentiment' => $sentiment
                        ];
                        
                        $count++;
                    }
                }
            }
        } catch (Exception $e) {
            // Return empty array on error
        }
        
        if (empty($news)) {
            $news = [
                [
                    'title' => 'Cari berita terbaru tentang ' . $cleanTicker,
                    'link' => 'https://www.google.com/search?q=' . urlencode($cleanTicker . ' saham berita'),
                    'date' => date('d M Y H:i'),
                    'source' => 'Google Search',
                    'description' => 'Klik untuk mencari berita terbaru',
                    'sentiment' => 'neutral'
                ]
            ];
        }
        
        return $news;
    }
    
    private function analyzeSentiment($text) {
        $text = strtolower($text);
        
        $positiveWords = ['naik', 'melonjak', 'tumbuh', 'positif', 'untung', 'bagus', 'kuat', 
                          'optimis', 'rebound', 'rally', 'bullish', 'profit', 'dividen',
                          'ekspansi', 'berkembang', 'meningkat', 'surplus', 'boom', 'soar'];
        
        $negativeWords = ['turun', 'anjlok', 'merosot', 'negatif', 'rugi', 'buruk', 'lemah',
                          'pesimis', 'koreksi', 'bearish', 'loss', 'penurunan', 'defisit',
                          'susut', 'jatuh', 'krisis', 'gagal', 'crash', 'plunge'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            if (strpos($text, $word) !== false) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeWords as $word) {
            if (strpos($text, $word) !== false) {
                $negativeCount++;
            }
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
    
    private function extractSource($title) {
        if (preg_match('/- (.+)$/', $title, $matches)) {
            return $matches[1];
        }
        return 'News Source';
    }
}
?>
