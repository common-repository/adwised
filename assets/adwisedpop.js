function adwisedPop(){
    function adwisedPopSetCookie(cname, cvalue) {
        var endOfToday = new Date();
        endOfToday.setHours(23, 59, 59, 0);
        var expires = "expires=" + endOfToday.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    function adwisedPopGetCookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(";");
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == " ") {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    function adwisedPopGetRequesterCookieName() {
        return "adwisedPop_" + document.adwisedPopId;
    }

    function adwisedPopGetVisitedCount() {
        var cookieKey = adwisedPopGetRequesterCookieName();
        return parseInt(adwisedPopGetCookie(cookieKey)) || 0;
    }

    function adwisedPopIncrementVisitedCount() {
        adwisedPopSetCookie(
            adwisedPopGetRequesterCookieName(),
            adwisedPopGetVisitedCount() + 1
        );
    }
    function adwisedPopGetPopCount() {
        return document.adwisedPopCount;
    }

    function adwisedPopGetLink() {
        return document.adwisedPopLinks[adwisedPopGetVisitedCount()];
    }
    function adwisedPopCheckIfRequesterIsInWhitelist() {
        if (adwisedPopGetVisitedCount() < adwisedPopGetPopCount()) {
            if (document.adwisedCanShow==0){
                return false
            }
            var userAgent = navigator.userAgent.toLowerCase();
            return userAgent.indexOf("googlebot") === -1 && userAgent.indexOf("google.com/bot.html") === -1;
        }
        return false;
    }
    function adwisedIsStringNullOrWhiteSpace(str) {
        return (
            str === undefined ||
            str === null ||
            typeof str !== "string" ||
            str.match(/^ *$/) !== null
        );
    }
    function adwisedIsElementExcluded(e) {
        if (adwisedIsStringNullOrWhiteSpace(document.adwisedExcludeQuerySelector)) {
            return false;
        }
        var exculdedElemenets = document.querySelectorAll(
            document.adwisedExcludeQuerySelector
        );
        var clickedItem = e.target;
        for (let i = 0; i < exculdedElemenets.length; i++) {
            var isMatch = e.target.matches(document.adwisedExcludeQuerySelector) || exculdedElemenets[i].contains(clickedItem);
            if (isMatch)
                return true
        }
        return false;
    }
    function adwisedPopShowPop(e) {
        if (window.SymRealWinOpen) {
            open = SymRealWinOpen;
        }
        if (window.NS_ActualOpen) {
            open = NS_ActualOpen;
        }
        if (adwisedPopGetVisitedCount() >= adwisedPopGetPopCount() || adwisedIsElementExcluded(e)) {
            return;
        } else {
            window.open(adwisedPopGetLink(), "", "width=480,height=600,top=99999999,left=99999999,status=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,fullscreen=no");
            adwisedPopIncrementVisitedCount();
        }
    }
    if (adwisedPopCheckIfRequesterIsInWhitelist()) {
        if (!document.addEventListener)
            document.attachEvent("onclick", adwisedPopShowPop);
        else
            document.addEventListener("click", adwisedPopShowPop, false);
    }
    
}
function adwisedIframe(){
    if (adwisedIframeCheckIfRequesterIsInWhitelist()) {
        return true;
    }
    return false;
    function adwisedIframeCheckIfRequesterIsInWhitelist(){
        var userAgent = navigator.userAgent.toLowerCase();
        return userAgent.indexOf("googlebot") === -1 && userAgent.indexOf("google.com/bot.html") === -1;    
    }
}