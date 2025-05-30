<div id="{{ $id }}" class="w-full h-80"></div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var options = {
            chart: {
                type: '{{ $type }}',
                height: 320,
                toolbar: { show: false },
            },
            series: @json($options['series']),
            xaxis: @json($options['xaxis']),
            colors: ['#34D399', '#F87171', '#60A5FA'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth' },
        };
        var chart = new ApexCharts(document.querySelector('#{{ $id }}'), options);
        chart.render();
    });
</script>