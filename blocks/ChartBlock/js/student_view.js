import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import Chart from 'chart.js'

export default StudentView.extend({
    events: {},

    initialize() {},

    render() {
        return this;
    },

    postRender() {
        if (this.$(".cw-chart-stored-content").val() != "") {
            this.buildChart();
        }

        return this;
    },

    buildChart() {
        var $view = this;
        var ctx = $view.$('.cw-chartblock-canvas').get(0).getContext('2d');

        var $type = $view.$(".cw-chart-stored-type").val();
        var $content = $view.$(".cw-chart-stored-content").val();
        var json = JSON.parse($content);
        
        var $labels = [];
        var $data = [];
        var $backgroundColor = [];
        var $borderColor = [];
        $.each(json, function(i){
            $labels.push(json[i].label);
            $data.push(json[i].value);
            $backgroundColor.push('rgba('+$view.getColor(json[i].color)+', 0.6)' );
            $borderColor.push('rgba('+$view.getColor(json[i].color)+', 1.0)' );
        });

        var $label = $view.$('.cw-chart-stored-label').val();

        if (($type == 'bar') || ($type == 'horizontalBar')) {
            var myChart = new Chart(ctx, {
                type: $type,
                data: {
                    labels: $labels,
                    datasets: [{
                        label: $label,
                        data: $data,
                        backgroundColor: $backgroundColor,
                        borderColor: $borderColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero:true
                            }
                        }]
                    }
                }
            });
        }

        if (($type == 'pie') || ($type == 'doughnut') || ($type == 'polarArea')){
            var myChart = new Chart(ctx, {
                type: $type,
                data: {
                    labels: $labels,
                    datasets: [{
                        data: $data,
                        backgroundColor: $backgroundColor,
                        borderWidth: 1
                    }]
                },
                options: {}
            });
        }

        if ($type == 'line') {
            var myChart = new Chart(ctx, {
                type: $type,
                data: {
                    labels: $labels,
                    datasets: [{
                        label: $label,
                        data: $data,
                        fill: false, 
                        borderWidth: 2,
                        pointBackgroundColor: $borderColor
                    }]
                },
                options: {}
            });
        }
    },

    getColor($color) {
        switch($color) {
            case 'red':
                return '192, 57, 43';
            case 'blue': 
                return '52, 152, 219';
            case 'yellow':
                return '241, 196, 15';
            case 'green':
                return '46, 204, 113';
            case 'purple':
                return '155, 89, 182';
            case 'orange':
                return '230, 126, 34';
            case 'turquoise':
                return '26, 188, 156';
            case 'grey':
                return '52, 73, 94';
            case 'lightgrey':
                return '149, 165, 166';
            case 'black':
                return '0, 0, 0';
        }
    }

});
