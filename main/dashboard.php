<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IHSG Pro Trading Analyzer</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
       <!-- NAVBAR -->
    <nav class="main-navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <span class="brand-icon">📊</span>
                <span class="brand-text">IHSG Trading Pro</span>
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php" class="nav-link active">
                    <span class="nav-icon">🔍</span>
                    <span>Analisis Saham</span>
                </a>
                <a href="scanner.php" class="nav-link">
                    <span class="nav-icon">🎯</span>
                    <span>Stock Screener</span>
                    <span class="nav-badge">New</span>
                </a>
            </div>
        </div>
    </nav>
    <div class="container">
        <header>
            <h1>📊 IHSG PRO Trading Analyzer</h1>
            <p>Analisis Teknikal Mendalam dengan Interpretasi Expert</p>
        </header>

        <section class="input-section">
            <form id="analyzeForm" method="POST">
                <div class="form-group">
                    <label for="ticker">Kode Saham (tanpa .JK):</label>
                    <input type="text" id="ticker" name="ticker" placeholder="Contoh: BBCA, TLKM, ASII, GOTO" value="<?php echo isset($_POST['ticker']) ? htmlspecialchars($_POST['ticker']) : 'WIFI'; ?>" required>
                </div>
                
                <button type="submit" class="btn-analyze">🔍 Analisa Lengkap</button>
            </form>
        </section>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'fetch_data.php';
            require_once 'indicators.php';
            require_once 'news_fetcher.php';
            
            $tickerInput = strtoupper(trim($_POST['ticker']));
            $ticker = $tickerInput . '.JK';
            $capital = 10000000;
            $riskPercent = 2;
            
            $historicalData = fetchYahooFinanceData($ticker);
            
            if ($historicalData && count($historicalData) > 0) {
                $newsData = fetchStockNews($tickerInput);
                $analysis = analyzeStockPro($historicalData, $capital, $riskPercent);
                
                $latestData = end($historicalData);
                $previousData = $historicalData[count($historicalData) - 2];
                $currentPrice = $latestData['close'];
                
                $priceChange = $latestData['close'] - $previousData['close'];
                $priceChangePercent = ($priceChange / $previousData['close']) * 100;
                ?>
                
                <section class="info-panel">
                    <div class="stock-header">
                        <div class="stock-title-row">
                            <h2><?php echo $tickerInput; ?></h2>
                            <span class="company-name">Indonesia Stock Exchange</span>
                        </div>
                        <div class="price-info">
                            <span class="price">Rp <?php echo number_format($latestData['close'], 0, ',', '.'); ?></span>
                            <span class="change <?php echo $priceChange >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $priceChange >= 0 ? '+' : ''; ?><?php echo number_format($priceChange, 0, ',', '.'); ?> 
                                (<?php echo $priceChange >= 0 ? '+' : ''; ?><?php echo number_format($priceChangePercent, 2); ?>%)
                            </span>
                        </div>
                        <div class="meta-info">
                            <span>📊 Volume: <?php echo number_format($latestData['volume'], 0, ',', '.'); ?></span>
                            <span>📅 <?php echo date('d M Y', strtotime($latestData['date'])); ?></span>
                            <span>⏰ Real-time Analysis</span>
                        </div>
                    </div>
                </section>

                <section class="news-section">
                    <h3>📰 Berita Terkini & Sentimen Pasar</h3>
                    <?php if (!empty($newsData)): ?>
                        <div class="news-grid">
                            <?php foreach (array_slice($newsData, 0, 6) as $news): ?>
                                <div class="news-card">
                                    <div class="news-header">
                                        <span class="news-date"><?php echo $news['date']; ?></span>
                                        <span class="sentiment-badge <?php echo $news['sentiment']; ?>">
                                            <?php 
                                            if ($news['sentiment'] === 'positive') echo '🟢 Positif';
                                            elseif ($news['sentiment'] === 'negative') echo '🔴 Negatif';
                                            else echo '🟡 Netral';
                                            ?>
                                        </span>
                                    </div>
                                    <h4 class="news-title"><?php echo $news['title']; ?></h4>
                                    <p class="news-summary"><?php echo $news['summary']; ?></p>
                                    <div class="news-impact">
                                        <strong>Dampak Terhadap Saham:</strong> <?php echo $news['impact']; ?>
                                    </div>
                                    <?php if ($news['url']): ?>
                                        <a href="<?php echo $news['url']; ?>" target="_blank" class="news-link">Baca Selengkapnya →</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="sentiment-summary">
                            <?php 
                            $sentimentScore = calculateSentimentScore($newsData);
                            $sentClass = $sentimentScore > 0 ? 'positive' : ($sentimentScore < 0 ? 'negative' : 'neutral');
                            ?>
                            <div class="sentiment-result <?php echo $sentClass; ?>">
                                <strong>Sentimen Berita Overall:</strong>
                                <?php 
                                if ($sentimentScore > 0) echo "🟢 POSITIF (+{$sentimentScore}) - Berita mendukung kenaikan harga";
                                elseif ($sentimentScore < 0) echo "🔴 NEGATIF ({$sentimentScore}) - Berita memberi tekanan pada harga";
                                else echo "🟡 NETRAL - Berita tidak memberikan arah yang jelas";
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="no-news">Tidak ada berita terbaru untuk saham ini.</p>
                    <?php endif; ?>
                </section>

                <section class="chart-section">
                    <h3>📈 Grafik Teknikal Lengkap</h3>
                    
                    <div class="chart-tabs">
                        <button class="tab-btn active" onclick="showChart('price')">Candlestick & MA</button>
                        <button class="tab-btn" onclick="showChart('volume')">Volume</button>
                        <button class="tab-btn" onclick="showChart('rsi')">RSI</button>
                        <button class="tab-btn" onclick="showChart('macd')">MACD</button>
                        <button class="tab-btn" onclick="showChart('stoch')">Stochastic</button>
                    </div>
                    
                    <div id="price-chart-container" class="chart-container-apex">
                        <div id="priceChart"></div>
                    </div>
                    
                    <div id="volume-chart-container" class="chart-container-apex" style="display:none;">
                        <div id="volumeChart"></div>
                    </div>
                    
                    <div id="rsi-chart-container" class="chart-container-apex" style="display:none;">
                        <div id="rsiChart"></div>
                    </div>
                    
                    <div id="macd-chart-container" class="chart-container-apex" style="display:none;">
                        <div id="macdChart"></div>
                    </div>
                    
                    <div id="stoch-chart-container" class="chart-container-apex" style="display:none;">
                        <div id="stochChart"></div>
                    </div>
                </section>

                <section class="indicators-section">
                    <h3>🔧 Analisis Indikator Teknikal Detail</h3>
                    <div class="indicators-grid">
                        <div class="indicator-card">
                            <h4>RSI (14)</h4>
                            <div class="indicator-value <?php echo $analysis['rsi'] > 70 ? 'overbought' : ($analysis['rsi'] < 30 ? 'oversold' : 'neutral'); ?>">
                                <?php echo number_format($analysis['rsi'], 2); ?>
                            </div>
                            <div class="indicator-phase <?php echo $analysis['rsi_interp']['color']; ?>">
                                <strong><?php echo $analysis['rsi_interp']['phase']; ?></strong>
                            </div>
                            <p class="indicator-action">
                                <?php echo $analysis['rsi_interp']['action']; ?>
                            </p>
                            <p class="indicator-explanation">
                                <?php echo $analysis['rsi_interp']['detail']; ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>Stochastic (14,3,3)</h4>
                            <div class="indicator-value">
                                %K: <?php echo number_format($analysis['stoch_k'], 2); ?><br>
                                %D: <?php echo number_format($analysis['stoch_d'], 2); ?>
                            </div>
                            <div class="indicator-phase <?php echo $analysis['stoch_interp']['color']; ?>">
                                <strong><?php echo $analysis['stoch_interp']['phase']; ?></strong>
                            </div>
                            <p class="indicator-action">
                                <?php echo $analysis['stoch_interp']['action']; ?>
                            </p>
                            <p class="indicator-explanation">
                                <?php echo $analysis['stoch_interp']['detail']; ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>MACD (12,26,9)</h4>
                            <div class="indicator-value">
                                MACD: <?php echo number_format($analysis['macd'], 4); ?><br>
                                Signal: <?php echo number_format($analysis['macd_signal'], 4); ?><br>
                                Hist: <?php echo number_format($analysis['macd_hist'], 4); ?>
                            </div>
                            <div class="indicator-phase <?php echo $analysis['macd_interp']['color']; ?>">
                                <strong><?php echo $analysis['macd_interp']['phase']; ?></strong>
                            </div>
                            <p class="indicator-action">
                                <?php echo $analysis['macd_interp']['action']; ?>
                            </p>
                            <p class="indicator-explanation">
                                <?php echo $analysis['macd_interp']['detail']; ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>Volume Analysis</h4>
                            <div class="indicator-value">
                                <?php echo number_format($latestData['volume'], 0, ',', '.'); ?>
                            </div>
                            <div class="indicator-phase <?php echo $analysis['volume_interp']['color']; ?>">
                                <strong><?php echo $analysis['volume_interp']['phase']; ?></strong>
                            </div>
                            <p class="indicator-action">
                                <?php echo $analysis['volume_interp']['action']; ?>
                            </p>
                            <p class="indicator-explanation">
                                <?php echo $analysis['volume_interp']['detail']; ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>Bollinger Bands</h4>
                            <div class="indicator-value">
                                Upper: Rp <?php echo number_format($analysis['chart_data']['bb_upper'][count($analysis['chart_data']['bb_upper'])-1], 0, ',', '.'); ?><br>
                                Middle: Rp <?php echo number_format($analysis['chart_data']['bb_middle'][count($analysis['chart_data']['bb_middle'])-1], 0, ',', '.'); ?><br>
                                Lower: Rp <?php echo number_format($analysis['chart_data']['bb_lower'][count($analysis['chart_data']['bb_lower'])-1], 0, ',', '.'); ?>
                            </div>
                            <div class="indicator-phase <?php echo $analysis['bb_interp']['color']; ?>">
                                <strong><?php echo $analysis['bb_interp']['phase']; ?></strong>
                            </div>
                            <p class="indicator-action">
                                <?php echo $analysis['bb_interp']['action']; ?>
                            </p>
                            <p class="indicator-explanation">
                                <?php echo $analysis['bb_interp']['detail']; ?>
                            </p>
                        </div>

                        <div class="indicator-card">
                            <h4>ADX (Trend Strength)</h4>
                            <div class="indicator-value">
                                <?php echo number_format($analysis['adx'], 2); ?>
                            </div>
                            <div class="indicator-phase <?php echo $analysis['adx_interp']['color']; ?>">
                                <strong><?php echo $analysis['adx_interp']['phase']; ?></strong>
                            </div>
                            <p class="indicator-action">
                                <?php echo $analysis['adx_interp']['action']; ?>
                            </p>
                            <p class="indicator-explanation">
                                <?php echo $analysis['adx_interp']['detail']; ?>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="pattern-section">
                    <h3>🕯️ Pola Candlestick Terdeteksi</h3>
                    <?php if (!empty($analysis['patterns'])): ?>
                        <div class="pattern-grid">
                            <?php foreach ($analysis['patterns'] as $pattern): ?>
                                <div class="pattern-card <?php echo $pattern['type']; ?>">
                                    <div class="pattern-header">
                                        <h4><?php echo $pattern['name']; ?></h4>
                                        <span class="pattern-strength <?php echo $pattern['strength']; ?>">
                                            <?php echo ucfirst($pattern['strength']); ?>
                                        </span>
                                    </div>
                                    <p class="pattern-date">📅 Tanggal: <?php echo $pattern['date']; ?></p>
                                    <p class="pattern-desc">
                                        <?php 
                                        $descriptions = [
                                            'Bullish Engulfing' => 'Pola pembalikan bullish yang sangat kuat. Candle hijau besar "menelan" candle merah sebelumnya secara sempurna, mengindikasikan tekanan beli yang sangat kuat dan shift momentum dari bearish ke bullish. Entry setelah konfirmasi candle berikutnya.',
                                            'Hammer' => 'Pola reversal bullish dengan karakteristik shadow bawah yang panjang (minimal 2x body). Menunjukkan bahwa meskipun harga sempat turun drastis, buyer berhasil push harga kembali naik. Ini adalah sinyal penolakan terhadap harga rendah dan potensi pembalikan ke atas.',
                                            'Morning Star' => 'Pola pembalikan 3-candle yang paling powerful. Candle 1 (merah besar) + Candle 2 (small body/doji) + Candle 3 (hijau besar). Mengindikasikan akhir dari downtrend dan konfirmasi awal uptrend. Setup buy yang sangat reliable.',
                                            'Bullish Harami' => 'Pola pembalikan dengan candle kecil (anak) berada di dalam body candle besar sebelumnya (ibu). Menunjukkan penurunan momentum bearish dan indecision market yang sering diikuti reversal bullish.',
                                            'Piercing Pattern' => 'Pola bullish 2-candle. Candle hijau kedua open di bawah low candle merah pertama, tapi close di atas 50% body candle merah. Strong buying pressure signal.'
                                        ];
                                        echo $descriptions[$pattern['name']] ?? 'Pola candlestick bullish terdeteksi yang mengindikasikan potensi kenaikan harga.';
                                        ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-pattern">⚠️ Tidak ada pola candlestick bullish yang terdeteksi dalam 10 hari terakhir. Tunggu konfirmasi pola sebelum entry untuk meningkatkan probabilitas success.</p>
                    <?php endif; ?>
                </section>

                <section class="sr-section">
                    <h3>📍 Support & Resistance Levels</h3>
                    
                    <div class="sr-table-container">
                        <table class="sr-table">
                            <thead>
                                <tr>
                                    <th colspan="3" class="resistance-header">🔴 RESISTANCE LEVELS</th>
                                </tr>
                                <tr class="sub-header">
                                    <th>Level</th>
                                    <th>Harga</th>
                                    <th>Jarak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $resLabels = ['R3 (Strong)', 'R2 (Medium)', 'R1 (Weak)'];
                                foreach ($analysis['resistances'] as $idx => $res): 
                                    $distance = (($res - $currentPrice) / $currentPrice) * 100;
                                ?>
                                    <tr class="resistance-row">
                                        <td class="level-label"><?php echo $resLabels[$idx]; ?></td>
                                        <td class="level-value">Rp <?php echo number_format($res, 0, ',', '.'); ?></td>
                                        <td class="distance-value">+<?php echo number_format($distance, 2); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="current-price-row">
                            <span class="current-label">🎯 CURRENT PRICE</span>
                            <span class="current-value">Rp <?php echo number_format($currentPrice, 0, ',', '.'); ?></span>
                        </div>
                        
                        <table class="sr-table">
                            <thead>
                                <tr>
                                    <th colspan="3" class="support-header">🟢 SUPPORT LEVELS</th>
                                </tr>
                                <tr class="sub-header">
                                    <th>Level</th>
                                    <th>Harga</th>
                                    <th>Jarak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $supLabels = ['S1 (Weak)', 'S2 (Medium)', 'S3 (Strong)'];
                                foreach ($analysis['supports'] as $idx => $sup): 
                                    $distance = (($sup - $currentPrice) / $currentPrice) * 100;
                                ?>
                                    <tr class="support-row">
                                        <td class="level-label"><?php echo $supLabels[$idx]; ?></td>
                                        <td class="level-value">Rp <?php echo number_format($sup, 0, ',', '.'); ?></td>
                                        <td class="distance-value"><?php echo number_format($distance, 2); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="sr-explanation">
                            <p><strong>📖 Cara Membaca S/R:</strong></p>
                            <ul>
                                <li><strong>Resistance:</strong> Level harga di mana tekanan jual biasanya muncul. Semakin dekat dengan R1, semakin tinggi probabilitas rejection. Breakout di atas R1 dengan volume tinggi = sinyal kuat untuk kenaikan lebih lanjut.</li>
                                <li><strong>Support:</strong> Level harga di mana tekanan beli biasanya muncul. Jika harga turun ke S1-S3, itu adalah zona buy yang bagus. Breakdown di bawah S3 = sinyal bearish kuat.</li>
                                <li><strong>Trading Strategy:</strong> Buy di support, sell di resistance. Atau buy saat breakout resistance dengan volume confirmation.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="tradeplan-section">
                    <h3>🎯 Advanced Trade Plan</h3>
                    
                    <div class="plan-header">
                        <div class="trend-status <?php echo strtolower($analysis['trend']); ?>">
                            <strong>📊 Trend:</strong> <?php echo $analysis['trend']; ?>
                        </div>
                        
                        <div class="recommendation-box <?php echo strtolower(str_replace(' ', '-', $analysis['recommendation'])); ?>">
                            <h4>💡 <?php echo $analysis['recommendation']; ?></h4>
                        </div>
                    </div>

                    <?php if ($analysis['recommendation'] !== 'HINDARI'): ?>
                    <div class="trade-strategies">
                        <div class="strategy-card">
                            <h4>📊 Conservative Strategy (Low Risk)</h4>
                            <div class="trade-details">
                                <div class="trade-row">
                                    <span class="trade-label">📍 Entry Zone:</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['conservative']['entry_min'], 0, ',', '.'); ?> - <?php echo number_format($analysis['conservative']['entry_max'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">🛑 Stop Loss:</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['conservative']['stop_loss'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">🎯 Target TP1 (50% exit):</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['conservative']['tp1'], 0, ',', '.'); ?> (RR: 1:<?php echo number_format($analysis['conservative']['rr1'], 2); ?>)</span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">🎯 Target TP2 (50% exit):</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['conservative']['tp2'], 0, ',', '.'); ?> (RR: 1:<?php echo number_format($analysis['conservative']['rr2'], 2); ?>)</span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">📦 Position Size:</span>
                                    <span class="trade-value"><?php echo number_format($analysis['conservative']['lots'], 0, ',', '.'); ?> lot (<?php echo number_format($analysis['conservative']['lots'] * 100, 0, ',', '.'); ?> shares)</span>
                                </div>
                                <div class="trade-row profit-row">
                                    <span class="trade-label">💰 Potential Profit (TP1):</span>
                                    <span class="trade-value profit">+Rp <?php echo number_format($analysis['conservative']['potential_profit_tp1'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row profit-row">
                                    <span class="trade-label">💰 Potential Profit (TP2):</span>
                                    <span class="trade-value profit">+Rp <?php echo number_format($analysis['conservative']['potential_profit_tp2'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row loss-row">
                                    <span class="trade-label">⚠️ Max Loss (jika SL hit):</span>
                                    <span class="trade-value loss">-Rp <?php echo number_format($analysis['conservative']['max_loss'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            <div class="strategy-notes">
                                <p><strong>💡 Catatan:</strong> Entry bertahap di zona support. Tunggu konfirmasi candle bullish + volume meningkat. Discipline dengan stop loss!</p>
                            </div>
                        </div>

                        <div class="strategy-card">
                            <h4>⚡ Aggressive Strategy (Higher Risk/Reward)</h4>
                            <div class="trade-details">
                                <div class="trade-row">
                                    <span class="trade-label">📍 Entry Zone:</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['aggressive']['entry_min'], 0, ',', '.'); ?> - <?php echo number_format($analysis['aggressive']['entry_max'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">🛑 Stop Loss:</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['aggressive']['stop_loss'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">🎯 Target TP1 (50% exit):</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['aggressive']['tp1'], 0, ',', '.'); ?> (RR: 1:<?php echo number_format($analysis['aggressive']['rr1'], 2); ?>)</span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">🎯 Target TP2 (50% exit):</span>
                                    <span class="trade-value">Rp <?php echo number_format($analysis['aggressive']['tp2'], 0, ',', '.'); ?> (RR: 1:<?php echo number_format($analysis['aggressive']['rr2'], 2); ?>)</span>
                                </div>
                                <div class="trade-row">
                                    <span class="trade-label">📦 Position Size:</span>
                                    <span class="trade-value"><?php echo number_format($analysis['aggressive']['lots'], 0, ',', '.'); ?> lot (<?php echo number_format($analysis['aggressive']['lots'] * 100, 0, ',', '.'); ?> shares)</span>
                                </div>
                                <div class="trade-row profit-row">
                                    <span class="trade-label">💰 Potential Profit (TP1):</span>
                                    <span class="trade-value profit">+Rp <?php echo number_format($analysis['aggressive']['potential_profit_tp1'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row profit-row">
                                    <span class="trade-label">💰 Potential Profit (TP2):</span>
                                    <span class="trade-value profit">+Rp <?php echo number_format($analysis['aggressive']['potential_profit_tp2'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="trade-row loss-row">
                                    <span class="trade-label">⚠️ Max Loss (jika SL hit):</span>
                                    <span class="trade-value loss">-Rp <?php echo number_format($analysis['aggressive']['max_loss'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            <div class="strategy-notes">
                                <p><strong>💡 Catatan:</strong> Entry lebih cepat tanpa tunggu pullback dalam. Risk lebih tinggi tapi reward juga lebih besar. Gunakan jika skor teknikal >75%.</p>
                            </div>
                        </div>
                    </div>

                    <div class="trade-timeline">
                        <h4>⏰ Execution Timeline & Checklist</h4>
                        <ul>
                            <li><strong>Day 1-2 (Entry Phase):</strong> <?php echo $analysis['timeline']['day1']; ?></li>
                            <li><strong>Day 3-5 (Monitoring Phase):</strong> <?php echo $analysis['timeline']['day3']; ?></li>
                            <li><strong>Day 6-10 (Exit Phase):</strong> <?php echo $analysis['timeline']['day6']; ?></li>
                        </ul>
                    </div>
                    <?php else: ?>
                        <div class="no-trade-setup">
                            <h4>🚫 Setup Trading Tidak Ideal</h4>
                            <p>Saat ini indikator teknikal menunjukkan kondisi yang kurang mendukung untuk entry. Skor teknikal terlalu rendah (< 45%). Berikut rekomendasi:</p>
                            <ul>
                                <li>✅ <strong>Patience:</strong> Tunggu skor teknikal naik minimal 60% sebelum entry</li>
                                <li>✅ <strong>Watch List:</strong> Masukkan saham ini ke watchlist dan monitor daily</li>
                                <li>✅ <strong>Alternative:</strong> Cari saham lain dengan skor teknikal lebih tinggi</li>
                                <li>✅ <strong>Wait Signal:</strong> Tunggu golden cross MACD atau RSI bounce dari oversold</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="checklist-section">
                    <h3>✅ Advanced Trading Signals Checklist (10 Kriteria)</h3>
                    <table class="checklist-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Kriteria</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 55%;">Detail & Interpretasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['checklist'] as $item): ?>
                            <tr>
                                <td><strong><?php echo $item['name']; ?></strong></td>
                                <td>
                                    <span class="status-badge <?php echo $item['passed'] ? 'pass' : 'fail'; ?>">
                                        <?php echo $item['passed'] ? '✅ Pass' : '❌ Fail'; ?>
                                    </span>
                                </td>
                                <td class="detail-text"><?php echo $item['detail']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="score-row">
                                <td><strong>🎯 Total Skor Teknikal:</strong></td>
                                <td colspan="2"><strong><?php echo $analysis['score']; ?> / <?php echo count($analysis['checklist']); ?></strong> (<?php echo round(($analysis['score'] / count($analysis['checklist'])) * 100); ?>%)</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div class="score-interpretation">
                        <?php 
                        $scorePercent = ($analysis['score'] / count($analysis['checklist'])) * 100;
                        if ($scorePercent >= 75) {
                            echo "<p class='high-score'>🎯 <strong>Setup SANGAT KUAT!</strong> ".round($scorePercent)."% indikator konfirmasi. Probabilitas success sangat tinggi. Entry dengan confidence dan discipline stop loss. Expected win rate: 70-80%.</p>";
                        } elseif ($scorePercent >= 60) {
                            echo "<p class='medium-score'>⚠️ <strong>Setup CUKUP BAIK.</strong> ".round($scorePercent)."% indikator konfirmasi. Entry bisa dilakukan dengan position sizing konservatif (50-70% dari plan). Expected win rate: 55-65%.</p>";
                        } elseif ($scorePercent >= 45) {
                            echo "<p class='medium-score'>⚡ <strong>Setup MARGINAL.</strong> ".round($scorePercent)."% indikator. Lebih baik wait konfirmasi lebih kuat. Jika entry, position size max 30% dengan stop loss sangat ketat. Expected win rate: 45-55%.</p>";
                        } else {
                            echo "<p class='low-score'>🚫 <strong>Setup LEMAH.</strong> Hanya ".round($scorePercent)."% indikator mendukung. TIDAK DIREKOMENDASIKAN untuk entry. Tunggu setup lebih baik atau cari saham alternatif dengan skor >60%.</p>";
                        }
                        ?>
                    </div>
                </section>

                <section class="narrative-section">
                    <h3>📝 Analisis Komprehensif & Rekomendasi Expert</h3>
                    <div class="narrative-content">
                        <?php echo $analysis['narrative']; ?>
                    </div>
                </section>

                <section class="risk-section">
                    <h3>⚠️ Risk Management & Trading Rules</h3>
                    <div class="risk-grid">
                        <div class="risk-card">
                            <h4>💰 Position Sizing Rules</h4>
                            <p>Modal Default: <strong>Rp 10.000.000</strong> | Max Risk: <strong>2% per trade</strong></p>
                            <ul>
                                <li>Max Loss per Trade: <strong>Rp 200.000</strong></li>
                                <li>Entry 1st: 50% di zona ideal (support/pullback)</li>
                                <li>Entry 2nd: 50% saat konfirmasi (breakout/bounce)</li>
                                <li><strong>NEVER</strong> risk lebih dari 2% total capital per trade</li>
                                <li>Max 3 positions bersamaan (diversifikasi)</li>
                            </ul>
                        </div>

                        <div class="risk-card">
                            <h4>🛑 Stop Loss Discipline</h4>
                            <ul>
                                <li><strong>WAJIB</strong> pasang SL segera setelah entry</li>
                                <li><strong>JANGAN PERNAH</strong> geser SL ke bawah</li>
                                <li>Trailing stop: geser ke breakeven jika profit >3%</li>
                                <li>Exit immediately jika breakdown support kuat</li>
                                <li>Mental SL: jika loss >2%, cut tanpa ragu</li>
                            </ul>
                        </div>

                        <div class="risk-card">
                            <h4>🎯 Take Profit Strategy</h4>
                            <ul>
                                <li><strong>TP1:</strong> Ambil 50% posisi (lock profit)</li>
                                <li><strong>Move SL:</strong> Ke breakeven setelah TP1 hit</li>
                                <li><strong>TP2:</strong> Target final (50% sisa posisi)</li>
                                <li><strong>Trailing:</strong> Jika momentum kuat, trailing 3-5% dari peak</li>
                                <li><strong>Mindset:</strong> Profit is profit - jangan greedy!</li>
                            </ul>
                        </div>

                        <div class="risk-card">
                            <h4>📊 Market Context Awareness</h4>
                            <ul>
                                <li>Cek IHSG trend sebelum entry individual stock</li>
                                <li>Hindari trading saat FOMC/major news</li>
                                <li>Volume >1.5x avg = better liquidity & validity</li>
                                <li>Waspadai gap opening >2% (tunggu fill gap)</li>
                                <li>Weekend holding: kurangi posisi jika profit >5%</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <script>
                    const chartData = <?php echo json_encode($analysis['chart_data']); ?>;
                    const tickerSymbol = "<?php echo $tickerInput; ?>";
                </script>
                <script src="chart_apex.js"></script>

                <?php
            } else {
                echo "<section class='error-section'><p>❌ Gagal mengambil data untuk <strong>$tickerInput</strong>. Pastikan kode saham benar (contoh: BBCA, TLKM, WIFI).</p></section>";
            }
        }
        ?>
    </div>
    <script>
    // Navbar shadow on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.main-navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>

</body>
</html>
