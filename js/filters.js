$(function () {
    $('#filter_new').click(function() {
        if ($(this).hasClass('active')) {
            showAll();
        }
        else {
            showAll();
            $(this).addClass('active');
            $('.events li:not(.new)').hide();
        }
    });

    $('#filter_hilight').click(function() {
        if ($(this).hasClass('active')) {
            showAll();
        }
        else {
            showAll();
            $(this).addClass('active');
            $('.events li:not(.hilight)').hide();
        }
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

    document.querySelector('#sort_date').addEventListener('click', function(e) {
        if (!e.target.classList.contains('active')) {
            sortList(document.querySelector('ul.events'), 'date');
            document.querySelector('#sort_name').classList.remove('active');
            e.target.classList.add('active');
        }
    });

    document.querySelector('#sort_name').addEventListener('click', function(e) {
        if (!e.target.classList.contains('active')) {
            sortList(document.querySelector('ul.events'), 'name');
            document.querySelector('#sort_date').classList.remove('active');
            e.target.classList.add('active');
        }
    });

    function sortList(ul, sortby){
        let new_ul = document.createElement('UL');
        new_ul.classList.add('events');

        let lis = [];

        for (let i = ul.childNodes.length; i--;) {
            if (ul.childNodes[i].nodeName === 'LI') {
                lis.push(ul.childNodes[i]);
            }
        }

        if (sortby === 'date') {
            lis.sort(function(a, b) {
                return a.dataset['date'] > b.dataset['date'];
            });
        } else {
            lis.sort(function(a, b) {
                return a.dataset['name'] > b.dataset['name'];
            });
        }

        for (let i = 0; i < lis.length; i++) {
            new_ul.appendChild(lis[i]);
        }

        ul.parentNode.replaceChild(new_ul, ul);
    }
});