import Chart from 'chart.js/auto';

const labels = [
    'Gennaio',
    'Febbraio',
    'Marzo',
    'Aprile',
    'Maggio',
    'Giugno',
    'Luglio',
    'Agosto',
    'Settembre',
    'Ottobre',
    'Novembre',
    'Dicembre',
];

const data = {
    labels: labels,
    datasets: [{
        label: 'Leads convertiti',
        backgroundColor: 'rgb(255, 99, 132)',
        borderColor: 'rgb(255, 99, 132)',
        data: [4, 1, 2, 5, 7, 10, 11,7,13,10,4,18],
    },
    {
        label:'Lead Totali',
        backgroundColor: 'rgb(143, 99, 255)',
        borderColor: 'rgb(143, 99, 255)',
        data: [5, 1, 4, 7, 8, 10, 13,9,15,10,4,20],
    }]
};

const config = {
    type: 'line',
    data: data,
    options: {}
};

new Chart(
    document.getElementById('myChart'),
    config,
    data,
    labels
);
$(document).ready(function () {
    
    var myChart = new Chart(document.getElementById('myChart'));
});
