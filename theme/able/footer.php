<div class="footer">
    <div class="row justify-content-center align-items-center  w-100">
        <div class="col text-left" style="padding: 50px; ">
            <img src="./assets/4.png" alt="">
        </div>
        <div class="col text-left" style="padding: 50px; color: #fff;">
            <a class="nav-link active" aria-current="page" href="#"><b>Quick Link</b></a>
            <a class="nav-link active" aria-current="page" href="#">Home</a>
            <a class="nav-link active" aria-current="page" href="#">About Us</a>
            <a class="nav-link active" aria-current="page" href="#">Active Ctizen</a>
            <a class="nav-link active" aria-current="page" href="#">Project & Event</a>
        </div>
        <div class="col text-left" style="padding: 50px; color: #fff;">
            <a class="nav-link active" aria-current="page" href="#"><b>Contact Us</b></a>
            <a class="nav-link active" aria-current="page">Mohammudpur, Dhaka Bangladesh</a>
            <a class="nav-link active" aria-current="page" href="#"><b>FAQ</b></a>
            <iframe class="ml-3"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d116833.83187878219!2d90.337287993974!3d23.78097572837469!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b8b087026b81%3A0x8fa563bbdd5904c2!2sDhaka%2C%20Bangladesh!5e0!3m2!1sen!2smy!4v1708329104954!5m2!1sen!2smy"
                width="200" height="150" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
    <div class="copy-ritht">
        Copyright @2024 JCI Bangladesh all right reserved
    </div>

</div>



<script>
    document.querySelectorAll('.custom-tooltip').forEach(el => {
        const title = el.getAttribute('title');
        if (title) {
        el.setAttribute('data-title', title);
        el.removeAttribute('title');
        }
    });
    
    $('.famous-personnel').slick({
        dots: true,
        autoplay: true,
        infinite: true,
        speed: 200,
        slidesToShow: 1,
        centerMode: true,
        centerPadding: '10px',
        variableWidth: true,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    infinite: true,
                    dots: true
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
        ]
    });

    if($('.slick-slider').size>0)
    $('.slick-slider').slick({
        centerMode: true,
        centerPadding: '10px',
        slidesToShow: 7,
        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '10px',
                    slidesToShow: 3
                }
            },
            {
                breakpoint: 992,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 2
                }
            },
            {
                breakpoint: 768,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 2
                }
            },
            {
                breakpoint: 480,
                settings: {
                    arrows: true,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 1
                }
            }
        ]
    });
</script>
<script>
    function toggleNav() {
        var sidenav = document.getElementById("mySidenav");
        if (sidenav.style.display == "block") {
            sidenav.style.display = "none"
        } else {
            sidenav.style.display = "block"
        }
        //   if (sidenav.style.display === "250px") {
        //     sidenav.style.width = "0";
        //   } else {
        //     sidenav.style.width = "250px";
        //   }
    }
</script>

</body>

</html>