/**
 * Advanced Chart Rendering - FIXED VERSION
 * Compatible with Chart.js 4.x
 */

let priceChart, volumeChart, rsiChart, macdChart, stochChart;

// Tab switching function
function showChart(chartType) {
    // Update tab buttons
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Hide all chart containers
    document.getElementById('price-chart-container').style.display = 'none';
    document.getElementById('volume-chart-container').style.display = 'none';
    document.getElementById('rsi-chart-container').style.display = 'none';
    document.getElementById('macd-chart-container').style.display = 'none';
    document.getElementById('stoch-chart-container').style.display = 'none';
    
    // Show selected chart
    switch(chartType) {
        case 'price':
            document.getElementById('price-chart-container').style.display = 'block';
            if (!priceChart) initPriceChart();
            break;
        case 'volume':
            document.getElementById('volume-chart-container').style.display = 'block';
            if (!volumeChart) initVolumeChart();
            break;
        case 'rsi':
            document.getElementById('rsi-chart-container').style.display = 'block';
            if (!rsiChart) initRSIChart();
            break;
        case 'macd':
            document.getElementById('macd-chart-container').style.display = 'block';
            if (!macdChart) initMACDChart();
            break;
        case 'stoch':
            document.getElementById('stoch-chart-container').style.display = 'block';
            if (!stochChart) initStochChart();
            break;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof chartData === 'undefined') {
        console.error('Chart data not found');
        return;
    }
    
    initPriceChart();
});

// Price Chart with Close Price and Moving Averages
function initPriceChart() {
    const ctx = document.getElementById('priceChart');
    if (!ctx) return;
    
    // Prepare close price data
    const closeData = chartData.dates.map((date, idx) => {
        const price = chartData.prices[idx];
        return {
            x: date,
            y: price.c
        };
    });
    
    const datasets = [
        {
            label: 'Close Price',
            data: closeData,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.1,
            pointRadius: 0,
            pointHoverRadius: 5
        },
        {
            label: 'BB Upper',
            data: chartData.bb_upper.map((val, idx) => ({ x: chartData.dates[idx], y: val })).filter(p => p.y !== null),
            borderColor: 'rgba(239, 68, 68, 0.6)',
            borderWidth: 1,
            borderDash: [5, 5],
            fill: false,
            pointRadius: 0
        },
        {
            label: 'SMA20 (BB Middle)',
            data: chartData.ma20.map((val, idx) => ({ x: chartData.dates[idx], y: val })).filter(p => p.y !== null),
            borderColor: '#f59e0b',
            borderWidth: 2,
            fill: false,
            pointRadius: 0
        },
        {
            label: 'BB Lower',
            data: chartData.bb_lower.map((val, idx) => ({ x: chartData.dates[idx], y: val })).filter(p => p.y !== null),
            borderColor: 'rgba(16, 185, 129, 0.6)',
            borderWidth: 1,
            borderDash: [5, 5],
            fill: false,
            pointRadius: 0
        },
        {
            label: 'MA50',
            data: chartData.ma50.map((val, idx) => ({ x: chartData.dates[idx], y: val })).filter(p => p.y !== null),
            borderColor: '#3b82f6',
            borderWidth: 2,
            fill: false,
            pointRadius: 0
        },
        {
            label: 'EMA9',
            data: chartData.ema9.map((val, idx) => ({ x: chartData.dates[idx], y: val })).filter(p => p.y !== null),
            borderColor: '#22c55e',
            borderWidth: 2,
            fill: false,
            pointRadius: 0
        }
    ];
    
    priceChart = new Chart(ctx, {
        type: 'line',
        data: { datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                title: {
                    display: true,
                    text: `${tickerSymbol} - Harga & Moving Averages + Bollinger Bands`,
                    font: { size: 16, weight: 'bold' },
                    color: '#667eea'
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'category',
                    grid: { display: false },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 }
                    }
                },
                y: {
                    position: 'right',
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
}

// Volume Chart
function initVolumeChart() {
    const ctx = document.getElementById('volumeChart');
    if (!ctx) return;
    
    const volumeColors = chartData.volume.map((vol, idx) => {
        if (idx === 0) return 'rgba(102, 126, 234, 0.7)';
        const prevClose = chartData.prices[idx - 1]?.c || 0;
        const currClose = chartData.prices[idx]?.c || 0;
        return currClose >= prevClose ? 'rgba(16, 185, 129, 0.8)' : 'rgba(239, 68, 68, 0.8)';
    });
    
    volumeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.dates,
            datasets: [{
                label: 'Volume',
                data: chartData.volume,
                backgroundColor: volumeColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `${tickerSymbol} - Volume Trading (Hijau = Naik, Merah = Turun)`,
                    font: { size: 16, weight: 'bold' },
                    color: '#667eea'
                },
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return 'Volume: ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 }
                    }
                },
                y: {
                    position: 'right',
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000000) return (value / 1000000000).toFixed(1) + 'B';
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                            return value;
                        },
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
}

// RSI Chart
function initRSIChart() {
    const ctx = document.getElementById('rsiChart');
    if (!ctx) return;
    
    const validRSI = chartData.rsi.map((val, idx) => ({
        x: chartData.dates[idx],
        y: val
    })).filter(point => point.y !== null);
    
    rsiChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'RSI (14)',
                    data: validRSI,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.2)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHoverRadius: 5
                },
                {
                    label: 'Overbought (70)',
                    data: chartData.dates.map(date => ({ x: date, y: 70 })),
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'Neutral (50)',
                    data: chartData.dates.map(date => ({ x: date, y: 50 })),
                    borderColor: '#6c757d',
                    borderWidth: 1,
                    borderDash: [2, 2],
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'Oversold (30)',
                    data: chartData.dates.map(date => ({ x: date, y: 30 })),
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                title: {
                    display: true,
                    text: `${tickerSymbol} - RSI (Relative Strength Index)`,
                    font: { size: 16, weight: 'bold' },
                    color: '#667eea'
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12
                }
            },
            scales: {
                x: {
                    type: 'category',
                    grid: { display: false },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 }
                    }
                },
                y: {
                    min: 0,
                    max: 100,
                    position: 'right',
                    ticks: {
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
}

// MACD Chart
function initMACDChart() {
    const ctx = document.getElementById('macdChart');
    if (!ctx) return;
    
    const validMACD = chartData.macd.map((val, idx) => ({
        x: chartData.dates[idx],
        y: val
    })).filter(point => point.y !== null);
    
    const validSignal = chartData.macd_signal.map((val, idx) => ({
        x: chartData.dates[idx],
        y: val
    })).filter(point => point.y !== null);
    
    const histColors = chartData.macd_hist.map(val => 
        val > 0 ? 'rgba(16, 185, 129, 0.8)' : 'rgba(239, 68, 68, 0.8)'
    );
    
    macdChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.dates,
            datasets: [
                {
                    label: 'MACD Histogram',
                    data: chartData.macd_hist,
                    backgroundColor: histColors,
                    borderWidth: 0,
                    type: 'bar'
                },
                {
                    label: 'MACD Line',
                    data: validMACD,
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    fill: false,
                    type: 'line',
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    tension: 0.3
                },
                {
                    label: 'Signal Line',
                    data: validSignal,
                    borderColor: '#f59e0b',
                    borderWidth: 2,
                    fill: false,
                    type: 'line',
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                title: {
                    display: true,
                    text: `${tickerSymbol} - MACD (12, 26, 9)`,
                    font: { size: 16, weight: 'bold' },
                    color: '#667eea'
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toFixed(4);
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'category',
                    grid: { display: false },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 }
                    }
                },
                y: {
                    position: 'right',
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(4);
                        },
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
}

// Stochastic Chart
function initStochChart() {
    const ctx = document.getElementById('stochChart');
    if (!ctx) return;
    
    const validK = chartData.stoch_k.map((val, idx) => ({
        x: chartData.dates[idx],
        y: val
    })).filter(point => point.y !== null);
    
    const validD = chartData.stoch_d.map((val, idx) => ({
        x: chartData.dates[idx],
        y: val
    })).filter(point => point.y !== null);
    
    stochChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: '%K (Fast)',
                    data: validK,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHoverRadius: 5
                },
                {
                    label: '%D (Slow)',
                    data: validD,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHoverRadius: 5
                },
                {
                    label: 'Overbought (80)',
                    data: chartData.dates.map(date => ({ x: date, y: 80 })),
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'Oversold (20)',
                    data: chartData.dates.map(date => ({ x: date, y: 20 })),
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                title: {
                    display: true,
                    text: `${tickerSymbol} - Stochastic Oscillator (14, 3, 3)`,
                    font: { size: 16, weight: 'bold' },
                    color: '#667eea'
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12
                }
            },
            scales: {
                x: {
                    type: 'category',
                    grid: { display: false },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 }
                    }
                },
                y: {
                    min: 0,
                    max: 100,
                    position: 'right',
                    ticks: {
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
}
