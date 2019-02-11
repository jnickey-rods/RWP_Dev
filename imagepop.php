<?php
    $image = htmlspecialchars($_GET['image']);

    if($_SERVER['HTTP_HOST'] == 'localhost'){
?>
    <script src="/rods/js/slider/jquery-1.9.1.min.js"></script>
<?php } ?>
    <script src="/js/slider/jquery-1.9.1.min.js"></script>

<style>

    .frame{
        height:450px;
        margin:0px;
        padding:0px;
        background-image: url(/media/banner/<?php echo $image; ?>);
        background-repeat: no-repeat;
    }

    .selectiondiv{
        border:2px solid white;
        background:red;
        opacity:0.4;
        filter:alpha(opacity=40);
        margin:0px;
        padding:0px;
        display:none;
    }

</style>

<div onmousedown="return false" id="YDR-Frame" class="frame">
    <div id="selection" class="selectiondiv"></div>
</div>
<div id="status2"></div>

<script>

    var x1, x2, y1, y2;

    var selection = false;

    var gMOUSEUP = false;
    var gMOUSEDOWN = false;

    $(document).mouseup(function () {
        gMOUSEUP = true;
        gMOUSEDOWN = false;
    });
    $(document).mousedown(function () {
        gMOUSEUP = false;
        gMOUSEDOWN = true;

    });

    $("#YDR-Frame").mousedown(function (e) {
        selection = true;

        x1 = e.pageX - this.offsetLeft;
        y1 = e.pageY - this.offsetTop;

    });

    $('#YDR-Frame').mousemove(function (e) {
        if (selection) {

            x2 = e.pageX - this.offsetLeft;
            y2 = e.pageY - this.offsetTop;


            (x2 < 0) ? selection = false : ($(this).width() < x2) ? selection = false : (y2 < 0) ? selection = false : ($(this).height() < y2) ? selection = false : selection = true;
            ;


            if (selection) {

                var TOP = (y1 < y2) ? y1 : y2;
                var LEFT = (x1 < x2) ? x1 : x2;
                var WIDTH = (x1 < x2) ? x2 - x1 : x1 - x2;
                var HEIGHT = (y1 < y2) ? y2 - y1 : y1 - y2;

                $("#selection").css({
                    position: 'relative',
                    zIndex: 5000,
                    left: LEFT,
                    top: TOP,
                    width: WIDTH,
                    height: HEIGHT
                });
                $("#selection").show();

                // Info output
                $('#status2').html(x1 + ':' + y1 + ':' + x2 + ':' + y2);

            }


        }
    });
// Selection complete, hide the selection div (or fade it out)
    $('#YDR-Frame').mouseup(function () {
        selection = false;
        //$("#selection").hide();
        $('#area1').attr("coords", x1 + "," + y1 + "," + x2 + "," + y2);
    });
// Usability fix. If mouse leaves the selection and enters the selection frame again with mousedown
    $("#YDR-Frame").mouseenter(function () {
        (gMOUSEDOWN) ? selection = true : selection = false;
    });
// Usability fix. If mouse leaves the selection and enters the selection div again with mousedown
    $("#selection").mouseenter(function () {
        (gMOUSEDOWN) ? selection = true : selection = false;
    });
// Set selection to false, to prevent further selection outside of your selection frame
    $('#YDR-Frame').mouseleave(function () {
        selection = false;
    });
    
</script>


<br /><br />



