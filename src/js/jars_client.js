(function(){
    window.jars_client = {
        updateBlend: function(blend, query, data, success) {
            $.ajax('/api/blend/' + blend + '/update?' + query, {
                method: 'post',
                contentType: false,
                processData: false,
                data: JSON.stringify(data),
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: success,
                error: function(data){
                    alert(data.responseJSON && data.responseJSON.error || 'Unknown error');
                }
            });
        },
        linetypeAdd: function(linetype, repeater, range_from, range_to, data){
            $.ajax('/api/' + linetype + '/add?repeater=' + repeater + '&from=' + range_from + '&to=' + range_to, {
                method: 'post',
                contentType: false,
                processData: false,
                data: JSON.stringify(data),
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function(data) {
                    window.location.reload();
                },
                error: function(data){
                    alert(data.responseJSON && data.responseJSON.error || 'Unknown error');
                }
            });
        },
        delete: function(blend, query, success){
            $.ajax('/api/blend/' + blend + '?' + query, {
                method: 'delete',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: success,
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        blendPrint: function(blend, query) {
            $.ajax('/api/blend/' + blend + '/print?' + query, {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        linePrint: function(linetype, id) {
            $.ajax('/api/' + linetype + '/print?id=' + id, {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function(data) {
                    $('#output').html(data.messages.join(', '));
                }
            });
        },
        lineDelete: function(linetype, id) {
            $.ajax('/api/' + linetype + '?id=' + id, {
                method: 'delete',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function() {
                    window.location.reload();
                },
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        lineUnlink: function(linetype, id, parent) {
            $.ajax('/api/' + linetype + '/' + id + '/unlink/' + parent, {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                success: function() {
                    window.location.reload();
                },
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        save: function(lines, success) {
            $.ajax(window.location.pathname + '/ajax/save', {
                method: 'post',
                contentType: false,
                processData: false,
                beforeSend: function(request) {
                    request.setRequestHeader("X-Auth", getCookie('token'));
                },
                data: JSON.stringify(lines),
                success: success,
                error: function(data){
                    alert(data.responseJSON.error);
                }
            });
        },
        lineGet: function(linetype, id, success) {
            for (var i = 0; i < window.lines.length; i++) {
                var line = window.lines[i];

                if (line.type == linetype && line.id == id) {
                    return success(line);
                }
            }

            alert('Could not find ' + linetype + '/' + id);
        }
    };
})();
