/**
 * ApexCharts with Candlestick Chart
 */

let priceChart, volumeChart, rsiChart, macdChart, stochChart;

function showChart(chartType) {
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    document.getElementById('price-chart-container').style.display = 'none';
    document.getElementById('volume-chart-container').style.display = 'none';
    document.getElementById('rsi-chart-container').style.display = 'none';
    document.getElementById('macd-chart-container').style.display = 'none';
    document.getElementById('stoch-chart-container').style.display = 'none';
    
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

document.addEventListener('DOMContentLoaded', function() {
    if (typeof chartData !== 'undefined') {
        initPriceChart();
    }
});

function initPriceChart() {
    // Prepare candlestick data
    const candlestickData = chartData.prices.map((p, idx) => {
        return {
            x: new Date(chartData.dates[idx]),
            y: [p.o, p.h, p.l, p.c]
        };
    });
    
    // Prepare MA data with timestamps
    const ma20Data = chartData.ma20.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const ma50Data = chartData.ma50.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const ema9Data = chartData.ema9.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const bbUpperData = chartData.bb_upper.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const bbLowerData = chartData.bb_lower.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const options = {
        series: [
            {
                name: 'Candlestick',
                type: 'candlestick',
                data: candlestickData
            },
            {
                name: 'MA20',
                type: 'line',
                data: ma20Data
            },
            {
                name: 'MA50',
                type: 'line',
                data: ma50Data
            },
            {
                name: 'EMA9',
                type: 'line',
                data: ema9Data
            },
            {
                name: 'BB Upper',
                type: 'line',
                data: bbUpperData
            },
            {
                name: 'BB Lower',
                type: 'line',
                data: bbLowerData
            }
        ],
        chart: {
            height: 550,
            type: 'candlestick',
            background: '#1a1a1a',
            foreColor: '#ffffff',
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
                    reset: true
                }
            },
            animations: {
                enabled: true
            }
        },
        plotOptions: {
            candlestick: {
                colors: {
                    upward: '#10b981',
                    downward: '#ef4444'
                },
                wick: {
                    useFillColor: true
                }
            }
        },
        colors: ['#667eea', '#f59e0b', '#3b82f6', '#22c55e', '#ef4444', '#10b981'],
        stroke: {
            width: [1, 2, 2, 2, 1, 1],
            curve: 'smooth',
            dashArray: [0, 0, 0, 0, 3, 3]
        },
        title: {
            text: tickerSymbol + ' - Candlestick Chart dengan Indikator',
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 'bold',
                color: '#ffffff'
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: {
                    colors: '#ffffff'
                }
            }
        },
        yaxis: {
            tooltip: {
                enabled: true
            },
            labels: {
                formatter: function(value) {
                    return 'Rp ' + Math.round(value).toLocaleString('id-ID');
                },
                style: {
                    colors: '#ffffff'
                }
            }
        },
        tooltip: {
            theme: 'dark',
            shared: true,
            custom: function({seriesIndex, dataPointIndex, w}) {
                const data = w.globals.initialSeries[seriesIndex].data[dataPointIndex];
                
                if (seriesIndex === 0) {
                    // Candlestick tooltip
                    const o = data.y[0];
                    const h = data.y[1];
                    const l = data.y[2];
                    const c = data.y[3];
                    
                    return '<div class="apexcharts-tooltip-candlestick">' +
                        '<div>Open: <span>Rp ' + Math.round(o).toLocaleString('id-ID') + '</span></div>' +
                        '<div>High: <span>Rp ' + Math.round(h).toLocaleString('id-ID') + '</span></div>' +
                        '<div>Low: <span>Rp ' + Math.round(l).toLocaleString('id-ID') + '</span></div>' +
                        '<div>Close: <span>Rp ' + Math.round(c).toLocaleString('id-ID') + '</span></div>' +
                        '</div>';
                }
                
                return '<div class="apexcharts-tooltip-box">' +
                    w.globals.initialSeries[seriesIndex].name + ': <strong>Rp ' + Math.round(data.y).toLocaleString('id-ID') + '</strong>' +
                    '</div>';
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            labels: {
                colors: '#ffffff'
            }
        },
        grid: {
            borderColor: '#333333',
            strokeDashArray: 3,
            xaxis: {
                lines: {
                    show: true
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            }
        }
    };

    priceChart = new ApexCharts(document.querySelector("#priceChart"), options);
    priceChart.render();
}

function initVolumeChart() {
    const volumeData = chartData.volume.map((vol, idx) => {
        const prevClose = idx > 0 ? chartData.prices[idx - 1].c : chartData.prices[0].c;
        const currClose = chartData.prices[idx].c;
        const color = currClose >= prevClose ? '#10b981' : '#ef4444';
        
        return {
            x: new Date(chartData.dates[idx]),
            y: vol,
            fillColor: color
        };
    });
    
    const options = {
        series: [{
            name: 'Volume',
            data: volumeData
        }],
        chart: {
            type: 'bar',
            height: 450,
            background: '#1a1a1a',
            foreColor: '#ffffff',
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                columnWidth: '80%',
                colors: {
                    ranges: [{
                        from: 0,
                        to: 999999999999,
                        color: '#10b981'
                    }]
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        title: {
            text: tickerSymbol + ' - Volume Trading',
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 'bold',
                color: '#ffffff'
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: {
                    colors: '#ffffff'
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function(value) {
                    if (value >= 1000000000) return (value / 1000000000).toFixed(1) + 'B';
                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                    if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                    return value;
                },
                style: {
                    colors: '#ffffff'
                }
            }
        },
        grid: {
            borderColor: '#333333'
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) {
                    return value.toLocaleString('id-ID');
                }
            }
        }
    };

    volumeChart = new ApexCharts(document.querySelector("#volumeChart"), options);
    volumeChart.render();
}

function initRSIChart() {
    const validRSI = chartData.rsi.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const options = {
        series: [{
            name: 'RSI',
            data: validRSI
        }],
        chart: {
            height: 450,
            type: 'line',
            background: '#1a1a1a',
            foreColor: '#ffffff',
            toolbar: {
                show: true
            }
        },
        colors: ['#667eea'],
        stroke: {
            width: 3,
            curve: 'smooth'
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3
            }
        },
        title: {
            text: tickerSymbol + ' - RSI (14)',
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 'bold',
                color: '#ffffff'
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: {
                    colors: '#ffffff'
                }
            }
        },
        yaxis: {
            min: 0,
            max: 100,
            tickAmount: 5,
            labels: {
                style: {
                    colors: '#ffffff'
                }
            }
        },
        annotations: {
            yaxis: [
                {
                    y: 70,
                    borderColor: '#ef4444',
                    strokeDashArray: 5,
                    label: {
                        text: 'Overbought',
                        style: {
                            color: '#fff',
                            background: '#ef4444'
                        }
                    }
                },
                {
                    y: 50,
                    borderColor: '#6c757d',
                    strokeDashArray: 2
                },
                {
                    y: 30,
                    borderColor: '#10b981',
                    strokeDashArray: 5,
                    label: {
                        text: 'Oversold',
                        style: {
                            color: '#fff',
                            background: '#10b981'
                        }
                    }
                }
            ]
        },
        grid: {
            borderColor: '#333333'
        },
        tooltip: {
            theme: 'dark'
        }
    };

    rsiChart = new ApexCharts(document.querySelector("#rsiChart"), options);
    rsiChart.render();
}

function initMACDChart() {
    const validMACD = chartData.macd.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const validSignal = chartData.macd_signal.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const validHist = chartData.macd_hist.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val, fillColor: val > 0 ? '#10b981' : '#ef4444'} : null;
    }).filter(v => v !== null);
    
    const options = {
        series: [
            {
                name: 'MACD',
                type: 'line',
                data: validMACD
            },
            {
                name: 'Signal',
                type: 'line',
                data: validSignal
            },
            {
                name: 'Histogram',
                type: 'column',
                data: validHist
            }
        ],
        chart: {
            height: 450,
            type: 'line',
            background: '#1a1a1a',
            foreColor: '#ffffff',
            toolbar: {
                show: true
            }
        },
        colors: ['#3b82f6', '#f59e0b', '#10b981'],
        stroke: {
            width: [3, 3, 0],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '60%'
            }
        },
        title: {
            text: tickerSymbol + ' - MACD (12,26,9)',
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 'bold',
                color: '#ffffff'
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: {
                    colors: '#ffffff'
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function(value) {
                    return value.toFixed(4);
                },
                style: {
                    colors: '#ffffff'
                }
            }
        },
        grid: {
            borderColor: '#333333'
        },
        tooltip: {
            theme: 'dark',
            shared: true
        },
        legend: {
            labels: {
                colors: '#ffffff'
            }
        }
    };

    macdChart = new ApexCharts(document.querySelector("#macdChart"), options);
    macdChart.render();
}

function initStochChart() {
    const validK = chartData.stoch_k.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const validD = chartData.stoch_d.map((val, idx) => {
        return val !== null ? {x: new Date(chartData.dates[idx]), y: val} : null;
    }).filter(v => v !== null);
    
    const options = {
        series: [
            {
                name: '%K',
                data: validK
            },
            {
                name: '%D',
                data: validD
            }
        ],
        chart: {
            height: 450,
            type: 'line',
            background: '#1a1a1a',
            foreColor: '#ffffff',
            toolbar: {
                show: true
            }
        },
        colors: ['#3b82f6', '#f59e0b'],
        stroke: {
            width: 3,
            curve: 'smooth'
        },
        title: {
            text: tickerSymbol + ' - Stochastic (14,3,3)',
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 'bold',
                color: '#ffffff'
            }
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: {
                    colors: '#ffffff'
                }
            }
        },
        yaxis: {
            min: 0,
            max: 100,
            tickAmount: 5,
            labels: {
                style: {
                    colors: '#ffffff'
                }
            }
        },
        annotations: {
            yaxis: [
                {
                    y: 80,
                    borderColor: '#ef4444',
                    strokeDashArray: 5,
                    label: {
                        text: 'Overbought',
                        style: {
                            color: '#fff',
                            background: '#ef4444'
                        }
                    }
                },
                {
                    y: 20,
                    borderColor: '#10b981',
                    strokeDashArray: 5,
                    label: {
                        text: 'Oversold',
                        style: {
                            color: '#fff',
                            background: '#10b981'
                        }
                    }
                }
            ]
        },
        grid: {
            borderColor: '#333333'
        },
        tooltip: {
            theme: 'dark',
            shared: true
        },
        legend: {
            labels: {
                colors: '#ffffff'
            }
        }
    };

    stochChart = new ApexCharts(document.querySelector("#stochChart"), options);
    stochChart.render();
}
