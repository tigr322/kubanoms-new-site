var bg = [
    "url(http://kubanoms.ru/img/map/eysk.gif) no-repeat 110px 19px",
    "url(http://kubanoms.ru/img/map/tihoreckiy.gif) no-repeat 243px 49px",
    "url(http://kubanoms.ru/img/map/timashevsk.gif) no-repeat 112px 95px",
    "url(http://kubanoms.ru/img/map/kropotkin.gif) no-repeat 235px 132px",
    "url(http://kubanoms.ru/img/map/tfomc.gif) no-repeat 158px 166px",
    "url(http://kubanoms.ru/img/map/temryuk.gif) no-repeat 22px 132px",
    "url(http://kubanoms.ru/img/map/novoross.gif) no-repeat 87px 187px",
    "url(http://kubanoms.ru/img/map/gorklyuch.gif) no-repeat 159px 208px",
    "url(http://kubanoms.ru/img/map/tuapse.gif) no-repeat 169px 254px",
    "url(http://kubanoms.ru/img/map/sochi.gif) no-repeat 217px 304px",
    "url(http://kubanoms.ru/img/map/armavir.gif) no-repeat 290px 189px",

];

function ShowFilial(i) {
    var o = document.getElementById('fildiv');
    if (o && o.style) o.style.background= bg[i];
    o = document.getElementById('filtxt'+i);
    if (o) o.className = 'fila';
}
function HideFilial(i) {
    var o = document.getElementById('fildiv');
    if (o && o.style) o.style.background= "url(img/spacer.gif)";
    var o = document.getElementById('filtxt'+i);
    if (o) o.className = '';
}

$(function() {
    $('.sidebar > li > a').click(function() {
        var $parent = $(this).parent(), $li = $('.sidebar > li');

        if ($parent.hasClass('active')) {
            $li.removeClass('active');
            $parent.removeClass('active');
        } else {
            $li.removeClass('active');
            $parent.addClass('active');
        }

        if ($parent.find('ul li').length > 0) {
            return false;
        }
    });

    $('.scroll-top').click(function(e) {
        $('html, body').animate({
            scrollTop: 0
        }, 500);

        e.preventDefault();
    });

    $('.tabs-container ul > li a').on('click', function(e) {

        $('.tabs-container ul > li').removeClass('active');
        $(this).parent().addClass('active');

        $('.tabs-container .tab-pane').removeClass('active');
        $($(this).attr('href')).addClass('active');
        e.preventDefault();

    });
});
