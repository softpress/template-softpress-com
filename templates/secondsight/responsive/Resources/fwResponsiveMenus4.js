(function() {
    var e = document.querySelectorAll("input[id$='-toggle']");
    for(var i=0;i<e.length;i++){
       fwAddListener(e[i], "change",function() {
            var _this = this,
                container = document.querySelector("#" + _this.id + " ~ [class*='fwNavContainer']");
            fwAddListener(container, "transitionend", function() {
                _this.checked ?
                    document.querySelector("html").style.overflow = document.querySelector("body").style.overflow = "hidden"
                :
                    document.querySelector("html").style.overflow = document.querySelector("body").style.overflow = "";
            });
       });
    }
    e = document.querySelectorAll(".fwNavItem a");
    for(var i=0;i<e.length;i++){
       fwAddListener(e[i], "click",function() {
       		// If we're not an anchor then we don't want to do anything
       		if(!(/#/.test(this.href)))
       			return;
       		// Get the input up the tree and uncheck it
       		var parent = this.parentNode;
       		while(!(/fwNavContainer/.test(parent.className)))
       		{
       			parent = parent.parentNode;
       			if(!parent)
       				return;
       		}
       		parent.parentNode.querySelector("input[id$='-toggle']").checked = false;
            document.querySelector("html").style.overflow = document.querySelector("body").style.overflow = "";
       });
    }
})();
function fwShowHideMenu(id, screenWidth)
{
    //var checkbox = document.querySelector("#" + id + " input:not(:checked)");
	if(Math.max(document.documentElement.clientWidth, window.innerWidth || 0) < screenWidth)
		setTimeout(function() { document.querySelector("#" + id + " [class*='fwNavContainer']").style.display = Math.max(document.documentElement.clientWidth, window.innerWidth || 0) < screenWidth ? "block" : "" }, 1000);
	else
    {
		document.querySelector("#" + id + " [class*='fwNavContainer']").style.display = "";
        document.querySelector("html").style.overflow = document.querySelector("body").style.overflow = "";
    }
}
function fwAddListener(element, type, callback)
{
    if (element.addEventListener) element.addEventListener(type, callback);
    else if (element.attachEvent) element.attachEvent('on' + type, callback);
}