(function() {
    // Graphs

    var $canvas = $('#bg');

    if ($canvas.length && window.graphSeries) {
        var onResize = function() {
            var width = Math.floor(window.innerWidth - 120 - (window.innerWidth >= 800 && 350 || 0));
            var height = Math.min(width * 3 / 4, Math.floor(window.innerHeight - 200));

            $('#bg-container').css({
                width: width + 'px',
                height: height + 'px'
            });

            $canvas.attr('width', width * 2).attr('height', height * 2).css('zoom', 0.5);

            var c = document.getElementById("bg");
            var ctx = c.getContext("2d");

            width = $canvas.width() - 2;
            height = $canvas.height() - 2;

            if (today) {
                ctx.lineWidth = 5;
                ctx.strokeStyle = '#' + highlight;
                ctx.fillStyle = '#' + highlight;
                ctx.beginPath();
                ctx.fillRect(today[0] * width + 1, 1, (today[1] * width + 1) - (today[0] * width + 1), height + 1);
            }

            ctx.lineWidth = 3;
            ctx.lineJoin = 'miter';
            ctx.strokeStyle = "#efefef";

            if (typeof divs != 'undefined') {
                for (var i = 0; i < divs.length; i++) {
                    ctx.beginPath();
                    ctx.moveTo(width * divs[i] + 1, 0);
                    ctx.lineTo(width * divs[i] + 1, height + 1);
                    ctx.stroke();
                }
            }

            if (typeof guides != 'undefined') {
                for (var i = 0; i < guides.length; i++) {
                    ctx.beginPath();

                    ctx.strokeStyle = guides[i].color;
                    let y = height * (1 - guides[i].y) + 1;
                    ctx.moveTo(0, y);
                    ctx.lineTo(width + 1, y);
                    ctx.stroke();
                }
            }

            ctx.strokeStyle = "#efefef";
            ctx.strokeStyle = "#bbb";
            ctx.lineWidth = 2;

            ctx.beginPath();

            var xAxis = height * (1 - xAxisProp);

            ctx.moveTo(0, xAxis + 1);
            ctx.lineTo(width + 2, xAxis + 1);
            ctx.stroke();

            ctx.lineWidth = 5;

            ctx.beginPath();

            ctx.moveTo(0, 0);
            ctx.lineTo(width + 2, 0);

            ctx.moveTo(0, height + 2);
            ctx.lineTo(width + 2, height + 2);

            ctx.moveTo(0, 0);
            ctx.lineTo(0, height + 2);

            ctx.moveTo(width + 2, 0);
            ctx.lineTo(width + 2, height + 2);

            ctx.stroke();

            ctx.lineWidth = 3;
            ctx.lineJoin = 'round';

            var seriesNum = 0;

            for (const seriesName in graphSeries) {
                var points = graphSeries[seriesName].points;
                var color = graphSeries[seriesName].color;
                let groupWidth = width / points.length;
                let barWidth = Math.min(30, Math.floor(groupWidth / numSeries));

                if (style === 'bar') {
                    ctx.fillStyle = color;

                    for (var i = 0; i < points.length; i++) {
                        let base = height * (1 - xAxisProp),
                            tip = height * (1 - points[i][1]),
                            top = base > tip ? base : tip,
                            bottom = base > tip ? tip : base,
                            offset = (groupWidth - barWidth * numSeries) / 2 + barWidth * seriesNum,
                            left = width * points[i][0] + offset + 1,
                            barHeight = bottom - top;

                        ctx.fillRect(left, top, barWidth, barHeight);
                    }
                } else {
                    ctx.beginPath();
                    ctx.moveTo(width * points[0][0] + 1, height * (1 - points[0][1]) + 1);
                    ctx.strokeStyle = color;

                    for (var i = 1; i < points.length; i++) {
                        ctx.lineTo(Math.round(width * points[i][0] + 1), Math.round(height * (1 - points[i][1]) + 1));
                    }

                    ctx.stroke();
                }

                seriesNum++;
            }
        };

        $(window).on('resize', onResize);
        onResize();
    }
})();
