// Case active menu when preview other url
$(function() {
    var a = window.location.href;
    const urlOriginal = location.protocol + '//' + location.host + location.pathname;
    for (var abc = $(".vertical-menu a").filter(function() {
        return urlOriginal == this.href;
    }).addClass("active").parent().addClass("active"); ;) {
        if (!abc.is("li")) break;
        abc.parent().css({"display": "block"});
        abc = abc.parent().addClass("in").parent().addClass("active");
    }
    if(a.includes('preview')){
        // get prev url
        var prevUrl = document.referrer;
        const prevUrlOrigin = prevUrl;
        const url = new URL(prevUrl);
        prevUrl = url.protocol + '//' + url.host + url.pathname;
        for (var menu = $(".vertical-menu a").filter(function() {
            return this.href == prevUrl || this.href == prevUrlOrigin;
        }).addClass("active-current").parent().addClass("active"); ;) {
            if (!menu.is("li")) break;            
            menu.parent().css({"display": "block"});
            menu = menu.parent().addClass("in").parent().addClass("active");
        }
    }
});
