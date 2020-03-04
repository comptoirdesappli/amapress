(function () {
    var throttle = 0;

    window.onerror = function (msg, url, line) {
        // Return if we've sent more than 25 errors.
        throttle++;
        if (throttle > 25) return;

        // Log the error.
        var req = new XMLHttpRequest();
        var params = 'action=js_log_error&msg=' + encodeURIComponent(msg)
            + '&url=' + encodeURIComponent(url)
            + '&referer=' + encodeURIComponent(window.location.href)
            + "&line=" + line;
        // Replace spaces with +, browsers expect this for form POSTs.
        params = params.replace(/%20/g, '+');
        req.open('POST', ajaxurl);
        req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        req.send(params);
    };
})();
