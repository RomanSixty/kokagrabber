$(function () {
    $('#filter_new a').click(function () {
        if ($(this).parent().hasClass('active')) {
            showAll();
        }
        else {
            showAll();
            $(this).parent().addClass('active');
            $('.events li:not(.new)').hide();
        }

        return false;
    });

    $('#filter_hilight a').click(function () {
        if ($(this).parent().hasClass('active')) {
            showAll();
        }
        else {
            showAll();
            $(this).parent().addClass('active');
            $('.events li:not(.hilight)').hide();
        }

        return false;
    });

    function showAll() {
        $('.filters .active').removeClass('active');
        $('.events li').show();
    }

    $('.events li').click(function (e) {
        if (e.target.tagName != 'A') {
            if ($(this).hasClass('hilight'))
                action = 'unhilight';
            else
                action = 'hilight';

            $entry = $(this);

            $.ajax('rpc.php?action=' + action + '&id=' + $(this).attr('data-id'))
             .done(function () {
                 if (action == 'hilight')
                     $entry.addClass('hilight');
                 else
                     $entry.removeClass('hilight');
             });
        }
    });
});