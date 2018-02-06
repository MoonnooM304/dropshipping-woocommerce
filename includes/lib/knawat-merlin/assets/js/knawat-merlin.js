
var Knawat_Merlin = (function($){

    var t;

    // callbacks from form button clicks.
    var callbacks = {
        install_plugins: function(btn){
            var plugins = new PluginManager();
            plugins.init(btn);
        },
        knawat_connect: function(btn){
            var plugins = new KnawatConnect();
            plugins.init(btn);
        }
    };

    function window_loaded(){

    	var 
    	body 		= $('.merlin__body'),
    	body_loading 	= $('.merlin__body--loading'),
    	body_exiting 	= $('.merlin__body--exiting'),
    	drawer_trigger 	= $('#merlin__drawer-trigger'),
    	drawer_opening 	= 'merlin__drawer--opening';
    	drawer_opened 	= 'merlin__drawer--open';

    	setTimeout(function(){
	        body.addClass('loaded');
	    },100); 

    	drawer_trigger.on('click', function(){
        	body.toggleClass( drawer_opened );
        });

    	$('.merlin__button--proceed:not(.merlin__button--closer)').click(function (e) {
		    e.preventDefault();
		    var goTo = this.getAttribute("href");

		    body.addClass('exiting');

		    setTimeout(function(){
		        window.location = goTo;
		    },400);       
		});

        $(".merlin__button--closer").on('click', function(e){

        	body.removeClass( drawer_opened );

            e.preventDefault();
		    var goTo = this.getAttribute("href");

		    setTimeout(function(){
		        body.addClass('exiting');
		    },600);   
		    
		    setTimeout(function(){
		        window.location = goTo;
		    },1100);   
        });

        $(".button-next").on( "click", function(e) {
            e.preventDefault();
            var loading_button = merlin_loading_button(this);
            if ( ! loading_button ) {
                return false;
            }
            var data_callback = $(this).data("callback");
            if( data_callback && typeof callbacks[data_callback] !== "undefined"){
                // We have to process a callback before continue with form submission.
                callbacks[data_callback](this);
                return false;
            } else {
                return true;
            }
        });
    }

    function KnawatConnect() {
    	var body 				= $('.merlin__body');
        var complete, notice 	= $("#knawat-connect-text");

        function ajax_callback(r) {
            
            if (typeof r.done !== "undefined") {
            	setTimeout(function(){
			        notice.addClass("lead");
			    },0); 
			    setTimeout(function(){
			        notice.addClass("success");
			        notice.html(r.message);
			    },600); 
			    
                
                complete();
            } else {
                notice.addClass("lead error");
                notice.html(r.error);
                jQuery('.merlin_knawat').removeClass("merlin__button--loading");
            }
        }

        function do_ajax() {
            knawatAPIKey = $("#knawat-connect-status").val();
            jQuery.post(merlin_params.ajaxurl, {
                action: "merlin_knawat_connect",
                wpnonce: merlin_params.wpnonce,
                kAPIKey: knawatAPIKey
            }, ajax_callback).fail(ajax_callback);
        }

        return {
            init: function(btn) {
                complete = function() {

                	setTimeout(function(){
				$(".merlin__body").addClass('js--finished');
			},1500);

                	body.removeClass( drawer_opened );

                	setTimeout(function(){
				$('.merlin__body').addClass('exiting');
			},3500);   

                    	setTimeout(function(){
				window.location.href=btn.href;
			},4000);
		    
                };
                do_ajax();
            }
        }
    }

    function PluginManager(){

    	var body 				= $('.merlin__body');
        var complete;
        var items_completed 	= 0;
        var current_item 		= "";
        var $current_node;
        var current_item_hash 	= "";

        function ajax_callback(response){
            var currentSpan = $current_node.find("label");
            if(typeof response === "object" && typeof response.message !== "undefined"){
                //$current_node.find("span").text(response.message);
                currentSpan.addClass(response.message.toLowerCase());
                if(typeof response.url != "undefined"){
                    // we have an ajax url action to perform.

                    if(response.hash == current_item_hash){
                        //$current_node.find("span").text("failed");
                        currentSpan.addClass("status--failed");
                        find_next();
                    }else {
                        current_item_hash = response.hash;
                        jQuery.post(response.url, response, function(response2) {
                            process_current();
                        }).fail(ajax_callback);
                    }

                }else if(typeof response.done != "undefined"){
                    // finished processing this plugin, move onto next
                    find_next();
                }else{
                    // error processing this plugin
                    find_next();
                }
            }else{
                // error - try again with next plugin
                //$current_node.find("span").text("Success");
                //currentSpan.addClass('success');
                currentSpan.addClass("status--error");
                find_next();
            }
        }
        function process_current(){
            if(current_item){
                // query our ajax handler to get the ajax to send to TGM
                // if we don"t get a reply we can assume everything worked and continue onto the next one.
                var $check = $current_node.find("input:checkbox");
                if($check.is(":checked")) {
                    jQuery.post(merlin_params.ajaxurl, {
                        action: "merlin_plugins",
                        wpnonce: merlin_params.wpnonce,
                        slug: current_item
                    }, ajax_callback).fail(ajax_callback);
                }else{
                    $current_node.addClass("skipping");
                    setTimeout(find_next,300);
                }
            }
        }

        function find_next(){
            var do_next = false;
            if($current_node){
                if(!$current_node.data("done_item")){
                    items_completed++;
                    $current_node.data("done_item",1);
                }
                $current_node.find(".spinner").css("visibility","hidden");
            }
            var $li = $(".merlin__drawer--install-plugins li");
            $li.each(function(){
                if(current_item == "" || do_next){
                    current_item = $(this).data("slug");
                    $current_node = $(this);
                    process_current();
                    do_next = false;
                }else if($(this).data("slug") == current_item){
                    do_next = true;
                }
            });
            if(items_completed >= $li.length){
                // finished all plugins!
                complete();
            }
        }

        return {
            init: function(btn){
                $(".merlin__drawer--install-plugins").addClass("installing");
                $(".merlin__drawer--install-plugins").find("input").prop("disabled", true);
                complete = function(){

                	setTimeout(function(){
				        $(".merlin__body").addClass('js--finished');
				    },1000);

                	body.removeClass( drawer_opened );

                	setTimeout(function(){
				        $('.merlin__body').addClass('exiting');
				    },3000);   

                    setTimeout(function(){
				        window.location.href=btn.href;
				    },3500);

                };
                find_next();
            }
        }
    }

    function merlin_loading_button( btn ){

        var $button = jQuery(btn);

        if ( $button.data( "done-loading" ) == "yes" ) {
        	return false;
        }

        var completed = false;

        var _modifier = $button.is("input") || $button.is("button") ? "val" : "text";
        
        $button.data("done-loading","yes");
        
        $button.addClass("merlin__button--loading");

        return {
            done: function(){
                completed = true;
                $button.attr("disabled",false);
            }
        }

    }

    return {
        init: function(){
            t = this;
            $(window_loaded);
        },
        callback: function(func){
            console.log(func);
            console.log(this);
        }
    }

})(jQuery);

Knawat_Merlin.init();

function KnawatPopupCenter(url, title, w, h) {
    // Fixes dual-screen position
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

    var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;
    var newWindow = window.open(url, title, 'scrollbars=yes, location=0, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    // Puts focus on the newWindow
    if (window.focus) {
        newWindow.focus();
    }
}



// For login with Knawat.com
function KnawatPopup1() {

    var w = 420;
    var h = 600;
    // Center Popup Logic
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;
    var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;

    const step1Popup = window.open(
        'https://app.knawat.com/autologin?returnurl=' + location.origin,
        'knawatLogin',
        'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=' + w + ',height=' + h + ',top=' + top + ',left=' + left),
    checkUrlInterval = setInterval(() => {
        try {
            if ( step1Popup.location.origin === location.origin ) {
                clearInterval(checkUrlInterval);
                step1Popup.close();
                jQuery(".knawat_connect_step_1").hide();
                jQuery(".knawat_connect_step_2").show();
            }
        } catch (error) {
            // this should be triggered while popup has another site in location.href
        }
    }, 100);
}

// For connect site with Knawat.com
function KnawatPopup2() {

    var w = 740;
    var h = 700;
    // Center Popup Logic
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;
    var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;

    const step2Popup = window.open(
        'https://app.knawat.com/stores/validate/woocommerce/' + escape(encodeURIComponent(location.origin)) + '?returnurl=' + location.origin,
        'knawatAddWooCommerce',
        'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=' + w + ',height=' + h + ',top=' + top + ',left=' + left),
    checkUrlInterval = setInterval(() => {
        try {
            if (step2Popup.location.origin === location.origin ) {
                clearInterval(checkUrlInterval);
                step2Popup.close();

                jQuery("#knawat-connect-status").val('connected')
                jQuery('#knawat_connect_next').trigger("click");
            }
        } catch (error) {
            // this should be triggered while popup has another site in location.href
        }
    }, 100);
}
