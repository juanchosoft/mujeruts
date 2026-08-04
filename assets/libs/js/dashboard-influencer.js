$(function() {
    "use strict";
    // ============================================================== 
    // Gender Js
    // ============================================================== 

    Morris.Donut({
        element: 'gender_donut',
        data: [
            { value: TOTAL_EJECUTADO, label: 'Ejecutado' },
            { value: TOTAL_POR_EJECUTAR, label: 'Por Ejecutar' }

        ],

        labelColor: '#5969ff',
        colors: [
            '#5969ff',
            '#ff407b',

        ],



        formatter: function(x) { return x + "%" }
    });

    // ============================================================== 
    //  chart bar horizontal
    // ============================================================== 
    var ctx = document.getElementById("chartjs_bar_horizontal").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'horizontalBar',

        data: {
            labels: LABELS_SECRETARIA,
            datasets: [{
                label: 'Total Invertido',
                data: DATA_SECRETARIA,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(255, 205, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(201, 203, 207, 0.2)'
                  ],

            }]
        },
        options: {
            responsive: true,
            hover: false,
            legend: {
                display: true,
                position: 'bottom',

                labels: {
                    fontColor: '#71748d',
                    fontFamily: 'Circular Std Book',
                    fontSize: 12,
                }
            },
            scales: {

                legend: {
                    display: false

                },
                yAxes: [{
                    gridLines: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        fontSize: 12,
                        fontFamily: 'Circular Std Book',
                        fontColor: '#71748d',
                    }
                }],
                xAxes: [{
                    gridLines: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        fontSize: 12,
                        fontFamily: 'Circular Std Book',
                        fontColor: '#71748d',
                    }
                }]



            }
        }
    });



});