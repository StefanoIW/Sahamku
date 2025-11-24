<?php
require_once 'config.php';

$analysis = null;
$error = null;
$analyzer = null;

if (isset($_POST['ticker'])) {
    $ticker = strtoupper(trim($_POST['ticker']));
    
    if (!strpos($ticker, '.JK') && !strpos($ticker, '^')) {
        $ticker .= '.JK';
    }
    
    try {
        $analyzer = new StockAnalyzer($ticker);
        $analyzer->fetchData('6mo', '1d');
        $analysis = $analyzer->generateTradingSignal();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IHSG Professional Stock Analyzer</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial@0.2.0/dist/chartjs-chart-financial.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 IHSG Professional Stock Analyzer</h1>
            <p>Advanced Technical Analysis dengan Real-time Charts & Trading Signals</p>
        </header>
        
        <div class="search-box">
            <form method="POST" action="">
                <input type="text" name="ticker" placeholder="Masukkan Kode Saham (BBCA, BBRI, TLKM, ASII, dll)" required value="<?php echo isset($_POST['ticker']) ? htmlspecialchars($_POST['ticker']) : ''; ?>">
                <button type="submit">🔍 Analisis Saham</button>
            </form>
            <p class="hint">*Otomatis menambahkan .JK untuk saham Indonesia</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                ⚠️ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($analysis && $analyzer): ?>
            <?php 
            $actionMsg = $analyzer->generateActionMessage($analysis);
            $simpleExplanation = $analyzer->getSimpleExplanation($analysis);
            $news = $analyzer->fetchStockNews($analysis['ticker']);
            ?>
            
            <div class="result-container">
                <!-- Header Info -->
                <div class="header-info">
                    <div>
                        <h2><?php echo $analysis['ticker']; ?></h2>
                        <p class="update-time">Last Update: <?php echo date('d M Y H:i:s'); ?> WIB</p>
                    </div>
                    <div class="current-price">
                        <span class="label">Harga Sekarang</span>
                        <span class="price">Rp <?php echo number_format($analysis['current_price'], 2); ?></span>
                        <span class="volume-info">Volume: <?php echo number_format($analysis['volume']); ?></span>
                    </div>
                </div>

                <!-- Recommendation -->
                <div class="recommendation-box <?php echo strtolower(str_replace(' ', '-', str_replace('/', '-', $analysis['recommendation']))); ?>">
                    <h3>🎯 <?php echo $analysis['recommendation']; ?></h3>
                    <p class="score">Confidence Score: <?php echo $analysis['score']; ?>/10</p>
                </div>

                <!-- Simple Explanation Section -->
                <div class="simple-explanation-section">
                    <h2>💬 Penjelasan Untuk Pemula (Bahasa Sederhana)</h2>
                    
                    <div class="explanation-card main-condition">
                        <h3>Kondisi Saham Saat Ini</h3>
                        <p class="big-text"><?php echo $simpleExplanation['condition']; ?></p>
                        <p class="analogy-text">💡 <?php echo $simpleExplanation['analogy']; ?></p>
                    </div>

                    <div class="explanation-grid">
                        <div class="explanation-card">
                            <h4>📊 Apa itu RSI?</h4>
                            <p><?php echo $simpleExplanation['rsi']; ?></p>
                        </div>

                        <div class="explanation-card">
                            <h4>🚗 Apa itu MACD?</h4>
                            <p><?php echo $simpleExplanation['macd']; ?></p>
                        </div>

                        <div class="explanation-card full-width">
                            <h4>🏢 Apa itu Support & Resistance?</h4>
                            <p><?php echo nl2br($simpleExplanation['support_resistance']); ?></p>
                        </div>
                    </div>

                    <div class="explanation-card action-explanation">
                        <h3>❓ Jadi Saya Harus Apa?</h3>
                        <p class="action-text"><?php echo nl2br($simpleExplanation['action_simple']); ?></p>
                    </div>

                    <div class="explanation-card risk-explanation">
                        <h3>💰 Untung Rugi Berapa?</h3>
                        <p><?php echo nl2br($simpleExplanation['risk_reward']); ?></p>
                    </div>
                </div>

                <!-- News Section -->
                <?php if (count($news) > 0): ?>
                <div class="news-section">
                    <h2>📰 Berita Terbaru & Terhangat yang Mempengaruhi Harga</h2>
                    <p class="news-subtitle">Berita-berita ini bisa mempengaruhi harga saham ke depannya</p>
                    
                    <div class="news-grid">
                        <?php foreach ($news as $item): ?>
                        <div class="news-card <?php echo $item['sentiment']; ?>">
                            <div class="news-header">
                                <span class="news-sentiment-badge <?php echo $item['sentiment']; ?>">
                                    <?php 
                                    if ($item['sentiment'] == 'positive') echo '🟢 Berita Positif';
                                    elseif ($item['sentiment'] == 'negative') echo '🔴 Berita Negatif';
                                    else echo '🟡 Berita Netral';
                                    ?>
                                </span>
                                <span class="news-date"><?php echo $item['date']; ?></span>
                            </div>
                            
                            <h4 class="news-title">
                                <a href="<?php echo $item['link']; ?>" target="_blank">
                                    <?php echo $item['title']; ?>
                                </a>
                            </h4>
                            
                            <?php if ($item['description']): ?>
                            <p class="news-description"><?php echo $item['description']; ?>...</p>
                            <?php endif; ?>
                            
                            <div class="news-footer">
                                <span class="news-source">📡 <?php echo $item['source']; ?></span>
                                <a href="<?php echo $item['link']; ?>" target="_blank" class="news-read-more">Baca Selengkapnya →</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="news-impact-explanation">
                        <h3>💡 Cara Membaca Berita untuk Trading</h3>
                        <ul>
                            <li><strong>🟢 Berita Positif</strong> = Kemungkinan harga NAIK (bagus untuk beli)</li>
                            <li><strong>🔴 Berita Negatif</strong> = Kemungkinan harga TURUN (hati-hati atau jual)</li>
                            <li><strong>🟡 Berita Netral</strong> = Tidak terlalu berpengaruh ke harga</li>
                        </ul>
                        <p class="warning-text">⚠️ Perhatian: Berita bisa berubah cepat. Selalu cek berita terbaru sebelum membeli/menjual saham!</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status & Action Alert Section -->
                <div class="status-alert-section">
                    <div class="status-card <?php echo $actionMsg['alert_type']; ?>">
                        <div class="status-icon">
                            <?php 
                            if ($actionMsg['alert_type'] == 'strong-bullish') echo '🚀';
                            elseif ($actionMsg['alert_type'] == 'bullish') echo '📈';
                            elseif ($actionMsg['alert_type'] == 'neutral') echo '⏸️';
                            elseif ($actionMsg['alert_type'] == 'bearish') echo '📉';
                            else echo '⚠️';
                            ?>
                        </div>
                        <div class="status-content">
                            <h3>Status Saham Saat Ini</h3>
                            <p class="status-text"><?php echo $actionMsg['status']; ?></p>
                            <span class="risk-badge"><?php echo $actionMsg['risk_level']; ?></span>
                        </div>
                    </div>

                    <div class="action-card">
                        <h3>📢 Rekomendasi Action</h3>
                        <div class="action-message">
                            <?php echo nl2br($actionMsg['action']); ?>
                        </div>
                    </div>

                    <div class="next-steps-card">
                        <h3>📋 Langkah Selanjutnya</h3>
                        <div class="steps-content">
                            <?php echo nl2br($actionMsg['next_step']); ?>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-container">
                        <h3>📈 Candlestick Chart with Moving Averages</h3>
                        <canvas id="candlestickChart"></canvas>
                    </div>
                    
                    <div class="chart-row">
                        <div class="chart-container half">
                            <h3>📊 Volume Analysis</h3>
                            <canvas id="volumeChart"></canvas>
                        </div>
                        <div class="chart-container half">
                            <h3>📉 MACD Indicator</h3>
                            <canvas id="macdChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-row">
                        <div class="chart-container half">
                            <h3>🎚️ RSI (14)</h3>
                            <canvas id="rsiChart"></canvas>
                        </div>
                        <div class="chart-container half">
                            <h3>📊 Stochastic Oscillator</h3>
                            <canvas id="stochasticChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Trading Plan -->
                <div class="trading-signals">
                    <h3>🎯 Trading Plan & Entry Strategy</h3>
                    <div class="signal-grid">
                        <div class="signal-item entry">
                            <span class="label">💰 Entry Price</span>
                            <span class="value">Rp <?php echo number_format($analysis['entry'], 2); ?></span>
                        </div>
                        <div class="signal-item sl">
                            <span class="label">🛡️ Stop Loss</span>
                            <span class="value">Rp <?php echo number_format($analysis['stop_loss'], 2); ?></span>
                            <span class="percent"><?php echo $analysis['entry'] > 0 ? round((($analysis['entry'] - $analysis['stop_loss']) / $analysis['entry']) * 100, 2) : 0; ?>%</span>
                        </div>
                        <div class="signal-item tp">
                            <span class="label">🎯 Take Profit</span>
                            <span class="value">Rp <?php echo number_format($analysis['take_profit'], 2); ?></span>
                            <span class="percent"><?php echo $analysis['entry'] > 0 ? round((($analysis['take_profit'] - $analysis['entry']) / $analysis['entry']) * 100, 2) : 0; ?>%</span>
                        </div>
                        <div class="signal-item rr">
                            <span class="label">⚖️ Risk/Reward</span>
                            <span class="value">1:<?php echo $analysis['risk_reward']; ?></span>
                            <span class="percent"><?php echo $analysis['risk_reward'] >= 2 ? 'Excellent' : ($analysis['risk_reward'] >= 1.5 ? 'Good' : 'Fair'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Reasoning -->
                <div class="reasoning-box">
                    <h3>💡 Trading Rationale & Analysis</h3>
                    <p><?php echo $analysis['reasoning']; ?></p>
                </div>

                <!-- Technical Indicators Dashboard -->
                <div class="indicators-dashboard">
                    <h3>📊 Technical Indicators Summary</h3>
                    <div class="indicators-grid">
                        <div class="indicator-card">
                            <h4>RSI (14)</h4>
                            <div class="indicator-value <?php echo $analysis['rsi'] < 30 ? 'oversold' : ($analysis['rsi'] > 70 ? 'overbought' : 'neutral'); ?>">
                                <?php echo $analysis['rsi']; ?>
                            </div>
                            <div class="indicator-bar">
                                <div class="bar-fill" style="width: <?php echo $analysis['rsi']; ?>%"></div>
                            </div>
                            <p class="indicator-status">
                                <?php 
                                if ($analysis['rsi'] < 30) echo '🟢 Oversold - Strong Buy Zone';
                                elseif ($analysis['rsi'] > 70) echo '🔴 Overbought - Take Profit Zone';
                                else echo '🟡 Neutral - Wait for Confirmation';
                                ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>MACD</h4>
                            <div class="macd-values">
                                <p>MACD: <strong><?php echo $analysis['macd']; ?></strong></p>
                                <p>Signal: <strong><?php echo $analysis['signal']; ?></strong></p>
                                <p>Histogram: <strong><?php echo $analysis['histogram']; ?></strong></p>
                            </div>
                            <p class="indicator-status <?php echo $analysis['macd'] > $analysis['signal'] ? 'bullish' : 'bearish'; ?>">
                                <?php echo $analysis['macd'] > $analysis['signal'] ? '🟢 Bullish Momentum' : '🔴 Bearish Momentum'; ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>Moving Averages</h4>
                            <p>SMA 20: <strong>Rp <?php echo number_format($analysis['sma20'], 2); ?></strong></p>
                            <p>SMA 50: <strong>Rp <?php echo number_format($analysis['sma50'], 2); ?></strong></p>
                            <?php if ($analysis['sma200'] > 0): ?>
                            <p>SMA 200: <strong>Rp <?php echo number_format($analysis['sma200'], 2); ?></strong></p>
                            <?php endif; ?>
                            <p class="indicator-status">
                                <?php 
                                if ($analysis['current_price'] > $analysis['sma20'] && $analysis['sma20'] > $analysis['sma50']) 
                                    echo '🟢 Strong Uptrend';
                                elseif ($analysis['current_price'] < $analysis['sma20'] && $analysis['sma20'] < $analysis['sma50']) 
                                    echo '🔴 Strong Downtrend';
                                else 
                                    echo '🟡 Sideways / Consolidation';
                                ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>Bollinger Bands</h4>
                            <p>Upper: <strong class="resistance">Rp <?php echo number_format($analysis['bb_upper'], 2); ?></strong></p>
                            <p>Middle: <strong>Rp <?php echo number_format($analysis['bb_middle'], 2); ?></strong></p>
                            <p>Lower: <strong class="support">Rp <?php echo number_format($analysis['bb_lower'], 2); ?></strong></p>
                            <p class="indicator-status">
                                <?php 
                                if ($analysis['current_price'] < $analysis['bb_lower']) 
                                    echo '🟢 Below Lower Band - Oversold';
                                elseif ($analysis['current_price'] > $analysis['bb_upper']) 
                                    echo '🔴 Above Upper Band - Overbought';
                                else 
                                    echo '🟡 Within Bands - Normal Range';
                                ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>Support & Resistance</h4>
                            <p>Resistance: <strong class="resistance">Rp <?php echo number_format($analysis['resistance'], 2); ?></strong></p>
                            <p>Support: <strong class="support">Rp <?php echo number_format($analysis['support'], 2); ?></strong></p>
                            <p>ATR (14): <strong>Rp <?php echo number_format($analysis['atr'], 2); ?></strong></p>
                            <div class="price-position">
                                <?php
                                $range = $analysis['resistance'] - $analysis['support'];
                                $position = $range > 0 ? (($analysis['current_price'] - $analysis['support']) / $range) * 100 : 50;
                                ?>
                                <div class="position-bar">
                                    <div class="current-marker" style="left: <?php echo $position; ?>%">
                                        <span class="marker-label">Now</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="indicator-card">
                            <h4>Volume Analysis</h4>
                            <p>Current: <strong><?php echo number_format($analysis['volume']); ?></strong></p>
                            <p>Avg (20D): <strong><?php echo number_format($analysis['avg_volume']); ?></strong></p>
                            <div class="volume-ratio">
                                <?php 
                                $volRatio = $analysis['avg_volume'] > 0 ? ($analysis['volume'] / $analysis['avg_volume']) * 100 : 100;
                                ?>
                                <div class="ratio-bar" style="width: <?php echo min($volRatio, 200); ?>%"></div>
                            </div>
                            <p class="indicator-status">
                                <?php 
                                if ($volRatio > 150) echo '🔥 Abnormal High Volume';
                                elseif ($volRatio > 100) echo '📈 Above Average';
                                else echo '📉 Below Average';
                                ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>Stochastic (14)</h4>
                            <div class="indicator-value <?php echo $analysis['stochastic'] < 20 ? 'oversold' : ($analysis['stochastic'] > 80 ? 'overbought' : 'neutral'); ?>">
                                <?php echo $analysis['stochastic']; ?>
                            </div>
                            <div class="indicator-bar">
                                <div class="bar-fill" style="width: <?php echo $analysis['stochastic']; ?>%"></div>
                            </div>
                            <p class="indicator-status">
                                <?php 
                                if ($analysis['stochastic'] < 20) echo '🟢 Oversold Territory';
                                elseif ($analysis['stochastic'] > 80) echo '🔴 Overbought Territory';
                                else echo '🟡 Neutral Zone';
                                ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>EMA Analysis</h4>
                            <p>EMA 12: <strong>Rp <?php echo number_format($analysis['ema12'], 2); ?></strong></p>
                            <p>EMA 26: <strong>Rp <?php echo number_format($analysis['ema26'], 2); ?></strong></p>
                            <p class="indicator-status">
                                <?php 
                                if ($analysis['ema12'] > $analysis['ema26']) 
                                    echo '🟢 Fast EMA Above Slow EMA';
                                else 
                                    echo '🔴 Fast EMA Below Slow EMA';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pattern Detection -->
                <?php if (count($analysis['patterns']) > 0): ?>
                <div class="patterns-section">
                    <h3>🔍 Candlestick Pattern Detection</h3>
                    <div class="patterns-list">
                        <?php foreach ($analysis['patterns'] as $pattern): ?>
                            <div class="pattern-item <?php echo strpos($pattern, 'Bullish') !== false || strpos($pattern, 'Hammer') !== false ? 'bullish' : 'bearish'; ?>">
                                <span class="pattern-icon"><?php echo strpos($pattern, 'Bullish') !== false || strpos($pattern, 'Hammer') !== false ? '🟢' : '🔴'; ?></span>
                                <span class="pattern-text"><?php echo $pattern; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Technical Signals -->
                <div class="signals-list">
                    <h3>📋 All Technical Signals</h3>
                    <ul>
                        <?php foreach ($analysis['signals'] as $signal): ?>
                            <li>
                                <?php 
                                if (strpos($signal, 'Bullish') !== false || strpos($signal, 'Buy') !== false || strpos($signal, 'Oversold') !== false) {
                                    echo '<span class="signal-bull">🟢</span>';
                                } elseif (strpos($signal, 'Bearish') !== false || strpos($signal, 'Sell') !== false || strpos($signal, 'Overbought') !== false) {
                                    echo '<span class="signal-bear">🔴</span>';
                                } else {
                                    echo '<span class="signal-neutral">🟡</span>';
                                }
                                echo $signal;
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Disclaimer -->
                <div class="disclaimer">
                    <p><strong>⚠️ Disclaimer:</strong> Analisis ini untuk tujuan edukasi dan informasi. Bukan saran investasi. Trading saham mengandung risiko. Selalu lakukan riset sendiri dan konsultasi dengan financial advisor profesional sebelum membuat keputusan investasi.</p>
                </div>
            </div>

            <!-- Chart Data Script -->
            <script>
                const chartData = <?php echo json_encode($analysis); ?>;
                
                const candlestickData = chartData.ohlcv.map(item => ({
                    x: item.timestamp,
                    o: item.open,
                    h: item.high,
                    l: item.low,
                    c: item.close
                }));
                
                const volumeData = chartData.ohlcv.map(item => ({
                    x: item.timestamp,
                    y: item.volume
                }));
                
                const labels = chartData.ohlcv.map(item => item.timestamp);
                
                const ctxCandle = document.getElementById('candlestickChart').getContext('2d');
                new Chart(ctxCandle, {
                    type: 'candlestick',
                    data: {
                        datasets: [{
                            label: chartData.ticker,
                            data: candlestickData,
                            color: {
                                up: '#26a69a',
                                down: '#ef5350',
                                unchanged: '#999'
                            }
                        }, {
                            label: 'SMA 20',
                            type: 'line',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.sma20[i] || null
                            })),
                            borderColor: '#2196F3',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }, {
                            label: 'SMA 50',
                            type: 'line',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.sma50[i] || null
                            })),
                            borderColor: '#FF9800',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }, {
                            label: 'BB Upper',
                            type: 'line',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.bb.upper[i] || null
                            })),
                            borderColor: 'rgba(156, 39, 176, 0.5)',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            pointRadius: 0,
                            fill: false
                        }, {
                            label: 'BB Lower',
                            type: 'line',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.bb.lower[i] || null
                            })),
                            borderColor: 'rgba(156, 39, 176, 0.5)',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            pointRadius: 0,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                });
                
                const ctxVolume = document.getElementById('volumeChart').getContext('2d');
                new Chart(ctxVolume, {
                    type: 'bar',
                    data: {
                        datasets: [{
                            label: 'Volume',
                            data: volumeData,
                            backgroundColor: volumeData.map((item, i) => {
                                if (i === 0) return '#999';
                                const prev = chartData.ohlcv[i-1];
                                const curr = chartData.ohlcv[i];
                                return curr.close >= prev.close ? '#26a69a' : '#ef5350';
                            })
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                }
                            },
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
                
                const ctxMacd = document.getElementById('macdChart').getContext('2d');
                new Chart(ctxMacd, {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: 'MACD',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.macd.macd[i] || null
                            })),
                            borderColor: '#2196F3',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }, {
                            label: 'Signal',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.macd.signal[i] || null
                            })),
                            borderColor: '#FF9800',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }, {
                            label: 'Histogram',
                            type: 'bar',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.macd.histogram[i] || null
                            })),
                            backgroundColor: labels.map((x, i) => {
                                const val = chartData.chart_data.macd.histogram[i] || 0;
                                return val >= 0 ? 'rgba(38, 166, 154, 0.5)' : 'rgba(239, 83, 80, 0.5)';
                            })
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    }
                });
                
                const ctxRsi = document.getElementById('rsiChart').getContext('2d');
                new Chart(ctxRsi, {
                    type: 'line',
                    data: {
                        labels: chartData.ohlcv.map(item => new Date(item.timestamp)),
                        datasets: [{
                            label: 'RSI',
                            data: Array(chartData.ohlcv.length).fill(chartData.rsi),
                            borderColor: '#9C27B0',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                min: 0,
                                max: 100
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    }
                });
                
                const ctxStoch = document.getElementById('stochasticChart').getContext('2d');
                new Chart(ctxStoch, {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: 'Stochastic',
                            data: labels.map((x, i) => ({
                                x: x,
                                y: chartData.chart_data.stochastic[i] || null
                            })),
                            borderColor: '#E91E63',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                }
                            },
                            y: {
                                min: 0,
                                max: 100
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>

    <footer>
        <p>📡 Real-time Data from Yahoo Finance | ⏰ Last Update: <?php echo date('d M Y H:i:s'); ?> WIB</p>
        <p>© 2025 IHSG Professional Analyzer | Built with PHP & Chart.js</p>
    </footer>
</body>
</html>
