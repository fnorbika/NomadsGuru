jQuery(document).ready(function ($) {
    // Safety check: Chart.js loaded?
    if (typeof Chart === 'undefined') {
        console.error('NomadsGuru: Chart.js library not loaded!');
        $('#ng-deals-chart').parent().html('<p style="color: #E06161;">Chart.js failed to load. Please check your internet connection.</p>');
        $('#ng-processing-chart').parent().html('<p style="color: #E06161;">Chart.js failed to load. Please check your internet connection.</p>');
        return;
    }

    // Safety check: Data exists?
    if (typeof dealsData === 'undefined' || typeof processingData === 'undefined') {
        console.log('NomadsGuru: No chart data available yet');
        return;
    }

    // Prevent multiple initializations
    if (window.ngChartsInitialized) {
        console.log('NomadsGuru: Charts already initialized');
        return;
    }
    window.ngChartsInitialized = true;

    // Deals Per Month Chart
    const dealsCtx = document.getElementById('ng-deals-chart');
    if (dealsCtx) {
        try {
            const months = dealsData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });
            const counts = dealsData.map(item => parseInt(item.count));

            new Chart(dealsCtx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Deals',
                        data: counts,
                        backgroundColor: '#2180B7',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            console.log('NomadsGuru: Deals chart initialized successfully');
        } catch (error) {
            console.error('NomadsGuru: Error initializing deals chart:', error);
            $(dealsCtx).parent().html('<p style="color: #E06161;">Error loading chart: ' + error.message + '</p>');
        }
    }

    // Processing Stats Chart
    const processingCtx = document.getElementById('ng-processing-chart');
    if (processingCtx && processingData.length > 0) {
        try {
            const statuses = processingData.map(item => item.status);
            const counts = processingData.map(item => parseInt(item.count));
            const colors = {
                'pending': '#E06161',
                'processing': '#FF9800',
                'completed': '#208C8D',
                'failed': '#E06161'
            };
            const backgroundColors = statuses.map(status => colors[status] || '#AEAAAA');

            new Chart(processingCtx, {
                type: 'doughnut',
                data: {
                    labels: statuses.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                    datasets: [{
                        data: counts,
                        backgroundColor: backgroundColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            console.log('NomadsGuru: Processing chart initialized successfully');
        } catch (error) {
            console.error('NomadsGuru: Error initializing processing chart:', error);
            $(processingCtx).parent().html('<p style="color: #E06161;">Error loading chart: ' + error.message + '</p>');
        }
    } else if (processingCtx) {
        $(processingCtx).parent().html('<p>No processing data available yet.</p>');
    }
});

