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
