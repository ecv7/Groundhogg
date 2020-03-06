function tool_tip_label(tooltipItem, data) {
    if (data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].label) {
        return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].label;
    } else {
        return  data.datasets[tooltipItem.datasetIndex].label + ": " + data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].y;
    }
}

function tool_tip_title() {
    return '';
}

(function (reporting, $, nonces) {

    $.extend(reporting, {

        data: {},
        calendar: null,
        myChart : [],

        init: function () {

            this.initCalendar();
            this.initFunnels();
            this.initCountry();

        },

        initCalendar: function () {

            var self = this;

            this.calendar = new Calendar({
                element: $('#groundhogg-datepicker'),
                presets: [
                    {
                        label: 'Last 30 days',
                        start: moment().subtract(29, 'days'),
                        end: moment(),
                    }, {
                        label: 'This month',
                        start: moment().startOf('month'),
                        end: moment().endOf('month'),
                    }, {
                        label: 'Last month',
                        start: moment().subtract(1, 'month').startOf('month'),
                        end: moment().subtract(1, 'month').endOf('month'),
                    }, {
                        label: 'Last 7 days',
                        start: moment().subtract(6, 'days'),
                        end: moment(),
                    }, {
                        label: 'Last 3 months',
                        start: moment(this.latest_date).subtract(3, 'month').startOf('month'),
                        end: moment(this.latest_date).subtract(1, 'month').endOf('month'),
                    }],
                earliest_date: 'January 1, 2006',
                latest_date: moment(),
                start_date: moment().subtract(29, 'days'),
                end_date: moment(),
                callback: function () {
                    self.refresh(this)
                },
            });

            // run it with defaults
            this.calendar.calendarSaveDates()
        },

        initFunnels : function() {

            var self = this;

            $( '#funnel-id' ).change(function () {
                self.refresh(self.calendar);
            });
        },

        initCountry : function() {

            var self = this;

            $( '#country' ).change(function () {
                self.refresh(self.calendar);
            });
        },

        refresh: function (calendar) {

            var self = this;

            self.showLoader();

            var start = moment(calendar.start_date).format('LL'),
                end = moment(calendar.end_date).format('LL');

            $.ajax({
                type: 'post',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'groundhogg_refresh_dashboard_reports',
                    reports: self.reports,
                    start: start,
                    end: end,
                    data: self.get_other_data()
                },
                success: function (json) {

                    self.hideLoader();

                    self.data = json.data.reports;

                    self.renderReports();

                },
                failure: function (response) {

                    alert('Unable to retrieve data...')

                },
            })

        },

        get_other_data : function(){
            return {
                funnel_id: $( '#funnel-id' ).val(),
                country: $('#country').val()
            };
        },

        renderReports: function () {

            for (var i = 0; i < this.reports.length; i++) {

                var report_id = this.reports[i];
                var report_data = this.data[report_id];

                // console.log( report_id, report_data )

                this.renderReport(report_id, report_data)

            }

        },

        renderReport: function (report_id, report_data) {

            var $report = $('#' + report_id);

            var type = report_data.type;

            switch (type) {
                case 'quick_stat':
                    this.renderQuickStatReport($report, report_data);
                    break;
                case 'chart':
                    this.renderChartReport($report, report_data.chart);
                    break;
            }

        },

        renderQuickStatReport: function ($report, report_data) {

            // console.log( report_data )

            $report.find('.groundhogg-quick-stat-number').html(report_data.number);
            $report.find('.groundhogg-quick-stat-previous').removeClass('green red').addClass(report_data.compare.arrow.color);
            $report.find('.groundhogg-quick-stat-compare').html(report_data.compare.text);
            $report.find('.groundhogg-quick-stat-arrow').removeClass('up down').addClass(report_data.compare.arrow.direction);
            $report.find('.groundhogg-quick-stat-prev-percent').html(report_data.compare.percent)

        },

        renderChartReport: function ($report, report_data) {

            if (typeof report_data.options.tooltips.callbacks !== 'undefined') {
                var funcName = report_data.options.tooltips.callbacks.label;
                report_data.options.tooltips.callbacks.label = window[funcName];
            }

            if (typeof report_data.options.tooltips.callbacks !== 'undefined') {
                var funcName = report_data.options.tooltips.callbacks.title;
                report_data.options.tooltips.callbacks.title = window[funcName];
            }


            if(this.myChart[$report.selector] !=null){
                this.myChart[$report.selector].destroy();
            }

            var ctx = $report[0].getContext('2d');
            this.myChart[$report.selector] = new Chart(ctx, report_data) ;

            // draw Hover line in the graph
            var draw_line = Chart.controllers.line.prototype.draw;
            Chart.helpers.extend(Chart.controllers.line.prototype, {
                draw: function () {
                    draw_line.apply(this, arguments);
                    if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
                        var ap = this.chart.tooltip._active[0];
                        var ctx = this.chart.ctx;
                        var x = ap.tooltipPosition().x;
                        var topy = this.chart.scales['y-axis-0'].top;
                        var bottomy = this.chart.scales['y-axis-0'].bottom;

                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(x, topy);
                        ctx.lineTo(x, bottomy);
                        ctx.lineWidth = 1;
                        ctx.strokeStyle = '#727272';
                        ctx.setLineDash([10, 10]);
                        ctx.stroke();
                        ctx.restore();
                    }
                }

            });


        },

        showLoader: function () {
            $('.gh-loader-overlay').show();
            $('.gh-loader').show();
        },

        hideLoader: function () {
            $('.gh-loader-overlay').hide();
            $('.gh-loader').hide();
        },

    });

    $(function () {
        reporting.init()
    })

})(GroundhoggReporting, jQuery, groundhogg_nonces);