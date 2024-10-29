(function($) {
    "use strict";
    $(function() {
        $('a.aza-oauth2').on('click', function() {
            var w = 500;
            var h = 500;
            var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
            var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

            var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
            var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

            var left = ((width / 2) - (w / 2)) + dualScreenLeft;
            var top = ((height / 2) - (h / 2)) + dualScreenTop;

            var oauth2 = window.open($(this).attr('href'), $(this).attr('href'), 'width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
            window.addEventListener("message", function(event) {
                if(event.data.token) {                    
                    window.location = window.location.href + '&' + event.data.token;
                    oauth2.close();
                }
            });
            return false;
        });
    });
})(jQuery);