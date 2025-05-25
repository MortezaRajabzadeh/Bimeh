// متغیرهای سراسری برای نگهداری چارت‌ها
let genderChart, geoChart, financialChart, monthlyChart, criteriaChart, doubleDonutChart, yearlyFlowChart;

// خواندن داده‌ها از JSON
function getChartData() {
    const dataElement = document.getElementById('chart-data');
    if (dataElement) {
        try {
            return JSON.parse(dataElement.textContent);
        } catch (e) {
            console.error('Error parsing chart data:', e);
            return null;
        }
    }
    return null;
}

// تابع helper برای destroy امن چارت‌ها
function safeDestroyChart(chart, chartName) {
    if (chart && typeof chart.destroy === 'function') {
        try {
            chart.destroy();
            console.log(`🗑️ ${chartName} safely destroyed`);
            return null;
        } catch (e) {
            console.warn(`⚠️ خطا در destroy ${chartName}:`, e);
            return null;
        }
    }
    return null;
}

// اتصال event listenerها
document.addEventListener('DOMContentLoaded', function() {
    const data = getChartData();
    if (data && window.dashboardCharts) {
        window.dashboardCharts.initializeAllCharts(data);
    }
});

// Event listener برای آپدیت Livewire
document.addEventListener('livewire:updated', function() {
    setTimeout(function() {
        const data = getChartData();
        if (data && window.dashboardCharts) {
            window.dashboardCharts.updateAllCharts(data);
        }
    }, 200);
});

// Event listener مخصوص برای refresh چارت‌ها
document.addEventListener('refreshAllCharts', function() {
    setTimeout(function() {
        const data = getChartData();
        if (data && window.dashboardCharts) {
            window.dashboardCharts.updateAllCharts(data);
        }
    }, 100);
});

// تابع برای حفظ تنظیمات پیش‌فرض چارت‌ها
function getDefaultChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 500,
            easing: 'easeInOutQuart'
        },
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: {
                        family: 'IRANSans, Tahoma, Arial, sans-serif',
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#ddd',
                borderWidth: 1,
                cornerRadius: 6,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('fa-IR').format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        }
    };
}

// تابع برای ایجاد نمودار جنسیتی
function createGenderChart(maleCount, femaleCount) {
    const ctx = document.getElementById('genderDonut');
    if (!ctx) {
        console.warn('⚠️ Canvas element genderDonut not found');
        return;
    }
    
    // اطمینان از destroy چارت قبلی
    genderChart = safeDestroyChart(genderChart, 'genderChart');
    
    genderChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['مرد', 'زن'],
            datasets: [{
                data: [Number(maleCount), Number(femaleCount)],
                backgroundColor: ['#3b82f6', '#10b981'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            ...getDefaultChartOptions(),
            cutout: '70%',
            plugins: {
                ...getDefaultChartOptions().plugins,
                legend: {
                    display: false
                }
            }
        }
    });
}

// تابع برای ایجاد نمودار جغرافیایی
function createGeoChart(geoLabels, geoDataMale, geoDataFemale, geoDataDeprived) {
    const ctx = document.getElementById('geoBarLineChart');
    if (!ctx) {
        console.warn('⚠️ Canvas element geoBarLineChart not found');
        return;
    }
    
    // اطمینان از destroy چارت قبلی
    geoChart = safeDestroyChart(geoChart, 'geoChart');
    
    geoChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: geoLabels || [],
            datasets: [
                {
                    label: 'مرد',
                    data: geoDataMale || [],
                    backgroundColor: '#3b82f6',
                    borderRadius: 4,
                    stack: 'combined'
                },
                {
                    label: 'زن',
                    data: geoDataFemale || [],
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                    stack: 'combined'
                },
                {
                    label: 'افراد محروم',
                    data: geoDataDeprived || [],
                    type: 'line',
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#ef4444',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            ...getDefaultChartOptions(),
            scales: {
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'IRANSans'
                        }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fa-IR').format(value);
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fa-IR').format(value);
                        }
                    }
                }
            }
        }
    });
}

// تابع برای ایجاد نمودار مالی
function createFinancialChart(financialData) {
    const ctx = document.getElementById('doubleDonut');
    if (!ctx) {
        console.warn('⚠️ Canvas element doubleDonut not found');
        return;
    }
    
    // اطمینان از destroy چارت قبلی
    doubleDonutChart = safeDestroyChart(doubleDonutChart, 'doubleDonutChart');
    
    doubleDonutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['حق بیمه', 'خسارات'],
            datasets: [{
                data: [
                    Number(financialData?.premiums || 0),
                    Number(financialData?.claims || 0)
                ],
                backgroundColor: ['#10b981', '#f97316'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            ...getDefaultChartOptions(),
            cutout: '70%',
            plugins: {
                ...getDefaultChartOptions().plugins,
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            return `${context.label}: ${new Intl.NumberFormat('fa-IR').format(value)}`;
                        }
                    }
                }
            }
        }
    });
}

// تابع برای ایجاد نمودار ماهانه
function createMonthlyChart(monthlyData) {
    const ctx = document.getElementById('monthlyClaimsChart');
    if (!ctx) {
        console.warn('⚠️ Canvas element monthlyClaimsChart not found');
        return;
    }
    
    // اطمینان از destroy چارت قبلی
    monthlyChart = safeDestroyChart(monthlyChart, 'monthlyChart');
    
    monthlyChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['بودجه', 'حق بیمه', 'خسارات'],
            datasets: [{
                data: [
                    Number(monthlyData?.budget || 0),
                    Number(monthlyData?.premiums || 0),
                    Number(monthlyData?.claims || 0)
                ],
                backgroundColor: ['#8b5cf6', '#10b981', '#f97316'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            ...getDefaultChartOptions(),
            cutout: '70%',
            plugins: {
                ...getDefaultChartOptions().plugins,
                legend: {
                    display: false
                }
            }
        }
    });
}

// تابع برای ایجاد نمودار معیارها
function createCriteriaChart(criteriaData) {
    const ctx = document.getElementById('criteriaBarChart');
    if (!ctx) {
        console.warn('⚠️ Canvas element criteriaBarChart not found');
        return;
    }
    
    // اطمینان از destroy چارت قبلی
    criteriaChart = safeDestroyChart(criteriaChart, 'criteriaChart');
    
    criteriaChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: (criteriaData || []).map(item => item.name),
            datasets: [{
                label: 'تعداد',
                data: (criteriaData || []).map(item => item.count),
                backgroundColor: (criteriaData || []).map(item => item.color),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            ...getDefaultChartOptions(),
            indexAxis: 'y',
            plugins: {
                ...getDefaultChartOptions().plugins,
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fa-IR').format(value);
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'IRANSans'
                        }
                    }
                }
            }
        }
    });
}

// چارت جریان مالی ساده
function createYearlyFlowChart(yearlyData) {
    const ctx = document.getElementById('monthlyClaimsFlowChart');
    if (!ctx) {
        console.warn('⚠️ Canvas element monthlyClaimsFlowChart not found');
        return;
    }
    if (!yearlyData) {
        console.warn('⚠️ No yearly data provided');
        return;
    }
    
    // اطمینان از destroy چارت قبلی
    yearlyFlowChart = safeDestroyChart(yearlyFlowChart, 'yearlyFlowChart');
    
    const monthNames = yearlyData.map(item => item.monthName || '');
    const premiums = yearlyData.map(item => item.premiums || 0);
    const claims = yearlyData.map(item => item.claims || 0);
    const budget = yearlyData.map(item => item.budget || 0);
    
    yearlyFlowChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthNames,
            datasets: [
                {
                    label: 'بودجه',
                    data: budget,
                    backgroundColor: '#8b5cf6',
                    borderRadius: 4,
                    stack: 'stack1'
                },
                {
                    label: 'حق بیمه',
                    data: premiums,
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                    stack: 'stack1'
                },
                {
                    label: 'خسارات',
                    data: claims,
                    backgroundColor: '#f97316',
                    borderRadius: 4,
                    stack: 'stack1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'IRANSans'
                        }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fa-IR').format(value);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            family: 'IRANSans'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('fa-IR').format(context.raw);
                        }
                    }
                }
            }
        }
    });
}

// تابع برای ایجاد نمودار مالی ساده برای بخش کناری
function createFinancialFlowChart(financialData) {
    const ctx = document.getElementById('financialFlowChart');
    if (!ctx) {
        console.warn('⚠️ Canvas element financialFlowChart not found');
        return;
    }
    
    // اطمینان از destroy چارت قبلی
    financialChart = safeDestroyChart(financialChart, 'financialChart');
    
    // داده‌های ساده برای نمایش
    const data = {
        labels: ['بودجه', 'حق بیمه', 'خسارات'],
        datasets: [{
            label: 'مبلغ',
            data: [
                Number(financialData?.budget || 0),
                Number(financialData?.premiums || 0),
                Number(financialData?.claims || 0)
            ],
            backgroundColor: ['#8b5cf6', '#10b981', '#f97316'],
            borderRadius: 6,
        }]
    };
    
    financialChart = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            ...getDefaultChartOptions(),
            plugins: {
                ...getDefaultChartOptions().plugins,
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fa-IR').format(value);
                        }
                    }
                }
            }
        }
    });
}

// تابع اصلی برای ایجاد تمام چارت‌ها
function initializeAllCharts(data) {
    try {
        createGenderChart(data.maleCount, data.femaleCount);
        createGeoChart(data.geoLabels, data.geoDataMale, data.geoDataFemale, data.geoDataDeprived);
        createFinancialChart(data.financialData);
        createMonthlyChart(data.monthlyData);
        createCriteriaChart(data.criteriaData);
        createFinancialFlowChart(data.financialData);
        
        // چارت جریان ساده
        if (data.yearlyData) {
            createYearlyFlowChart(data.yearlyData);
        }
    } catch (error) {
        console.error('خطا در ایجاد چارت‌ها:', error);
    }
}

// تابع آپدیت چارت‌ها با destroy/recreate (تضمین عدم تداخل)
function updateAllCharts(data) {
    try {
        console.log('🔄 شروع آپدیت چارت‌ها...');
        
        // مرحله 1: Destroy کردن تمام چارت‌های موجود
        destroyAllCharts();
        
        // مرحله 2: کمی تاخیر برای اطمینان از destroy کامل
        setTimeout(() => {
            try {
                // مرحله 3: ایجاد مجدد چارت‌ها با داده‌های جدید
                recreateAllCharts(data);
                console.log('✅ تمام چارت‌ها با موفقیت آپدیت شدند');
            } catch (recreateError) {
                console.error('❌ خطا در recreate چارت‌ها:', recreateError);
            }
        }, 100);
        
    } catch (error) {
        console.error('❌ خطا در آپدیت چارت‌ها:', error);
        // Fallback: سعی در ایجاد مجدد 
        setTimeout(() => {
            initializeAllCharts(data);
        }, 200);
    }
}

// تابع برای destroy کردن همه چارت‌ها
function destroyAllCharts() {
    const charts = [
        { chart: genderChart, name: 'genderChart' },
        { chart: geoChart, name: 'geoChart' },
        { chart: financialChart, name: 'financialChart' },
        { chart: monthlyChart, name: 'monthlyChart' },
        { chart: criteriaChart, name: 'criteriaChart' },
        { chart: doubleDonutChart, name: 'doubleDonutChart' },
        { chart: yearlyFlowChart, name: 'yearlyFlowChart' }
    ];
    
    charts.forEach(({ chart, name }) => {
        if (chart && typeof chart.destroy === 'function') {
            try {
                chart.destroy();
                console.log(`🗑️ ${name} destroyed`);
            } catch (e) {
                console.warn(`⚠️ خطا در destroy ${name}:`, e);
            }
        }
    });
    
    // Reset متغیرهای سراسری
    genderChart = null;
    geoChart = null;
    financialChart = null;
    monthlyChart = null;
    criteriaChart = null;
    doubleDonutChart = null;
    yearlyFlowChart = null;
}

// تابع برای recreate کردن همه چارت‌ها
function recreateAllCharts(data) {
    // بررسی وجود داده‌ها
    if (!data) {
        console.warn('⚠️ داده‌ای برای ایجاد چارت‌ها موجود نیست');
        return;
    }
    
    // ایجاد چارت‌ها به ترتیب
    const chartCreators = [
        () => createGenderChart(data.maleCount, data.femaleCount),
        () => createGeoChart(data.geoLabels, data.geoDataMale, data.geoDataFemale, data.geoDataDeprived),
        () => createFinancialChart(data.financialData),
        () => createMonthlyChart(data.monthlyData),
        () => createCriteriaChart(data.criteriaData),
        () => createFinancialFlowChart(data.financialData),
        () => data.yearlyData && createYearlyFlowChart(data.yearlyData)
    ];
    
    chartCreators.forEach((creator, index) => {
        try {
            if (creator) {
                creator();
            }
        } catch (e) {
            console.error(`❌ خطا در ایجاد چارت ${index}:`, e);
        }
    });
}

// در دسترس قرار دادن تابع‌ها برای استفاده سراسری
window.dashboardCharts = {
    initializeAllCharts,
    updateAllCharts,
    destroyAllCharts,
    recreateAllCharts,
    safeDestroyChart,
    createGenderChart,
    createGeoChart,
    createFinancialChart,
    createMonthlyChart,
    createCriteriaChart,
    createYearlyFlowChart,
    createFinancialFlowChart
};

// اضافه کردن event listener برای DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // چارت‌ها پس از لود شدن داده‌ها از طریق Livewire ایجاد می‌شوند
    console.log('Dashboard charts library loaded');
}); 