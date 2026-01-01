<?php
/**
 * Fetch and analyze stock news with sentiment analysis
 */

function fetchStockNews($ticker) {
    // Use Google News RSS or alternative news API
    $newsData = fetchFromGoogleNews($ticker);
    
    // Analyze sentiment and impact
    foreach ($newsData as &$news) {
        $news['sentiment'] = analyzeSentiment($news['title'] . ' ' . $news['summary']);
        $news['impact'] = generateImpactAnalysis($news['title'], $news['summary'], $news['sentiment']);
    }
    
    return $newsData;
}

function fetchFromGoogleNews($ticker) {
    // Google News RSS Feed
    $searchQuery = urlencode($ticker . " saham");
    $url = "https://news.google.com/rss/search?q={$searchQuery}+when:7d&hl=id&gl=ID&ceid=ID:id";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0'
        ]
    ];
    
    $context = stream_context_create($options);
    $rss = @file_get_contents($url, false, $context);
    
    if ($rss === FALSE) {
        return getMockNews($ticker);
    }
    
    $xml = @simplexml_load_string($rss);
    
    if ($xml === FALSE) {
        return getMockNews($ticker);
    }
    
    $newsArray = [];
    $count = 0;
    
    foreach ($xml->channel->item as $item) {
        if ($count >= 6) break;
        
        $pubDate = strtotime((string)$item->pubDate);
        $title = (string)$item->title;
        $link = (string)$item->link;
        
        // Extract summary from description
        $description = strip_tags((string)$item->description);
        $summary = substr($description, 0, 200) . '...';
        
        $newsArray[] = [
            'title' => $title,
            'summary' => $summary,
            'date' => date('d M Y', $pubDate),
            'url' => $link,
            'sentiment' => 'neutral',
            'impact' => ''
        ];
        
        $count++;
    }
    
    return $newsArray;
}

function analyzeSentiment($text) {
    $text = strtolower($text);
    
    // Positive keywords
    $positiveWords = ['naik', 'meningkat', 'tumbuh', 'profit', 'laba', 'ekspansi', 'positif', 
                      'rebound', 'menguat', 'optimis', 'bertumbuh', 'dividen', 'akuisisi',
                      'investasi', 'inovasi', 'kesepakatan', 'kontrak', 'kinerja baik'];
    
    // Negative keywords
    $negativeWords = ['turun', 'menurun', 'rugi', 'kerugian', 'negatif', 'anjlok', 'jatuh',
                      'melemah', 'pesimis', 'krisis', 'masalah', 'investigasi', 'skandal',
                      'gagal', 'penurunan', 'defisit', 'hutang', 'debt'];
    
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
    
    if ($positiveCount > $negativeCount) return 'positive';
    if ($negativeCount > $positiveCount) return 'negative';
    return 'neutral';
}

function generateImpactAnalysis($title, $summary, $sentiment) {
    $impacts = [
        'positive' => [
            'Berita ini berpotensi mendorong harga naik karena sentimen pasar positif.',
            'Kemungkinan akan meningkatkan minat beli investor jangka pendek.',
            'Dapat memicu rally harga jika volume mengikuti.',
            'Fundamental yang membaik mendukung trend bullish.',
        ],
        'negative' => [
            'Berita negatif ini dapat memberi tekanan jual pada saham.',
            'Investor mungkin akan wait-and-see atau melakukan profit taking.',
            'Risiko koreksi harga meningkat dalam jangka pendek.',
            'Perlu waspada terhadap potential downside risk.',
        ],
        'neutral' => [
            'Berita ini kemungkinan tidak berdampak signifikan pada pergerakan harga.',
            'Market akan fokus pada data teknikal dan volume trading.',
            'Tetap monitor perkembangan selanjutnya.',
        ]
    ];
    
    $impactList = $impacts[$sentiment];
    return $impactList[array_rand($impactList)];
}

function calculateSentimentScore($newsData) {
    $score = 0;
    
    foreach ($newsData as $news) {
        if ($news['sentiment'] === 'positive') $score += 1;
        elseif ($news['sentiment'] === 'negative') $score -= 1;
    }
    
    return $score;
}

function getMockNews($ticker) {
    // Fallback mock data if RSS fails
    return [
        [
            'title' => "Saham {$ticker} Mencatatkan Volume Tinggi Hari Ini",
            'summary' => "Perdagangan saham {$ticker} mencatat peningkatan volume signifikan yang mengindikasikan minat investor yang kuat...",
            'date' => date('d M Y'),
            'url' => '',
            'sentiment' => 'positive',
            'impact' => 'Volume tinggi dapat mengindikasikan accumulation phase dari investor institusi.'
        ],
        [
            'title' => "Analisis Teknikal {$ticker}: Level Support Kunci",
            'summary' => "Pergerakan {$ticker} saat ini menguji area support penting di level psikologis...",
            'date' => date('d M Y', strtotime('-1 day')),
            'url' => '',
            'sentiment' => 'neutral',
            'impact' => 'Pantau reaksi harga di area support untuk menentukan arah selanjutnya.'
        ]
    ];
}
?>
