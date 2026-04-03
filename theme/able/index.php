<!doctype html>
<html lang="en">
<!-- [Head] start -->

<head>
    <title>Horizontal Layout | Able Pro Dashboard Template</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description"
        content="Able Pro is trending dashboard template made using Bootstrap 5 design framework. Able Pro is available in Bootstrap, React, CodeIgniter, Angular,  and .net Technologies." />
    <meta name="keywords"
        content="Bootstrap admin template, Dashboard UI Kit, Dashboard Template, Backend Panel, react dashboard, angular dashboard" />
    <meta name="author" content="Phoenixcoded" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="<?php print ROOT; ?>/assets/images/favicon.svg" type="image/x-icon" />
    <!-- [Font] Family -->
    <link rel="stylesheet" href="<?php print ROOT; ?>/assets/fonts/inter/inter.css" id="main-font-link" />
    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="<?php print ROOT; ?>/assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="<?php print ROOT; ?>/assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="<?php print ROOT; ?>/assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="<?php print ROOT; ?>/assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="<?php print ROOT; ?>/assets/css/style.css?v=1" id="main-style-link" />
    <link rel="stylesheet" href="<?php print ROOT; ?>/assets/css/style-preset.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Tippy.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />

    <!-- Tippy.js JS -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <style type="text/css">
        .opacity-50{
            opacity: .3;
        }
        .radius-50{
            border-radius: 50%;
        }
        .nav-products>div{
            position: absolute;
            margin-top: -60px;
            display: flex;
        }
        .nav-products .nav-product:nth-child(odd){
/*            box-shadow: rgb(215, 215, 221) 2px 2px 8px, rgb(255, 255, 255) -4px -4px 8px; padding: 8px 10px; border-radius: 5px;*/
        }
        .nav-products .nav-product{
            height: 71px;
            box-shadow: rgba(0, 0, 0, 0.15) 2.5px 2.5px 5px 0px inset, rgb(255, 255, 255) -2.5px -2.5px 5px 0px inset !important; 
            padding: 8px 10px; 
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .pc-header{
            backdrop-filter: none !important;
            background: none !important;
        }
        a.btn{
            color: #fff;
        }
        .topnav ul{
            z-index: 99;
        }
        .frht{
            float: right;
        }
        .text-right, .rht,.right{
            text-align: right;
        }
        .text-left{
            text-align: left;
        }
        .text-center, .cntr, .center{
            text-align: center;
        }
        @media (min-width: 1025px) {
            [data-pc-layout=horizontal] .pc-container {
                top: calc(65px) !important;
            }
        }
        .nav-product{
            margin-right: 10px;
            margin-bottom: 5px;
            display: inline-block;
        }
        .inline-block,.in{
            display: inline-block;
        }

        .splide{
/*            width: 800px;*/
            margin-left: auto;
            margin-right: auto;
            height: 65px;
        }
        .splide-sell{
            height: 165px;
        }
        .splide__slide{
            margin-right: 15px !important;
        }
        /*
        .pc-navbar li.pc-hasmenu:first-child{
            position: absolute !important;
            top: 90px;
            border-radius: 5px;
        }
        .pc-navbar li.pc-hasmenu:first-child .pc-link *{
        }
        */
        .color-red{
            color: red !important;
        }
        .w100{ width: 100px;}
        .w32{ width: 32px;}
        .w64{ width: 64px;}
        .w150{ width: 150px;}
        .w250{ width: 250px;}
        .w100p{ width: 100%; }
        .form-control-fluid{
            width: auto !important;
            display: inline-block !important;
        }
        .hidden{
            display: none !important;
        }
        .radio-label span{
            cursor:pointer
        }
        td .order-item:first-child{
/*            border-top: solid 1px #ccc;*/
        }
        .item-count{

        }
        .order-item{
/*            border-bottom: solid 1px #ccc;*/
        }
        .item-price{
            font-weight: 700;
        }
        .item-qty{
            font-size: 1.1rem;
            color: red;
        }
        .op td.price{
          height: 57px;
        }
        .op td.unit{
          height: 57px;
        }
        td.price{
          font-size: 1.7rem !important;
          color: red;
          font-weight: 700;
        }
        
        td.price small{
          font-size: .7rem;
          padding-right: 3px;
        }
        .lexend {
          font-family: "Lexend", sans-serif;
          font-optical-sizing: auto;
          font-weight: 700;
          font-style: normal;
        }
        .menu-toggle{
            display: none;
        }
        .pre-wrap{
            white-space: pre-wrap !important;
        }
        /* Hide specific elements when printing */
        @media print {
            .no-print {
                display: none;
            }
        }
        @media only screen and (max-width: 600px) {
            .menu-toggle{
                display: inline-block;
            }
        }
        <?php if(PAGE=='auth'): ?>
            .pc-container{
                top: 0px !important;
            }
        <?php endif; ?>
    </style>
</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-layout="vertical" data-pc-direction="ltr"
    data-pc-theme_contrast="" data-pc-theme="light" data-pc-direction="ltr">
    <!-- [ Pre-loader ] start -->
    <div class="page-loader">
        <div class="bar"></div>
    </div>
    <!-- [ Pre-loader ] End -->
    <!-- [ Sidebar Menu ] start -->
    <?php if(PAGE!='auth'): ?>

    <a class='menu-toggle'>
        <span style="font-size: 1.5rem;margin-left: 12px;position: absolute;font-weight: 100;" class='menu-toggle'><i class='fas fa-bars'></i></span>
      </a>
    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="navbar-content">
                <?php require 'nav.php'; ?>
            </div>
        </div>
    </nav>
    <!-- [ Sidebar Menu ] end -->
    <?php endif; ?>
    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <?php 
            if(PAGE=='auth'){
                include 'app/pages/login.php'; 
            } else{
                include 'content.php'; 
            }
        ?>
    </div>
    <!-- [ Main Content ] end -->
    <footer class="pc-footer">
        <!-- <div class="footer-wrapper container-fluid">
            <div class="row">
                <div class="col my-1">
                    <p class="m-0">Able Pro &#9829; crafted by Team <a href="https://themeforest.net/user/phoenixcoded"
                            target="_blank">Phoenixcoded</a></p>
                </div>
                <div class="col-auto my-1">
                    <ul class="list-inline footer-link mb-0">
                        <li class="list-inline-item"><a href="index.html">Home</a></li>
                        <li class="list-inline-item"><a href="https://phoenixcoded.gitbook.io/able-pro/"
                                target="_blank">Documentation</a></li>
                        <li class="list-inline-item"><a href="https://phoenixcoded.authordesk.app/"
                                target="_blank">Support</a></li>
                    </ul>
                </div>
            </div>
        </div> -->
    </footer>
    <!-- Required Js -->
    <script src="<?php print ROOT; ?>/assets/js/plugins/popper.min.js"></script>
    <script src="<?php print ROOT; ?>/assets/js/plugins/simplebar.min.js"></script>
    <script src="<?php print ROOT; ?>/assets/js/plugins/bootstrap.min.js"></script>
    <script src="<?php print ROOT; ?>/assets/js/fonts/custom-font.js"></script>
    <script src="<?php print ROOT; ?>/assets/js/pcoded.js"></script>
    <script src="<?php print ROOT; ?>/assets/js/plugins/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        
    tippy('[data-tippy-content]', {
        placement: 'top',
        animation: 'shift-away',
        theme: 'light-border',
        delay: [100, 100],  // [show, hide] delay in ms
    });
    
        $(".menu-toggle").click(function(){
            $(".pc-sidebar").removeClass("d-none").css('display', "block").css("left", 0);
        });
        localStorage.setItem('layout', 'horizontal');
        // layout_change('false');
    </script>
    <script src="<?php print ROOT; ?>/assets/js/pages/calendar.js"></script>

    <!-- <script>
        layout_theme_contrast_change('false');
    </script>

    <script>
        change_box_container('false');
    </script>

    <script>
        layout_caption_change('true');
    </script>

    <script>
        layout_rtl_change('false');
    </script>

    <script>
        preset_change('preset-1');
    </script>

    <script>
        main_layout_change('vertical');
    </script>

    
    <script>
        localStorage.setItem('layout', 'horizontal');
    </script> -->
    <script src="<?php print ROOT; ?>/assets/js/plugins/feather.min.js"></script>


</body>
<!-- [Body] end -->

</html>