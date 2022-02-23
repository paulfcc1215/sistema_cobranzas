(function($) {
    $.fn.drags = function(opt) {

        opt = $.extend(
            {
                handle:"",
                cursor:"move",
                return: false,
                onmouseup: new Array()
            }, opt
        );
        if(typeof opt.onmouseup == "function") {
            opt.onmouseup=new Array(opt.onmouseup);
        }
        
        if(opt.handle === "") {
            var $el = this;
        } else {
            var $el = this.find(opt.handle);
        }

        return $el.css('cursor', opt.cursor).on("mousedown", function(e) {
            if(opt.handle === "") {
                var $drag = $(this).addClass('draggable');
            } else {
                var $drag = $(this).addClass('active-handle').parent().addClass('draggable');
            }
            
            var original_position=$drag.position();
            $drag.data("opos_left",original_position.left);
            $drag.data("opos_top",original_position.top);
            
            var z_idx = $drag.css('z-index'),
                drg_h = $drag.outerHeight(),
                drg_w = $drag.outerWidth(),
                pos_y = $drag.offset().top + drg_h - e.pageY,
                pos_x = $drag.offset().left + drg_w - e.pageX;
            $drag.css('z-index', 1000).parents().on("mousemove", function(e) {
                $('.draggable').offset({
                    top:e.pageY + pos_y - drg_h,
                    left:e.pageX + pos_x - drg_w
                }).on("mouseup", function() {
                    $(this).removeClass('draggable').css('z-index', z_idx);
                });
            });
            e.preventDefault(); // disable selection
        }).on("mouseup", function(e) {
            if(opt.handle === "") {
                var $drag = $(this).removeClass('draggable');
            } else {
                var $drag = $(this).removeClass('active-handle').parent().removeClass('draggable');
            }
            if(opt.return) {
                $drag.css('top', 0).css('left', 0);
            }
            if(opt.onmouseup.length > 0) {
                for(var fni in opt.onmouseup) {
                    opt.onmouseup[fni](e);
                }
            }
        });

    }
})(jQuery);