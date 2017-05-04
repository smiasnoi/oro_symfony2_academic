// comment form routines
$(document).ready(function() {
    $(".btn-comment").click(function () {
        $(".btn-comment").each(function(){
            var el = $(this);
            if (!el.hasClass('navbar-btn')) {
                el.hide();
            }
        });

        $("#commentFormWrapper.collapse").collapse('show');
    });

    $(".btn-cancel-comment").click(function () {
        $("#commentFormWrapper.collapse").collapse('hide');
    });

    $("#commentFormWrapper.collapse").on('hidden.bs.collapse', function(){
        $(".btn-comment").show();
    });
});

// modal content clear
$(document).on("hidden.bs.modal", function (e) {
    $(e.target).removeData("bs.modal").find(".modal-content").empty();
});

// Select2 jQuery plugin routines initializing
$(document).ready(function() {
    var __select2Routines = window.__select2Routines || [], i;
    for (i = 0; i < __select2Routines.length; i++) {
        __select2Routines[i]();
    }
});
