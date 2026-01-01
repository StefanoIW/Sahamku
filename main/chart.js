// Render candlestick chart with moving averages
document.addEventListener('DOMContentLoaded', function() {
    if (typeof chartData === 'undefined') {
        return;
    }
    
    const ctx = document.getElementById('priceChart').getContext('2d');
    
    // Prepare datasets
    const datasets = [
        {
            label: 'Harga (Candlestick)',
            data: chartData.prices,
            type: 'candlestick',
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
        },
        {
            label: 'MA20',
            data: chartData.ma20.map((val, idx) => ({
                x: chartData.dates[idx],
                y: val
            })),
            type: 'line',
            borderColor: '#3b82f6',
            borderWidth: 2,
            fill: false,
            pointRadius: 0,
        },
        {
            label: 'MA50',
            data: chartData.ma50.map((val, idx) => ({
                x: chartData.dates[idx],
                y: val
            })),
            type: 'line',
            borderColor: '#f59e0b',
            borderWidth: 2,
            fill: false,
            pointRadius: 0,
        },
        {
            label: 'EMA9',
            data: chartData.ema9.map((val, idx) => ({
                x: chartData.dates[idx],
                y: val
            })),
            type: 'line',
            borderColor: '#22c55e',
            borderWidth: 2,
            fill: false,
            pointRadius: 0,
        }
    ];
    
    // Filter out null values
    datasets.forEach(dataset => {
        if (dataset.type === 'line') {
            dataset.data = dataset.data.filter(point => point.y !== null);
        }
    });
    
    const chart = new Chart(ctx, {
        type: 'candlestick',
        data: {
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        displayFormats: {
                            day: 'MMM DD'
                        }
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    position: 'right',
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
});
