$(document).ready(function() {
    let width = $(document).width();
    //console.log(width);
    if (width <= 768) {
        $('.navcheck').attr('checked', false);
        $('.secondarynav').attr("hidden", false);
    } else {
        $('.navcheck').attr('checked', true);
        $('.secondarynav').attr("hidden", true);

    }
    $('.navcheck').change(function(e) {
        e.preventDefault();
        if ($(this).is(':checked')) {
            $('.sidebar-brand').attr("hidden", true);

        } else {
            $('.sidebar-brand').attr("hidden", false);

        }
    });
});