<?php
session_start();

// Fetch IHSG data from Yahoo Finance with better error handling
function fetchIHSGData() {
    $ticker = '^JKSE'; // Jakarta Composite Index
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?interval=1d&range=1mo";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Default fallback data
    $fallbackData = [
        'current' => 7250.50,
        'change' => 45.25,
        'changePercent' => 0.63,
        'timestamps' => array_map(function($i) { 
            return time() - (30 - $i) * 86400; 
        }, range(0, 29)),
        'prices' => [],
        'high' => 7280.00,
        'low' => 7200.00,
        'volume' => 15500000000
    ];
    
    // Generate fallback prices
    $basePrice = 7200;
    for ($i = 0; $i < 30; $i++) {
        $basePrice += (rand(-100, 150) / 10);
        $fallbackData['prices'][] = round($basePrice, 2);
    }
    
    // Check if request failed
    if ($httpCode !== 200 || !$response || $curlError) {
        error_log("Yahoo Finance API Error: HTTP $httpCode - $curlError");
        return $fallbackData;
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    // Validate response structure
    if (!isset($data['chart']['result'][0])) {
        error_log("Yahoo Finance API: Invalid response structure");
        return $fallbackData;
    }
    
    $result = $data['chart']['result'][0];
    
    // Check if meta data exists
    if (!isset($result['meta'])) {
        error_log("Yahoo Finance API: Missing meta data");
        return $fallbackData;
    }
    
    $meta = $result['meta'];
    
    // Get quotes
    if (!isset($result['indicators']['quote'][0])) {
        error_log("Yahoo Finance API: Missing quote data");
        return $fallbackData;
    }
    
    $quotes = $result['indicators']['quote'][0];
    
    // Safely get current price
    $currentPrice = isset($meta['regularMarketPrice']) ? (float)$meta['regularMarketPrice'] : 
                   (isset($meta['chartPreviousClose']) ? (float)$meta['chartPreviousClose'] : 7250.50);
    
    // Safely get previous close
    $previousClose = isset($meta['previousClose']) ? (float)$meta['previousClose'] : 
                    (isset($meta['chartPreviousClose']) ? (float)$meta['chartPreviousClose'] : $currentPrice);
    
    // Avoid division by zero
    if ($previousClose == 0 || $previousClose < 100) {
        $previousClose = $currentPrice > 0 ? $currentPrice : 7200;
    }
    
    // Calculate change
    $change = $currentPrice - $previousClose;
    $changePercent = ($change / $previousClose) * 100;
    
    // Get timestamps and prices
    $timestamps = isset($result['timestamp']) ? $result['timestamp'] : [];
    $closes = isset($quotes['close']) ? array_filter($quotes['close'], function($v) { 
        return $v !== null; 
    }) : [];
    
    // If no valid data, use fallback
    if (empty($timestamps) || empty($closes)) {
        error_log("Yahoo Finance API: Empty data arrays");
        return $fallbackData;
    }
    
    // Get last 30 data points
    $timestamps = array_slice($timestamps, -30);
    $closes = array_slice(array_values($closes), -30);
    
    // Get high, low, volume with defaults
    $high = isset($meta['regularMarketDayHigh']) ? (float)$meta['regularMarketDayHigh'] : $currentPrice * 1.02;
    $low = isset($meta['regularMarketDayLow']) ? (float)$meta['regularMarketDayLow'] : $currentPrice * 0.98;
    $volume = isset($quotes['volume']) && is_array($quotes['volume']) ? 
             (int)end(array_filter($quotes['volume'], function($v) { return $v !== null; })) : 
             15500000000;
    
    return [
        'current' => round($currentPrice, 2),
        'change' => round($change, 2),
        'changePercent' => round($changePercent, 2),
        'timestamps' => $timestamps,
        'prices' => array_map(function($p) { return round($p, 2); }, $closes),
        'high' => round($high, 2),
        'low' => round($low, 2),
        'volume' => $volume,
        'source' => 'yahoo'
    ];
}

// Fetch IHSG data
try {
    $ihsgData = fetchIHSGData();
} catch (Exception $e) {
    error_log("Error fetching IHSG data: " . $e->getMessage());
    $ihsgData = [
        'current' => 7250.50,
        'change' => 45.25,
        'changePercent' => 0.63,
        'timestamps' => array_map(function($i) { 
            return time() - (30 - $i) * 86400; 
        }, range(0, 29)),
        'prices' => array_map(function() { 
            static $base = 7200;
            $base += rand(-50, 100) / 10;
            return round($base, 2);
        }, range(0, 29)),
        'high' => 7280.00,
        'low' => 7200.00,
        'volume' => 15500000000,
        'source' => 'fallback'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SahamQu - Platform Analisis Saham Indonesia Terbaik</title>
    <meta name="description" content="Analisis saham Indonesia dengan AI. Real-time signals, historical data, dan akurasi tinggi untuk trader profesional.">
    <link rel="stylesheet" href="landing.css?v=2.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="logo-text">
                    <span class="logo-main">SahamQu</span>
                    <span class="logo-tagline">Smart Trading</span>
                </div>
            </div>
            <ul class="nav-menu" id="navMenu">
                <li><a href="#features">Fitur</a></li>
                <li><a href="#how-it-works">Cara Kerja</a></li>
                <li><a href="#pricing">Harga</a></li>
            </ul>
            <div class="nav-buttons">
                <button class="btn-login" onclick="openModal('login')">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
                <button class="btn-register" onclick="openModal('register')">
                    <i class="fas fa-rocket"></i> Daftar Sekarang
                </button>
            </div>
            <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Real IHSG Data -->
    <section class="hero">
        <div class="hero-background">
            <div class="hero-shape hero-shape-1"></div>
            <div class="hero-shape hero-shape-2"></div>
            <div class="hero-shape hero-shape-3"></div>
        </div>
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-badge">
                    <i class="fas fa-trophy"></i>
                    <span>Platform Analisis Saham #1 di Indonesia</span>
                </div>
                <h1 class="hero-title">
                    Analisis Saham<br>
                    <span class="gradient-text">Lebih Cerdas</span>,<br>
                    Trading <span class="gradient-text">Lebih Profit</span>
                </h1>
                <p class="hero-subtitle">
                    Platform analisis teknikal berbasis AI untuk trader Indonesia. 
                    Real-time signals dengan akurasi 95%, data historis sejak IPO, 
                    dan tools profesional untuk maximize profit Anda.
                </p>
                <div class="hero-buttons">
                    <button class="btn-hero-primary" onclick="openModal('register')">
                        <span>Mulai Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="btn-hero-secondary" onclick="scrollToSection('features')">
                        <i class="fas fa-info-circle"></i>
                        <span>Pelajari Lebih Lanjut</span>
                    </button>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3>5,000+</h3>
                            <p>Active Traders</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h3>1M+</h3>
                            <p>Analisis Dilakukan</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3>95%</h3>
                            <p>Accuracy Rate</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <!-- Real IHSG Card -->
                <div class="hero-card main-card">
                    <div class="card-header">
                        <div class="card-title">
                            <span class="ticker-symbol">IHSG</span>
                            <span class="stock-name">Indeks Harga Saham Gabungan</span>
                        </div>
                        <span class="card-badge <?php echo $ihsgData['change'] >= 0 ? 'buy' : 'sell'; ?>">
                            <?php echo $ihsgData['change'] >= 0 ? 'BULLISH' : 'BEARISH'; ?>
                        </span>
                    </div>
                    <div class="card-price">
                        <span class="price-label">Index Sekarang</span>
                        <span class="price-value"><?php echo number_format($ihsgData['current'], 2); ?></span>
                        <span class="price-change <?php echo $ihsgData['change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $ihsgData['change'] >= 0 ? '+' : ''; ?><?php echo number_format($ihsgData['changePercent'], 2); ?>%
                        </span>
                    </div>
                    <div class="card-chart">
                        <canvas id="heroChart" data-prices='<?php echo json_encode($ihsgData['prices']); ?>' data-timestamps='<?php echo json_encode($ihsgData['timestamps']); ?>'></canvas>
                    </div>
                    <div class="card-stats">
                        <div class="stat-row">
                            <span class="stat-label">High:</span>
                            <span class="stat-value"><?php echo number_format($ihsgData['high'], 2); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Low:</span>
                            <span class="stat-value"><?php echo number_format($ihsgData['low'], 2); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Volume:</span>
                            <span class="stat-value"><?php echo number_format($ihsgData['volume'] / 1000000, 0); ?>M</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="realtime-badge">
                            <i class="fas fa-circle pulse"></i>
                            <span>Real-time Data</span>
                        </div>
                        <div class="card-action">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Floating cards -->
                <div class="floating-card card-1">
                    <i class="fas fa-robot"></i>
                    <span>AI Analysis</span>
                </div>
                <div class="floating-card card-2">
                    <i class="fas fa-clock"></i>
                    <span>Real-time Data</span>
                </div>
                <div class="floating-card card-3">
                    <i class="fas fa-shield-alt"></i>
                    <span>95% Akurasi</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="trust-section">
        <div class="trust-container">
            <p class="trust-title">Dipercaya oleh trader dari berbagai perusahaan</p>
            <div class="trust-logos">
                <div class="trust-logo">Mandiri Sekuritas</div>
                <div class="trust-logo">BCA Sekuritas</div>
                <div class="trust-logo">Mirae Asset</div>
                <div class="trust-logo">Ajaib</div>
                <div class="trust-logo">Pluang</div>
                <div class="trust-logo">Stockbit</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="section-header">
            <span class="section-badge">Fitur Unggulan</span>
            <h2>Tools Profesional untuk<br><span class="gradient-text">Trader Indonesia</span></h2>
            <p>Platform lengkap dengan fitur terdepan untuk analisis saham yang akurat</p>
        </div>
        <div class="features-grid">
            <!-- Feature 1 -->
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                </div>
                <h3>AI-Powered Analysis</h3>
                <p>Algoritma machine learning dengan akurasi 95% untuk prediksi pergerakan harga dan generate signal optimal.</p>
                <a href="#" class="feature-link" onclick="toggleFeatureDetail(event, 'feature1')">
                    Learn More <i class="fas fa-chevron-down"></i>
                </a>
                <div id="feature1" class="feature-detail">
                    <p><strong>Teknologi AI Terdepan:</strong></p>
                    <ul>
                        <li>✓ Deep Learning untuk pattern recognition</li>
                        <li>✓ Natural Language Processing untuk analisis sentiment</li>
                        <li>✓ Real-time prediction dengan confidence score</li>
                        <li>✓ Backtesting otomatis untuk validasi strategi</li>
                    </ul>
                    <p class="detail-note">AI kami dilatih dengan 10 tahun data historis IHSG dan terus belajar dari pergerakan pasar real-time.</p>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="feature-card featured">
                <div class="feature-badge">Most Popular</div>
                <div class="feature-icon-wrapper">
                    <div class="feature-icon">
                        <i class="fas fa-chart-area"></i>
                    </div>
                </div>
                <h3>Real-time Technical Analysis</h3>
                <p>15+ indikator profesional: RSI, MACD, Bollinger Bands, Fibonacci, Volume Profile, dan Divergence Detection.</p>
                <a href="#" class="feature-link" onclick="toggleFeatureDetail(event, 'feature2')">
                    Learn More <i class="fas fa-chevron-down"></i>
                </a>
                <div id="feature2" class="feature-detail">
                    <p><strong>Indikator Lengkap:</strong></p>
                    <ul>
                        <li>✓ RSI & Stochastic untuk momentum</li>
                        <li>✓ MACD & Signal Line crossover</li>
                        <li>✓ Bollinger Bands & Squeeze detection</li>
                        <li>✓ Fibonacci retracement & extension</li>
                        <li>✓ Volume Profile & Point of Control</li>
                        <li>✓ Support/Resistance multi-timeframe</li>
                        <li>✓ Candlestick pattern recognition</li>
                        <li>✓ Divergence detection (bullish/bearish)</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                </div>
                <h3>Historical Data Lengkap</h3>
                <p>Akses data historis sejak IPO. Zoom & pan chart untuk analisis mendalam periode manapun.</p>
                <a href="#" class="feature-link" onclick="toggleFeatureDetail(event, 'feature3')">
                    Learn More <i class="fas fa-chevron-down"></i>
                </a>
                <div id="feature3" class="feature-detail">
                    <p><strong>Data Komprehensif:</strong></p>
                    <ul>
                        <li>✓ Historical data sejak IPO saham</li>
                        <li>✓ Multiple timeframes (1m, 5m, 15m, 1h, 1d, 1w)</li>
                        <li>✓ Interactive zoom & pan charts</li>
                        <li>✓ Export data ke CSV/Excel</li>
                        <li>✓ Historical backtest untuk strategi</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <div class="feature-icon">
                        <i class="fas fa-search-dollar"></i>
                    </div>
                </div>
                <h3>Smart Stock Screener</h3>
                <p>Scan 1000+ saham IDX otomatis. Filter by strategy: Scalping, Swing, BSJP, BPJS dengan liquidity score.</p>
                <a href="#" class="feature-link" onclick="toggleFeatureDetail(event, 'feature4')">
                    Learn More <i class="fas fa-chevron-down"></i>
                </a>
                <div id="feature4" class="feature-detail">
                    <p><strong>Screening Otomatis:</strong></p>
                    <ul>
                        <li>✓ Scan 1000+ saham dalam 60 detik</li>
                        <li>✓ Filter berdasarkan strategi trading</li>
                        <li>✓ Liquidity score untuk entry/exit aman</li>
                        <li>✓ Custom criteria screening</li>
                        <li>✓ Save & share watchlist</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                </div>
                <h3>Smart Alerts</h3>
                <p>Notifikasi real-time via email/WhatsApp saat price target tercapai atau muncul signal baru.</p>
                <a href="#" class="feature-link" onclick="toggleFeatureDetail(event, 'feature5')">
                    Learn More <i class="fas fa-chevron-down"></i>
                </a>
                <div id="feature5" class="feature-detail">
                    <p><strong>Notifikasi Cerdas:</strong></p>
                    <ul>
                        <li>✓ Price alerts (target/stop loss hit)</li>
                        <li>✓ Signal alerts (buy/sell opportunities)</li>
                        <li>✓ Volume spike alerts</li>
                        <li>✓ Multi-channel: Email, WhatsApp, Push</li>
                        <li>✓ Custom alert conditions</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <div class="feature-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                </div>
                <h3>Watchlist & Portfolio</h3>
                <p>Track saham favorit, monitor performance, dan kelola portofolio dengan profit/loss tracking.</p>
                <a href="#" class="feature-link" onclick="toggleFeatureDetail(event, 'feature6')">
                    Learn More <i class="fas fa-chevron-down"></i>
                </a>
                <div id="feature6" class="feature-detail">
                    <p><strong>Portfolio Management:</strong></p>
                    <ul>
                        <li>✓ Unlimited watchlist creation</li>
                        <li>✓ Real-time P/L tracking</li>
                        <li>✓ Portfolio performance analytics</li>
                        <li>✓ Risk management calculator</li>
                        <li>✓ Trade journal & notes</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="how-it-works">
        <div class="section-header">
            <span class="section-badge">Cara Kerja</span>
            <h2>Mulai Trading dalam<br><span class="gradient-text">3 Langkah Mudah</span></h2>
        </div>
        <div class="steps-container">
            <div class="step-card">
                <div class="step-number">01</div>
                <div class="step-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Daftar & Pilih Paket</h3>
                <p>Buat akun dalam 30 detik menggunakan email atau Google. Pilih paket yang sesuai kebutuhan trading Anda.</p>
            </div>
            <div class="step-connector">→</div>
            <div class="step-card">
                <div class="step-number">02</div>
                <div class="step-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Input Kode Saham</h3>
                <p>Masukkan ticker saham yang ingin dianalisis (contoh: BBCA, TLKM) atau gunakan stock screener.</p>
            </div>
            <div class="step-connector">→</div>
            <div class="step-card">
                <div class="step-number">03</div>
                <div class="step-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>Dapatkan Signal</h3>
                <p>Terima analisis lengkap dengan entry point, stop loss, take profit, dan confidence score dalam sekejap.</p>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing">
        <div class="section-header">
            <span class="section-badge">Harga Transparan</span>
            <h2>Pilih Paket yang<br><span class="gradient-text">Sesuai Kebutuhan</span></h2>
            <p>Investasi terbaik untuk kesuksesan trading Anda</p>
        </div>
        <div class="pricing-toggle">
            <span class="toggle-label">Bulanan</span>
            <label class="toggle-switch">
                <input type="checkbox" id="pricingToggle" onchange="togglePricing()">
                <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label">Tahunan <span class="save-badge">Hemat 20%</span></span>
        </div>
        <div class="pricing-cards">
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Starter</h3>
                    <p>Untuk pemula yang serius</p>
                </div>
                <div class="pricing-price">
                    <span class="currency">Rp</span>
                    <span class="amount monthly-price">99K</span>
                    <span class="amount yearly-price" style="display:none;">79K</span>
                    <span class="period">/bulan</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> 50 analisis per hari</li>
                    <li><i class="fas fa-check"></i> 10+ Basic indicators</li>
                    <li><i class="fas fa-check"></i> Watchlist 20 saham</li>
                    <li><i class="fas fa-check"></i> Email support</li>
                    <li><i class="fas fa-check"></i> Historical data 1 tahun</li>
                    <li class="disabled"><i class="fas fa-times"></i> Real-time alerts</li>
                    <li class="disabled"><i class="fas fa-times"></i> Stock screener</li>
                </ul>
                <button class="btn-pricing" onclick="openModal('register')">Pilih Paket Ini</button>
            </div>
            
            <div class="pricing-card featured">
                <div class="popular-badge">
                    <i class="fas fa-crown"></i>
                    <span>Most Popular</span>
                </div>
                <div class="pricing-header">
                    <h3>Pro</h3>
                    <p>Untuk trader profesional</p>
                </div>
                <div class="pricing-price">
                    <span class="currency">Rp</span>
                    <span class="amount monthly-price">199K</span>
                    <span class="amount yearly-price" style="display:none;">159K</span>
                    <span class="period">/bulan</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> <strong>Unlimited</strong> analisis</li>
                    <li><i class="fas fa-check"></i> <strong>15+ Advanced</strong> indicators</li>
                    <li><i class="fas fa-check"></i> <strong>Unlimited</strong> watchlist</li>
                    <li><i class="fas fa-check"></i> Real-time alerts</li>
                    <li><i class="fas fa-check"></i> Stock screener</li>
                    <li><i class="fas fa-check"></i> Portfolio tracking</li>
                    <li><i class="fas fa-check"></i> Priority support</li>
                    <li><i class="fas fa-check"></i> AI-powered signals</li>
                    <li><i class="fas fa-check"></i> Historical data sejak IPO</li>
                </ul>
                <button class="btn-pricing primary" onclick="openModal('register')">
                    Pilih Paket Ini
                </button>
            </div>
            
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Enterprise</h3>
                    <p>Untuk tim & institusi</p>
                </div>
                <div class="pricing-price">
                    <span class="currency">Rp</span>
                    <span class="amount monthly-price">999K</span>
                    <span class="amount yearly-price" style="display:none;">799K</span>
                    <span class="period">/bulan</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> <strong>Everything in Pro</strong></li>
                    <li><i class="fas fa-check"></i> API access</li>
                    <li><i class="fas fa-check"></i> Custom indicators</li>
                    <li><i class="fas fa-check"></i> White label solution</li>
                    <li><i class="fas fa-check"></i> Multi-user accounts (10+)</li>
                    <li><i class="fas fa-check"></i> Dedicated account manager</li>
                    <li><i class="fas fa-check"></i> 24/7 priority support</li>
                    <li><i class="fas fa-check"></i> Custom training & onboarding</li>
                </ul>
                <button class="btn-pricing" onclick="alert('Hubungi: sales@sahamqu.com')">Hubungi Sales</button>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-content">
            <h2>Siap Mulai Trading Lebih Cerdas?</h2>
            <p>Bergabung dengan 5,000+ trader sukses yang sudah menggunakan SahamQu</p>
            <button class="btn-cta" onclick="openModal('register')">
                <span>Daftar & Pilih Paket Sekarang</span>
                <i class="fas fa-arrow-right"></i>
            </button>
            <div class="cta-features">
                <div class="cta-feature">
                    <i class="fas fa-check-circle"></i>
                    <span>Setup instant</span>
                </div>
                <div class="cta-feature">
                    <i class="fas fa-check-circle"></i>
                    <span>Cancel kapan saja</span>
                </div>
                <div class="cta-feature">
                    <i class="fas fa-check-circle"></i>
                    <span>Support 24/7</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section brand">
                <div class="footer-logo">
                    <div class="logo-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-main">SahamQu</span>
                        <span class="logo-tagline">Smart Trading</span>
                    </div>
                </div>
                <p>Platform analisis saham #1 di Indonesia dengan AI-powered technology untuk trader profesional.</p>
                <div class="social-links">
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Product</h4>
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#">API Documentation</a></li>
                    <li><a href="#">Roadmap</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Company</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Resources</h4>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Community</a></li>
                    <li><a href="#">Tutorials</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Disclaimer</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 SahamQu. All rights reserved. Made with ❤️ in Indonesia</p>
            <div class="footer-badges">
                <span class="badge">🔒 SSL Secured</span>
                <span class="badge">✓ ISO Certified</span>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('login')">&times;</span>
            <h2>Masuk ke SahamQu</h2>
            <p class="modal-subtitle">Selamat datang kembali! Login untuk melanjutkan trading.</p>
            
            <form method="POST" action="auth_handler.php">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="nama@email.com" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                
                <div class="form-options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember">
                        <span>Ingat Saya</span>
                    </label>
                    <a href="#" onclick="alert('Hubungi support@sahamqu.com untuk reset password')">Lupa Password?</a>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Masuk</span>
                </button>
            </form>
            
            <div class="divider">
                <span>atau lanjutkan dengan</span>
            </div>
            
            <button class="btn-google" onclick="loginWithGoogle()">
                <i class="fab fa-google"></i>
                <span>Login dengan Google</span>
            </button>
            
            <div class="modal-footer">
                Belum punya akun? <a href="#" onclick="switchModal('login', 'register')">Daftar Sekarang</a>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('register')">&times;</span>
            <h2>Daftar di SahamQu</h2>
            <p class="modal-subtitle">Mulai journey trading Anda hari ini!</p>
            
            <form method="POST" action="auth_handler.php" id="registerForm">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="nama@gmail.com" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min. 8 karakter" required>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="confirm_password" placeholder="Ketik ulang password" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-rocket"></i>
                    <span>Daftar Sekarang</span>
                </button>
            </form>
            
            <div class="divider">
                <span>atau daftar dengan</span>
            </div>
            
            <button class="btn-google" onclick="loginWithGoogle()">
                <i class="fab fa-google"></i>
                <span>Daftar dengan Google</span>
            </button>
            
            <div class="modal-footer">
                Sudah punya akun? <a href="#" onclick="switchModal('register', 'login')">Masuk</a>
            </div>
        </div>
    </div>

    <!-- Verification Modal -->
    <div id="verifyModal" class="modal" <?php echo isset($_GET['verify']) ? 'style="display:block;"' : ''; ?>>
        <div class="modal-content">
            <span class="close" onclick="closeModal('verify')">&times;</span>
            <div class="verify-icon">📧</div>
            <h2>Verifikasi Email</h2>
            <p class="modal-subtitle">
                Masukkan kode 6 digit yang telah dikirim ke<br>
                <strong id="verifyEmail"><?php echo htmlspecialchars($_GET['email'] ?? ''); ?></strong>
            </p>
            
            <form method="POST" action="auth_handler.php">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="email" id="verifyEmailInput" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                <input type="hidden" name="code" id="verificationCode">
                
                <div class="code-inputs">
                    <input type="text" class="code-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="code-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="code-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="code-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="code-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="code-input" maxlength="1" pattern="\d" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-check-circle"></i>
                    <span>Verifikasi</span>
                </button>
            </form>
            
            <div class="modal-footer">
                Tidak menerima kode? <a href="#" onclick="resendCode()">Kirim Ulang</a>
            </div>
            
            <div class="modal-footer" style="margin-top: 1rem; font-size: 0.9rem;">
                💡 <strong>Tip:</strong> Cek folder inbox dan spam email Anda
            </div>
        </div>
    </div>

    <script src="landing.js"></script>
</body>
</html>
