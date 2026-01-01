<?php
/**
 * Enhanced Technical Indicators with Advanced Accuracy
 */

// ==================== MOVING AVERAGES ====================

function calculateSMA($data, $period) {
    $sma = [];
    $closes = array_column($data, 'close');
    
    for ($i = 0; $i < count($closes); $i++) {
        if ($i < $period - 1) {
            $sma[] = null;
        } else {
            $sum = array_sum(array_slice($closes, $i - $period + 1, $period));
            $sma[] = $sum / $period;
        }
    }
    
    return $sma;
}

function calculateEMA($data, $period) {
    $ema = [];
    $closes = array_column($data, 'close');
    $multiplier = 2 / ($period + 1);
    
    $sma = array_sum(array_slice($closes, 0, $period)) / $period;
    $ema[0] = $sma;
    
    for ($i = 1; $i < count($closes); $i++) {
        if ($i < $period - 1) {
            $ema[$i] = null;
        } else if ($i == $period - 1) {
            $ema[$i] = $sma;
        } else {
            $ema[$i] = ($closes[$i] - $ema[$i - 1]) * $multiplier + $ema[$i - 1];
        }
    }
    
    return $ema;
}

// ==================== RSI WITH INTERPRETATION ====================

function calculateRSI($data, $period = 14) {
    $closes = array_column($data, 'close');
    $rsi = [];
    
    $gains = [];
    $losses = [];
    
    for ($i = 1; $i < count($closes); $i++) {
        $change = $closes[$i] - $closes[$i - 1];
        $gains[] = $change > 0 ? $change : 0;
        $losses[] = $change < 0 ? abs($change) : 0;
    }
    
    $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
    $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;
    
    for ($i = 0; $i < count($closes); $i++) {
        if ($i < $period) {
            $rsi[] = null;
        } else if ($i == $period) {
            $rs = $avgLoss == 0 ? 100 : $avgGain / $avgLoss;
            $rsi[] = 100 - (100 / (1 + $rs));
        } else {
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i - 1]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i - 1]) / $period;
            $rs = $avgLoss == 0 ? 100 : $avgGain / $avgLoss;
            $rsi[] = 100 - (100 / (1 + $rs));
        }
    }
    
    return $rsi;
}

function interpretRSI($rsi, $prevRSI) {
    $interpretation = [];
    
    if ($rsi > 70) {
        $interpretation['phase'] = 'OVERBOUGHT';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = 'Jangan beli di sini! Harga sudah terlalu tinggi dan berisiko koreksi.';
        $interpretation['detail'] = 'RSI di atas 70 menandakan pasar jenuh beli (overbought). Tekanan jual kemungkinan besar akan muncul. Tunggu koreksi atau pullback sebelum entry.';
    } elseif ($rsi < 30) {
        $interpretation['phase'] = 'OVERSOLD';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '✅ Zona BUY! Harga sudah oversold, potensi rebound tinggi.';
        $interpretation['detail'] = 'RSI di bawah 30 menandakan pasar jenuh jual (oversold). Ini adalah area akumulasi yang baik. Beli bertahap dengan konfirmasi dari indikator lain.';
    } elseif ($rsi > 50 && $prevRSI <= 50) {
        $interpretation['phase'] = 'BULLISH MOMENTUM';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '🚀 Momentum berubah bullish! RSI baru menembus level 50.';
        $interpretation['detail'] = 'RSI menembus level 50 dari bawah mengindikasikan shift momentum dari bearish ke bullish. Ini adalah sinyal early entry yang bagus.';
    } elseif ($rsi > 50) {
        $interpretation['phase'] = 'BULLISH ZONE';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '📈 Momentum bullish masih kuat. Hold atau tambah posisi.';
        $interpretation['detail'] = 'RSI berada di zona 50-70 menunjukkan trend naik yang sehat dengan ruang untuk naik lebih lanjut.';
    } elseif ($rsi < 50 && $prevRSI >= 50) {
        $interpretation['phase'] = 'BEARISH MOMENTUM';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '⚠️ Momentum melemah! RSI turun di bawah 50.';
        $interpretation['detail'] = 'RSI turun di bawah 50 mengindikasikan momentum berubah bearish. Pertimbangkan cut loss atau wait and see.';
    } else {
        $interpretation['phase'] = 'BEARISH ZONE';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '📉 Momentum bearish. Belum ada sinyal beli.';
        $interpretation['detail'] = 'RSI di zona 30-50 menunjukkan tekanan jual masih dominan. Tunggu RSI kembali ke zona bullish.';
    }
    
    return $interpretation;
}

// ==================== STOCHASTIC WITH INTERPRETATION ====================

function calculateStochastic($data, $kPeriod = 14, $dPeriod = 3) {
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
    
    $dValues = [];
    for ($i = 0; $i < count($kValues); $i++) {
        if ($i < $kPeriod + $dPeriod - 2 || is_null($kValues[$i])) {
            $dValues[] = null;
        } else {
            $validK = array_filter(array_slice($kValues, $i - $dPeriod + 1, $dPeriod), function($v) { return !is_null($v); });
            $dValues[] = count($validK) > 0 ? array_sum($validK) / count($validK) : null;
        }
    }
    
    return ['k' => $kValues, 'd' => $dValues];
}

function interpretStochastic($k, $d, $prevK, $prevD) {
    $interpretation = [];
    
    if ($k < 20 && $d < 20) {
        $interpretation['phase'] = 'OVERSOLD ZONE';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '✅ Sangat oversold! Potensi reversal bullish tinggi.';
        $interpretation['detail'] = 'Kedua garis (%K dan %D) berada di bawah 20. Ini adalah zona jenuh jual ekstrim. Tunggu golden cross (K memotong D ke atas) untuk konfirmasi entry.';
    } elseif ($k > $d && $prevK <= $prevD && $k < 30) {
        $interpretation['phase'] = 'GOLDEN CROSS IN OVERSOLD';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '🔥 SINYAL BELI KUAT! Golden cross di zona oversold.';
        $interpretation['detail'] = '%K memotong %D ke atas saat keduanya di zona oversold. Ini adalah sinyal beli paling akurat dari stochastic. Entry sekarang dengan stop loss ketat.';
    } elseif ($k > 80 && $d > 80) {
        $interpretation['phase'] = 'OVERBOUGHT ZONE';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '⚠️ Overbought! Jangan beli, waspadai koreksi.';
        $interpretation['detail'] = 'Kedua garis di atas 80 menandakan jenuh beli. Harga kemungkinan akan koreksi. Jika sudah punya posisi, pertimbangkan take profit.';
    } elseif ($k < $d && $prevK >= $prevD && $k > 70) {
        $interpretation['phase'] = 'DEATH CROSS IN OVERBOUGHT';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '🚨 SINYAL JUAL! Death cross di zona overbought.';
        $interpretation['detail'] = '%K memotong %D ke bawah saat keduanya overbought. Sinyal jual atau take profit. Exit sekarang untuk lock profit.';
    } elseif ($k > $d) {
        $interpretation['phase'] = 'BULLISH ALIGNMENT';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '📈 %K di atas %D, momentum bullish.';
        $interpretation['detail'] = 'Fast line (%K) di atas slow line (%D) menunjukkan momentum naik. Selama alignment ini terjaga, trend bullish masih valid.';
    } else {
        $interpretation['phase'] = 'BEARISH ALIGNMENT';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '📉 %K di bawah %D, momentum bearish.';
        $interpretation['detail'] = '%K di bawah %D menandakan momentum turun. Hindari entry sampai terjadi golden cross.';
    }
    
    return $interpretation;
}

// ==================== MACD WITH INTERPRETATION ====================

function calculateMACD($data, $fastPeriod = 12, $slowPeriod = 26, $signalPeriod = 9) {
    $closes = array_column($data, 'close');
    
    $fastEMA = calculateEMA($data, $fastPeriod);
    $slowEMA = calculateEMA($data, $slowPeriod);
    
    $macdLine = [];
    for ($i = 0; $i < count($closes); $i++) {
        if (is_null($fastEMA[$i]) || is_null($slowEMA[$i])) {
            $macdLine[] = null;
        } else {
            $macdLine[] = $fastEMA[$i] - $slowEMA[$i];
        }
    }
    
    $signalLine = [];
    $validMacd = array_filter($macdLine, function($v) { return !is_null($v); });
    $macdForSignal = array_values($validMacd);
    
    if (count($macdForSignal) >= $signalPeriod) {
        $multiplier = 2 / ($signalPeriod + 1);
        $sma = array_sum(array_slice($macdForSignal, 0, $signalPeriod)) / $signalPeriod;
        
        $signalEMA = [$sma];
        for ($i = 1; $i < count($macdForSignal); $i++) {
            if ($i < $signalPeriod - 1) {
                $signalEMA[] = null;
            } else if ($i == $signalPeriod - 1) {
                $signalEMA[] = $sma;
            } else {
                $signalEMA[] = ($macdForSignal[$i] - $signalEMA[$i - 1]) * $multiplier + $signalEMA[$i - 1];
            }
        }
        
        $j = 0;
        for ($i = 0; $i < count($macdLine); $i++) {
            if (is_null($macdLine[$i])) {
                $signalLine[] = null;
            } else {
                $signalLine[] = $signalEMA[$j] ?? null;
                $j++;
            }
        }
    } else {
        $signalLine = array_fill(0, count($macdLine), null);
    }
    
    $histogram = [];
    for ($i = 0; $i < count($macdLine); $i++) {
        if (is_null($macdLine[$i]) || is_null($signalLine[$i])) {
            $histogram[] = null;
        } else {
            $histogram[] = $macdLine[$i] - $signalLine[$i];
        }
    }
    
    return [
        'macd' => $macdLine,
        'signal' => $signalLine,
        'histogram' => $histogram
    ];
}

function interpretMACD($macd, $signal, $hist, $prevMacd, $prevSignal, $prevHist) {
    $interpretation = [];
    
    if ($macd > $signal && $prevMacd <= $prevSignal && $hist > 0) {
        $interpretation['phase'] = 'BULLISH CROSSOVER';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '🚀 SINYAL BELI! MACD baru crossover signal line ke atas.';
        $interpretation['detail'] = 'MACD line memotong signal line ke atas (bullish crossover). Ini adalah sinyal beli yang powerful. Histogram juga positif mengkonfirmasi momentum bullish. Entry sekarang!';
    } elseif ($macd < $signal && $prevMacd >= $prevSignal && $hist < 0) {
        $interpretation['phase'] = 'BEARISH CROSSOVER';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '🚨 SINYAL JUAL! MACD crossover ke bawah.';
        $interpretation['detail'] = 'MACD line memotong signal line ke bawah (bearish crossover). Momentum berubah bearish. Exit atau cut loss sekarang untuk hindari loss lebih besar.';
    } elseif ($macd > 0 && $signal > 0 && $hist > 0 && $hist > $prevHist) {
        $interpretation['phase'] = 'STRONG BULLISH MOMENTUM';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '💪 Momentum bullish sangat kuat! Histogram melebar.';
        $interpretation['detail'] = 'MACD, signal, dan histogram semuanya positif dengan histogram yang terus membesar. Ini menandakan akselerasi momentum bullish. Hold posisi atau add more.';
    } elseif ($macd > $signal && $hist > 0) {
        $interpretation['phase'] = 'BULLISH TREND';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '📈 Trend bullish masih berlanjut.';
        $interpretation['detail'] = 'MACD di atas signal line dengan histogram positif. Trend naik masih valid. Jika belum entry, masih bisa masuk dengan risk management ketat.';
    } elseif ($macd > 0 && $signal > 0 && $hist > 0 && $hist < $prevHist) {
        $interpretation['phase'] = 'BULLISH DIVERGENCE WARNING';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '⚠️ Momentum melemah! Histogram menyempit.';
        $interpretation['detail'] = 'Meskipun masih bullish, histogram yang menyempit mengindikasikan momentum mulai berkurang. Prepare untuk take profit atau tighten stop loss.';
    } elseif ($macd < 0 && $signal < 0 && $hist < 0 && $hist < $prevHist) {
        $interpretation['phase'] = 'STRONG BEARISH MOMENTUM';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '📉 Momentum bearish kuat! Hindari entry.';
        $interpretation['detail'] = 'Semua komponen MACD negatif dan histogram semakin lebar ke bawah. Downtrend sangat kuat. Jangan buy the dip, tunggu reversal signal.';
    } elseif ($macd < $signal && $hist < 0) {
        $interpretation['phase'] = 'BEARISH TREND';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '🔴 Trend bearish. Belum saatnya beli.';
        $interpretation['detail'] = 'MACD di bawah signal line dengan histogram negatif. Downtrend masih berlanjut. Patience, tunggu bullish crossover.';
    } else {
        $interpretation['phase'] = 'NEUTRAL/RANGING';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '➖ Tidak ada sinyal jelas. Market sideways.';
        $interpretation['detail'] = 'MACD tidak memberikan sinyal yang jelas. Market kemungkinan sedang ranging. Tunggu breakout atau crossover sebelum entry.';
    }
    
    return $interpretation;
}

// ==================== BOLLINGER BANDS ====================

function calculateBollingerBands($data, $period = 20, $stdDev = 2) {
    $closes = array_column($data, 'close');
    $sma = calculateSMA($data, $period);
    
    $upper = [];
    $lower = [];
    $bandwidth = [];
    
    for ($i = 0; $i < count($closes); $i++) {
        if ($i < $period - 1 || is_null($sma[$i])) {
            $upper[] = null;
            $lower[] = null;
            $bandwidth[] = null;
        } else {
            $slice = array_slice($closes, $i - $period + 1, $period);
            $mean = $sma[$i];
            
            $variance = 0;
            foreach ($slice as $value) {
                $variance += pow($value - $mean, 2);
            }
            $std = sqrt($variance / $period);
            
            $upper[] = $mean + ($stdDev * $std);
            $lower[] = $mean - ($stdDev * $std);
            $bandwidth[] = (($upper[$i] - $lower[$i]) / $mean) * 100;
        }
    }
    
    return [
        'upper' => $upper,
        'middle' => $sma,
        'lower' => $lower,
        'bandwidth' => $bandwidth
    ];
}

function interpretBollingerBands($price, $upper, $lower, $middle, $bandwidth, $avgBandwidth) {
    $interpretation = [];
    
    $bbWidth = $upper - $lower;
    $pricePosition = ($price - $lower) / $bbWidth;
    
    // Squeeze detection
    $isSqueeze = $bandwidth < ($avgBandwidth * 0.7);
    
    if ($isSqueeze) {
        $interpretation['phase'] = 'BOLLINGER SQUEEZE';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '⚡ BB Squeeze! Potensi breakout besar segera terjadi!';
        $interpretation['detail'] = 'Band menyempit drastis (bandwidth < 70% dari rata-rata). Ini menandakan volatilitas rendah dan biasanya diikuti oleh pergerakan eksplosif. Siap-siap untuk breakout! Watch volume.';
    } elseif ($pricePosition > 1.0) {
        $interpretation['phase'] = 'ABOVE UPPER BAND';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '🚨 Harga di atas BB Upper! Overbought ekstrim.';
        $interpretation['detail'] = 'Harga menembus dan berada di atas upper band. Ini adalah kondisi overbought yang ekstrim. Kemungkinan besar akan terjadi pullback ke middle band. Jangan beli, pertimbangkan take profit jika sudah punya posisi.';
    } elseif ($pricePosition > 0.8) {
        $interpretation['phase'] = 'NEAR UPPER BAND';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '⚠️ Mendekati BB Upper. Waspadai reversal.';
        $interpretation['detail'] = 'Harga sudah 80% mendekati upper band. Area ini adalah zona resistance psikologis. Jika volume rendah, kemungkinan besar akan reject dan turun ke middle band.';
    } elseif ($pricePosition < 0.0) {
        $interpretation['phase'] = 'BELOW LOWER BAND';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '✅ Harga di bawah BB Lower! Oversold ekstrim - BUY ZONE!';
        $interpretation['detail'] = 'Harga menembus dan berada di bawah lower band. Kondisi oversold yang ekstrim. Historically, harga akan bounce back ke middle band. Ini adalah zona akumulasi yang sangat bagus. Buy sekarang!';
    } elseif ($pricePosition < 0.2) {
        $interpretation['phase'] = 'NEAR LOWER BAND';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '🟢 Mendekati BB Lower. Zona buy yang bagus!';
        $interpretation['detail'] = 'Harga di zona 20% dari lower band. Area ini adalah support kuat. Biasanya harga akan rebound dari sini. Entry bertahap dengan target middle band atau upper band.';
    } elseif ($pricePosition >= 0.4 && $pricePosition <= 0.6) {
        $interpretation['phase'] = 'AT MIDDLE BAND';
        $interpretation['color'] = 'primary';
        $interpretation['action'] = '➖ Harga di middle band. Tunggu breakout direction.';
        $interpretation['detail'] = 'Harga berada di sekitar middle band (SMA20). Ini adalah zona netral. Tunggu harga memilih arah - jika breakout ke atas dengan volume, itu bullish. Jika breakdown, itu bearish.';
    } else {
        $interpretation['phase'] = 'NORMAL RANGE';
        $interpretation['color'] = 'primary';
        $interpretation['action'] = '📊 Harga dalam range normal BB.';
        $interpretation['detail'] = 'Harga bergerak dalam band normal antara middle dan upper/lower band. Market sedang dalam kondisi normal. Gunakan support/resistance untuk timing entry.';
    }
    
    return $interpretation;
}

// Continue with rest of the file...
// ==================== ADX WITH INTERPRETATION ====================

function calculateADX($data, $period = 14) {
    $highs = array_column($data, 'high');
    $lows = array_column($data, 'low');
    $closes = array_column($data, 'close');
    
    $tr = [];
    $plusDM = [];
    $minusDM = [];
    
    for ($i = 1; $i < count($closes); $i++) {
        $tr1 = $highs[$i] - $lows[$i];
        $tr2 = abs($highs[$i] - $closes[$i - 1]);
        $tr3 = abs($lows[$i] - $closes[$i - 1]);
        $tr[] = max($tr1, $tr2, $tr3);
        
        $upMove = $highs[$i] - $highs[$i - 1];
        $downMove = $lows[$i - 1] - $lows[$i];
        
        if ($upMove > $downMove && $upMove > 0) {
            $plusDM[] = $upMove;
        } else {
            $plusDM[] = 0;
        }
        
        if ($downMove > $upMove && $downMove > 0) {
            $minusDM[] = $downMove;
        } else {
            $minusDM[] = 0;
        }
    }
    
    $smoothedTR = [];
    $smoothedPlusDM = [];
    $smoothedMinusDM = [];
    
    $smoothedTR[0] = array_sum(array_slice($tr, 0, $period));
    $smoothedPlusDM[0] = array_sum(array_slice($plusDM, 0, $period));
    $smoothedMinusDM[0] = array_sum(array_slice($minusDM, 0, $period));
    
    for ($i = 1; $i < count($tr); $i++) {
        if ($i < $period - 1) {
            $smoothedTR[] = null;
            $smoothedPlusDM[] = null;
            $smoothedMinusDM[] = null;
        } else {
            $smoothedTR[] = $smoothedTR[$i - 1] - ($smoothedTR[$i - 1] / $period) + $tr[$i];
            $smoothedPlusDM[] = $smoothedPlusDM[$i - 1] - ($smoothedPlusDM[$i - 1] / $period) + $plusDM[$i];
            $smoothedMinusDM[] = $smoothedMinusDM[$i - 1] - ($smoothedMinusDM[$i - 1] / $period) + $minusDM[$i];
        }
    }
    
    $plusDI = [];
    $minusDI = [];
    $dx = [];
    
    for ($i = 0; $i < count($smoothedTR); $i++) {
        if (is_null($smoothedTR[$i]) || $smoothedTR[$i] == 0) {
            $plusDI[] = null;
            $minusDI[] = null;
            $dx[] = null;
        } else {
            $plusDI[] = 100 * ($smoothedPlusDM[$i] / $smoothedTR[$i]);
            $minusDI[] = 100 * ($smoothedMinusDM[$i] / $smoothedTR[$i]);
            
            $diSum = $plusDI[$i] + $minusDI[$i];
            if ($diSum == 0) {
                $dx[] = 0;
            } else {
                $dx[] = 100 * abs($plusDI[$i] - $minusDI[$i]) / $diSum;
            }
        }
    }
    
    $adx = [];
    $validDX = array_filter($dx, function($v) { return !is_null($v); });
    
    if (count($validDX) >= $period) {
        $adxValues = array_values($validDX);
        $adx[0] = array_sum(array_slice($adxValues, 0, $period)) / $period;
        
        for ($i = 1; $i < count($adxValues); $i++) {
            if ($i < $period - 1) {
                $adx[] = null;
            } else {
                $adx[] = (($adx[$i - 1] * ($period - 1)) + $adxValues[$i]) / $period;
            }
        }
    }
    
    return end($adx) ?? 0;
}

function interpretADX($adx) {
    $interpretation = [];
    
    if ($adx > 50) {
        $interpretation['phase'] = 'VERY STRONG TREND';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '🔥 Trend SANGAT kuat! Ride the trend!';
        $interpretation['detail'] = 'ADX di atas 50 menandakan trend yang sangat kuat dan dominan. Ini adalah kondisi ideal untuk trend following. Jangan melawan trend - ikuti saja. Entry dengan pullback kecil.';
    } elseif ($adx > 25) {
        $interpretation['phase'] = 'STRONG TREND';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '✅ Trend kuat terkonfirmasi. Aman untuk entry.';
        $interpretation['detail'] = 'ADX di atas 25 menunjukkan ada trend yang jelas dan kuat. Entah bullish atau bearish, tapi yang pasti ada directional movement. Gunakan indikator lain untuk tentukan arah, lalu ikuti trend tersebut.';
    } elseif ($adx > 20) {
        $interpretation['phase'] = 'MODERATE TREND';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '⚠️ Trend mulai terbentuk, tapi masih lemah.';
        $interpretation['detail'] = 'ADX di 20-25 menandakan trend yang baru mulai terbentuk atau trend yang melemah. Perlu konfirmasi lebih lanjut. Wait untuk ADX naik di atas 25 atau cari setup lain.';
    } else {
        $interpretation['phase'] = 'NO TREND / SIDEWAYS';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '🚫 Tidak ada trend! Market sideways. JANGAN TRADE!';
        $interpretation['detail'] = 'ADX di bawah 20 menandakan tidak ada trend yang jelas. Market sedang ranging/sideways. Trend following strategy TIDAK akan work. Hindari trading atau gunakan range trading strategy dengan support/resistance.';
    }
    
    return $interpretation;
}

// ==================== VOLUME ANALYSIS ====================

function interpretVolume($currentVol, $avgVol, $priceChange) {
    $interpretation = [];
    $volRatio = $currentVol / $avgVol;
    
    if ($volRatio > 2.0 && $priceChange > 0) {
        $interpretation['phase'] = 'VOLUME BREAKOUT BULLISH';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '🚀 Volume spike dengan harga naik! Breakout valid!';
        $interpretation['detail'] = 'Volume lebih dari 2x rata-rata dengan harga naik. Ini adalah tanda breakout yang valid dengan partisipasi institusional. Entry sekarang, momentum sangat kuat!';
    } elseif ($volRatio > 2.0 && $priceChange < 0) {
        $interpretation['phase'] = 'VOLUME SPIKE BEARISH';
        $interpretation['color'] = 'danger';
        $interpretation['action'] = '🚨 Volume spike dengan harga turun! Panic selling!';
        $interpretation['detail'] = 'Volume tinggi dengan harga turun menandakan panic selling atau distribusi besar. Hindari buy the dip. Tunggu volume kembali normal dan harga stabilize.';
    } elseif ($volRatio > 1.5 && $priceChange > 0) {
        $interpretation['phase'] = 'HEALTHY BULLISH VOLUME';
        $interpretation['color'] = 'success';
        $interpretation['action'] = '📈 Volume meningkat sehat dengan harga naik.';
        $interpretation['detail'] = 'Volume di atas rata-rata (1.5x) mendukung kenaikan harga. Ini adalah konfirmasi bahwa uptrend didukung oleh partisipasi trader yang kuat. Good for entry.';
    } elseif ($volRatio < 0.7) {
        $interpretation['phase'] = 'LOW VOLUME';
        $interpretation['color'] = 'warning';
        $interpretation['action'] = '⚠️ Volume rendah. Pergerakan tidak valid.';
        $interpretation['detail'] = 'Volume di bawah 70% rata-rata menandakan kurangnya partisipasi. Pergerakan harga dengan volume rendah biasanya tidak sustainable. Tunggu volume meningkat sebelum entry.';
    } else {
        $interpretation['phase'] = 'NORMAL VOLUME';
        $interpretation['color'] = 'primary';
        $interpretation['action'] = '📊 Volume normal, sesuai rata-rata.';
        $interpretation['detail'] = 'Volume dalam range normal. Pergerakan harga masih valid tapi tidak ada konfirmasi kuat. Gunakan indikator lain untuk validasi entry.';
    }
    
    return $interpretation;
}

// ==================== SUPPORT & RESISTANCE ====================

function findSupportResistance($data, $lookback = 100, $threshold = 0.015) {
    $highs = array_column($data, 'high');
    $lows = array_column($data, 'low');
    
    $recentData = array_slice($data, -$lookback);
    $recentHighs = array_slice($highs, -$lookback);
    $recentLows = array_slice($lows, -$lookback);
    
    $resistanceLevels = [];
    $supportLevels = [];
    
    for ($i = 3; $i < count($recentHighs) - 3; $i++) {
        if ($recentHighs[$i] >= $recentHighs[$i-1] && 
            $recentHighs[$i] >= $recentHighs[$i-2] &&
            $recentHighs[$i] >= $recentHighs[$i-3] &&
            $recentHighs[$i] >= $recentHighs[$i+1] && 
            $recentHighs[$i] >= $recentHighs[$i+2] &&
            $recentHighs[$i] >= $recentHighs[$i+3]) {
            $resistanceLevels[] = $recentHighs[$i];
        }
        
        if ($recentLows[$i] <= $recentLows[$i-1] && 
            $recentLows[$i] <= $recentLows[$i-2] &&
            $recentLows[$i] <= $recentLows[$i-3] &&
            $recentLows[$i] <= $recentLows[$i+1] && 
            $recentLows[$i] <= $recentLows[$i+2] &&
            $recentLows[$i] <= $recentLows[$i+3]) {
            $supportLevels[] = $recentLows[$i];
        }
    }
    
    $resistances = clusterLevels($resistanceLevels, $threshold);
    $supports = clusterLevels($supportLevels, $threshold);
    
    rsort($resistances);
    sort($supports);
    
    return [
        'resistances' => array_slice($resistances, 0, 3),
        'supports' => array_slice(array_reverse($supports), 0, 3)
    ];
}

function clusterLevels($levels, $threshold) {
    if (empty($levels)) return [];
    
    sort($levels);
    $clustered = [];
    $currentCluster = [$levels[0]];
    
    for ($i = 1; $i < count($levels); $i++) {
        $diff = abs($levels[$i] - $levels[$i-1]) / $levels[$i-1];
        
        if ($diff <= $threshold) {
            $currentCluster[] = $levels[$i];
        } else {
            $clustered[] = array_sum($currentCluster) / count($currentCluster);
            $currentCluster = [$levels[$i]];
        }
    }
    
    if (!empty($currentCluster)) {
        $clustered[] = array_sum($currentCluster) / count($currentCluster);
    }
    
    return $clustered;
}

// ==================== CANDLESTICK PATTERNS ====================

function detectCandlestickPatterns($data, $lookback = 10) {
    $patterns = [];
    $recentData = array_slice($data, -$lookback);
    
    foreach ($recentData as $idx => $candle) {
        $open = $candle['open'];
        $close = $candle['close'];
        $high = $candle['high'];
        $low = $candle['low'];
        $body = abs($close - $open);
        $range = $high - $low;
        
        if ($range == 0) continue;
        
        if ($idx > 0) {
            $prev = $recentData[$idx - 1];
            if ($prev['close'] < $prev['open'] &&
                $close > $open &&
                $open < $prev['close'] &&
                $close > $prev['open']) {
                
                $patterns[] = [
                    'name' => 'Bullish Engulfing',
                    'type' => 'bullish',
                    'date' => $candle['date'],
                    'strength' => 'strong'
                ];
            }
        }
        
        $lowerShadow = min($open, $close) - $low;
        $upperShadow = $high - max($open, $close);
        
        if ($lowerShadow > $body * 2 && $upperShadow < $body * 0.3) {
            $patterns[] = [
                'name' => 'Hammer',
                'type' => 'bullish',
                'date' => $candle['date'],
                'strength' => 'medium'
            ];
        }
        
        if ($idx >= 2) {
            $c1 = $recentData[$idx - 2];
            $c2 = $recentData[$idx - 1];
            $c3 = $candle;
            
            $c1Body = abs($c1['close'] - $c1['open']);
            $c2Body = abs($c2['close'] - $c2['open']);
            
            if ($c1['close'] < $c1['open'] &&
                $c2Body < $c1Body * 0.3 &&
                $c3['close'] > $c3['open'] &&
                $c3['close'] > ($c1['open'] + $c1['close']) / 2) {
                
                $patterns[] = [
                    'name' => 'Morning Star',
                    'type' => 'bullish',
                    'date' => $candle['date'],
                    'strength' => 'strong'
                ];
            }
        }
    }
    
    return $patterns;
}

// ==================== MAIN ANALYSIS FUNCTION ====================

function analyzeStockPro($data, $capital, $riskPercent) {
    $latestIdx = count($data) - 1;
    $currentPrice = $data[$latestIdx]['close'];
    
    $ma20 = calculateSMA($data, 20);
    $ma50 = calculateSMA($data, 50);
    $ma200 = calculateSMA($data, 200);
    $ema9 = calculateEMA($data, 9);
    $ema21 = calculateEMA($data, 21);
    $rsi = calculateRSI($data, 14);
    $stoch = calculateStochastic($data, 14, 3);
    $macd = calculateMACD($data, 12, 26, 9);
    $bb = calculateBollingerBands($data, 20, 2);
    $adx = calculateADX($data, 14);
    
    $srLevels = findSupportResistance($data, 120, 0.015);
    
    $volumes = array_slice(array_column($data, 'volume'), -20);
    $volumeAvg = array_sum($volumes) / count($volumes);
    $currentVolume = $data[$latestIdx]['volume'];
    $volumeSpike = $currentVolume > ($volumeAvg * 1.8);
    
    $patterns = detectCandlestickPatterns($data, 10);
    
    // Calculate average bandwidth for BB
    $avgBandwidth = 0;
    $count = 0;
    for ($i = $latestIdx - 20; $i < $latestIdx; $i++) {
        if (isset($bb['bandwidth'][$i]) && !is_null($bb['bandwidth'][$i])) {
            $avgBandwidth += $bb['bandwidth'][$i];
            $count++;
        }
    }
    $avgBandwidth = $count > 0 ? $avgBandwidth / $count : 0;
    
    // Get interpretations
    $rsiInterp = interpretRSI($rsi[$latestIdx], $rsi[$latestIdx - 1] ?? 50);
    $stochInterp = interpretStochastic($stoch['k'][$latestIdx], $stoch['d'][$latestIdx], 
                                       $stoch['k'][$latestIdx - 1] ?? 50, $stoch['d'][$latestIdx - 1] ?? 50);
    $macdInterp = interpretMACD($macd['macd'][$latestIdx], $macd['signal'][$latestIdx], $macd['histogram'][$latestIdx],
                                $macd['macd'][$latestIdx - 1] ?? 0, $macd['signal'][$latestIdx - 1] ?? 0, $macd['histogram'][$latestIdx - 1] ?? 0);
    $bbInterp = interpretBollingerBands($currentPrice, $bb['upper'][$latestIdx], $bb['lower'][$latestIdx], 
                                        $bb['middle'][$latestIdx], $bb['bandwidth'][$latestIdx] ?? 0, $avgBandwidth);
    $adxInterp = interpretADX($adx);
    $priceChange = $currentPrice - $data[$latestIdx - 1]['close'];
    $volInterp = interpretVolume($currentVolume, $volumeAvg, $priceChange);
    
    // Trend determination
    $trend = 'SIDEWAYS';
    if ($currentPrice > $ma20[$latestIdx] && $currentPrice > $ma50[$latestIdx] && 
        $ma20[$latestIdx] > $ma50[$latestIdx]) {
        $trend = 'BULLISH';
    } elseif ($currentPrice < $ma20[$latestIdx] && $currentPrice < $ma50[$latestIdx] &&
              $ma20[$latestIdx] < $ma50[$latestIdx]) {
        $trend = 'BEARISH';
    }
    
    // Enhanced checklist
    $checklist = [
        [
            'name' => 'Harga di atas MA20 & MA50',
            'passed' => $currentPrice > $ma20[$latestIdx] && $currentPrice > $ma50[$latestIdx],
            'detail' => 'Harga: Rp '.number_format($currentPrice, 0, ',', '.').', MA20: Rp '.number_format($ma20[$latestIdx], 0, ',', '.').', MA50: Rp '.number_format($ma50[$latestIdx], 0, ',', '.')
        ],
        [
            'name' => 'MA20 > MA50 (Golden Cross)',
            'passed' => $ma20[$latestIdx] > $ma50[$latestIdx],
            'detail' => $ma20[$latestIdx] > $ma50[$latestIdx] ? '✅ Golden cross confirmed - trend bullish kuat' : '❌ MA20 masih di bawah MA50'
        ],
        [
            'name' => 'RSI dalam zona bullish',
            'passed' => $rsi[$latestIdx] > 50 && $rsi[$latestIdx] < 70,
            'detail' => 'RSI: '.number_format($rsi[$latestIdx], 2).' - '.$rsiInterp['action']
        ],
        [
            'name' => 'Stochastic bullish',
            'passed' => $stoch['k'][$latestIdx] > $stoch['d'][$latestIdx] && $stoch['k'][$latestIdx] < 80,
            'detail' => '%K: '.number_format($stoch['k'][$latestIdx], 2).', %D: '.number_format($stoch['d'][$latestIdx], 2).' - '.$stochInterp['action']
        ],
        [
            'name' => 'MACD bullish crossover/alignment',
            'passed' => $macd['macd'][$latestIdx] > $macd['signal'][$latestIdx] && $macd['histogram'][$latestIdx] > 0,
            'detail' => 'Histogram: '.number_format($macd['histogram'][$latestIdx], 4).' - '.$macdInterp['action']
        ],
        [
            'name' => 'ADX menunjukkan trend kuat',
            'passed' => $adx > 20,
            'detail' => 'ADX: '.number_format($adx, 2).' - '.$adxInterp['action']
        ],
        [
            'name' => 'Bollinger Bands mendukung',
            'passed' => $bbInterp['phase'] !== 'ABOVE UPPER BAND',
            'detail' => $bbInterp['action']
        ],
        [
            'name' => 'Volume mendukung pergerakan',
            'passed' => $volumeSpike || $currentVolume > $volumeAvg,
            'detail' => $volInterp['action'].' - Current: '.number_format($currentVolume, 0, ',', '.').', Avg: '.number_format($volumeAvg, 0, ',', '.')
        ],
        [
            'name' => 'Support kuat teridentifikasi',
            'passed' => count($srLevels['supports']) >= 2,
            'detail' => count($srLevels['supports']).' level support ditemukan di: Rp '.number_format($srLevels['supports'][0] ?? 0, 0, ',', '.')
        ],
        [
            'name' => 'Pola candlestick bullish',
            'passed' => count($patterns) > 0,
            'detail' => count($patterns) > 0 ? '✅ '.$patterns[0]['name'].' detected' : '❌ Tidak ada pola bullish'
        ]
    ];
    
    $score = count(array_filter($checklist, function($item) { return $item['passed']; }));
    
    // Recommendation
    $scorePercent = ($score / count($checklist)) * 100;
    if ($scorePercent >= 75) {
        $recommendation = 'STRONG BUY';
    } elseif ($scorePercent >= 60) {
        $recommendation = 'BUY';
    } elseif ($scorePercent >= 45) {
        $recommendation = 'PERTIMBANGKAN';
    } else {
        $recommendation = 'HINDARI';
    }
    
    $conservative = calculateTradeStrategy($data, $srLevels, $currentPrice, $capital, $riskPercent, 'conservative');
    $aggressive = calculateTradeStrategy($data, $srLevels, $currentPrice, $capital, $riskPercent, 'aggressive');
    
    $timeline = [
        'day1' => 'Monitor entry zone. Tunggu konfirmasi volume dan pola candle bullish.',
        'day3' => 'Evaluasi pergerakan harga. Jika sudah profit 3-5%, consider take partial profit 30%.',
        'day6' => 'Target TP1 harus tercapai. Trailing stop untuk sisa posisi menuju TP2.'
    ];
    
    $narrative = generateAdvancedNarrative($data, $trend, $rsi[$latestIdx], $volumeSpike, $patterns, 
                                          $srLevels, $score, $adx, count($checklist), $rsiInterp, $stochInterp, 
                                          $macdInterp, $bbInterp, $adxInterp, $volInterp);
    
    $chartData = prepareChartData($data, $ma20, $ma50, $ema9, $bb, $rsi, $macd, $stoch, $volumes);
    
    return [
        'trend' => $trend,
        'recommendation' => $recommendation,
        'rsi' => $rsi[$latestIdx],
        'rsi_interp' => $rsiInterp,
        'stoch_k' => $stoch['k'][$latestIdx],
        'stoch_d' => $stoch['d'][$latestIdx],
        'stoch_interp' => $stochInterp,
        'macd' => $macd['macd'][$latestIdx],
        'macd_signal' => $macd['signal'][$latestIdx],
        'macd_hist' => $macd['histogram'][$latestIdx],
        'macd_interp' => $macdInterp,
        'macd_cross' => $macd['macd'][$latestIdx] > $macd['signal'][$latestIdx] && $macd['histogram'][$latestIdx] > 0,
        'volume_avg' => $volumeAvg,
        'volume_spike' => $volumeSpike,
        'volume_interp' => $volInterp,
        'bb_position' => $bbInterp['phase'],
        'bb_signal' => $bbInterp['action'],
        'bb_interp' => $bbInterp,
        'adx' => $adx,
        'adx_interp' => $adxInterp,
        'supports' => $srLevels['supports'],
        'resistances' => $srLevels['resistances'],
        'patterns' => $patterns,
        'conservative' => $conservative,
        'aggressive' => $aggressive,
        'timeline' => $timeline,
        'checklist' => $checklist,
        'score' => $score,
        'narrative' => $narrative,
        'chart_data' => $chartData
    ];
}

function calculateTradeStrategy($data, $srLevels, $currentPrice, $capital, $riskPercent, $type) {
    // Ensure we have supports and resistances
    $nearestSupport = !empty($srLevels['supports']) ? $srLevels['supports'][0] : $currentPrice * 0.95;
    $nearestResistance = !empty($srLevels['resistances']) ? end($srLevels['resistances']) : $currentPrice * 1.05;
    $strongResistance = !empty($srLevels['resistances']) ? $srLevels['resistances'][0] : $currentPrice * 1.10;
    
    if ($type === 'conservative') {
        // Conservative: Entry near support, safer approach
        $entryMin = $nearestSupport * 1.005; // 0.5% above support
        $entryMax = $nearestSupport * 1.02;  // 2% above support
        $stopLoss = $nearestSupport * 0.97;  // 3% below support
        
        // TP1 must be ABOVE entry - minimum 3% profit
        $tp1 = max($nearestResistance, $currentPrice * 1.03, ($entryMin + $entryMax) / 2 * 1.03);
        
        // TP2 must be ABOVE TP1 - target strong resistance or 6%+ profit
        $tp2 = max($strongResistance, $tp1 * 1.03, $currentPrice * 1.06);
        
    } else {
        // Aggressive: Entry closer to current price, higher risk
        $entryMin = $currentPrice * 0.998;   // Very close to current
        $entryMax = $currentPrice * 1.015;   // 1.5% above current
        $stopLoss = $nearestSupport * 0.975; // Tighter stop
        
        // TP1 must be ABOVE entry - minimum 4% profit
        $tp1 = max($nearestResistance, $currentPrice * 1.04, ($entryMin + $entryMax) / 2 * 1.04);
        
        // TP2 must be ABOVE TP1 - target strong resistance or 8%+ profit  
        $tp2 = max($strongResistance, $tp1 * 1.04, $currentPrice * 1.08);
    }
    
    // Calculate average entry
    $avgEntry = ($entryMin + $entryMax) / 2;
    
    // VALIDATE: Ensure TP1 and TP2 are ABOVE entry
    if ($tp1 <= $avgEntry) {
        $tp1 = $avgEntry * 1.035; // Force minimum 3.5% profit
    }
    
    if ($tp2 <= $tp1) {
        $tp2 = $tp1 * 1.03; // Force minimum 3% above TP1
    }
    
    // Calculate risk and rewards
    $risk = $avgEntry - $stopLoss;
    $reward1 = $tp1 - $avgEntry;
    $reward2 = $tp2 - $avgEntry;
    
    // Calculate Risk:Reward ratios
    $rr1 = $risk > 0 ? $reward1 / $risk : 0;
    $rr2 = $risk > 0 ? $reward2 / $risk : 0;
    
    // Position sizing
    $maxRisk = $capital * ($riskPercent / 100);
    $lotSize = 100; // 1 lot = 100 shares
    $lots = $risk > 0 ? floor($maxRisk / ($risk * $lotSize)) : 0;
    
    // Ensure minimum lot size
    if ($lots < 1) {
        $lots = 1;
    }
    
    // Cap maximum lots for safety
    if ($lots > 50) {
        $lots = 50;
    }
    
    return [
        'entry_min' => round($entryMin, 0),
        'entry_max' => round($entryMax, 0),
        'stop_loss' => round($stopLoss, 0),
        'tp1' => round($tp1, 0),
        'tp2' => round($tp2, 0),
        'rr1' => round($rr1, 2),
        'rr2' => round($rr2, 2),
        'lots' => $lots,
        'potential_profit_tp1' => round($reward1 * $lots * $lotSize, 0),
        'potential_profit_tp2' => round($reward2 * $lots * $lotSize, 0),
        'max_loss' => round($risk * $lots * $lotSize, 0)
    ];
}


function generateAdvancedNarrative($data, $trend, $rsi, $volumeSpike, $patterns, $srLevels, $score, 
                                   $adx, $totalChecks, $rsiInterp, $stochInterp, $macdInterp, $bbInterp, $adxInterp, $volInterp) {
    $latestPrice = end($data)['close'];
    $scorePercent = round(($score / $totalChecks) * 100);
    
    $narrative = "<p><strong>Status Trend:</strong> Saham saat ini berada dalam trend <strong>{$trend}</strong>. ";
    $narrative .= "Skor teknikal menunjukkan <strong>{$scorePercent}%</strong> dari {$totalChecks} indikator mendukung pergerakan bullish.</p>";
    
    $narrative .= "<p><strong>Analisis ADX:</strong> {$adxInterp['detail']}</p>";
    
    $narrative .= "<p><strong>Analisis RSI:</strong> {$rsiInterp['detail']}</p>";
    
    $narrative .= "<p><strong>Analisis Stochastic:</strong> {$stochInterp['detail']}</p>";
    
    $narrative .= "<p><strong>Analisis MACD:</strong> {$macdInterp['detail']}</p>";
    
    $narrative .= "<p><strong>Analisis Bollinger Bands:</strong> {$bbInterp['detail']}</p>";
    
    $narrative .= "<p><strong>Analisis Volume:</strong> {$volInterp['detail']}</p>";
    
    if (!empty($patterns)) {
        $narrative .= "<p><strong>Pola Candlestick:</strong> Terdeteksi pola <strong>{$patterns[0]['name']}</strong> yang merupakan sinyal bullish {$patterns[0]['strength']}. ";
        $narrative .= "Pola ini mengkonfirmasi potensi reversal atau kelanjutan trend bullish.</p>";
    }
    
    if (!empty($srLevels['supports']) && !empty($srLevels['resistances'])) {
        $nearest_support = $srLevels['supports'][0];
        $nearest_resistance = end($srLevels['resistances']);
        $supportDistance = (($latestPrice - $nearest_support) / $latestPrice) * 100;
        $resistanceDistance = (($nearest_resistance - $latestPrice) / $latestPrice) * 100;
        
        $narrative .= "<p><strong>Support & Resistance:</strong> Support terdekat di Rp ".number_format($nearest_support, 0, ',', '.')." (jarak ".round($supportDistance, 2)."% dari harga saat ini). ";
        $narrative .= "Resistance terdekat di Rp ".number_format($nearest_resistance, 0, ',', '.')." (target ".round($resistanceDistance, 2)."% dari harga saat ini).</p>";
    }
    
    $narrative .= "<p><strong>Kesimpulan & Rekomendasi:</strong> ";
    if ($scorePercent >= 75) {
        $narrative .= "Setup trading SANGAT KUAT dengan probabilitas success tinggi. Entry sekarang dengan strict stop loss dan target profit bertahap. ";
        $narrative .= "Gunakan strategi conservative untuk risk management optimal.";
    } elseif ($scorePercent >= 60) {
        $narrative .= "Setup trading CUKUP BAGUS. Entry bisa dilakukan dengan position sizing lebih kecil (50-70% dari rencana). ";
        $narrative .= "Tunggu konfirmasi tambahan dari volume atau breakout resistance.";
    } elseif ($scorePercent >= 45) {
        $narrative .= "Setup MARGINAL. Lebih baik wait and see untuk konfirmasi lebih lanjut. ";
        $narrative .= "Jika tetap ingin entry, gunakan position sizing sangat kecil (30% max) dengan stop loss ketat.";
    } else {
        $narrative .= "Setup LEMAH. TIDAK DIREKOMENDASIKAN untuk entry saat ini. ";
        $narrative .= "Tunggu setup yang lebih baik atau cari saham lain dengan skor teknikal lebih tinggi.";
    }
    $narrative .= "</p>";
    
    return $narrative;
}

function prepareChartData($data, $ma20, $ma50, $ema9, $bb, $rsi, $macd, $stoch, $volumes) {
    $dates = array_column($data, 'date');
    $prices = array_map(function($item) {
        return [
            'o' => $item['open'],
            'h' => $item['high'],
            'l' => $item['low'],
            'c' => $item['close']
        ];
    }, $data);
    
    return [
        'dates' => $dates,
        'prices' => $prices,
        'ma20' => $ma20,
        'ma50' => $ma50,
        'ema9' => $ema9,
        'bb_upper' => $bb['upper'],
        'bb_middle' => $bb['middle'],
        'bb_lower' => $bb['lower'],
        'rsi' => $rsi,
        'macd' => $macd['macd'],
        'macd_signal' => $macd['signal'],
        'macd_hist' => $macd['histogram'],
        'stoch_k' => $stoch['k'],
        'stoch_d' => $stoch['d'],
        'volume' => array_column($data, 'volume')
    ];
}
?>
