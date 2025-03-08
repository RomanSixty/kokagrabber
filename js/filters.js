document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('#filter_new').addEventListener('click', function(e) {
        showAll();

        if (e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        } else {
            e.target.classList.add('active');
            document.querySelector('#filter_hilight').classList.remove('active');

            Array.prototype.forEach.call(document.querySelectorAll('.events li:not(.new)'), function(el) {
                el.style.display = 'none';
            });
        }
    });

    document.querySelector('#filter_hilight').addEventListener('click', function(e) {
        showAll();

        if (e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        } else {
            e.target.classList.add('active');
            document.querySelector('#filter_new').classList.remove('active');

            Array.prototype.forEach.call(document.querySelectorAll('.events li:not(.hilight)'), function(el) {
                el.style.display = 'none';
            });
        }
    });

    function showAll() {
        Array.prototype.forEach.call(document.querySelectorAll('.events li'), function(el) {
            el.style.display = 'block';
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('.events li'), function(el) {
        el.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A') {
                let action = 'hilight';

                if (e.target.classList.contains('hilight')) {
                    action = 'unhilight';
                }

                fetch('rpc.php?action=' + action + '&id=' + el.dataset['id'], {
                    method: 'post',
                }).then(function() {
                     if (action === 'hilight') {
                         e.target.classList.add('hilight');
                     } else {
                         e.target.classList.remove('hilight');
                     }
                });
            }
        })
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