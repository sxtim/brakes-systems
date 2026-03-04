(function () {
  const initProjectsSlider = () => {
    const slider = document.querySelector(".home-main-projects-swiper");
    if (!slider || typeof window.Swiper !== "function") {
      return;
    }

    // Avoid duplicate initialization on composite/AJAX re-renders.
    if (slider.dataset.swiperInitialized === "Y") {
      return;
    }

    slider.dataset.swiperInitialized = "Y";

    // eslint-disable-next-line no-new
    new window.Swiper(slider, {
      slidesPerView: 2,
      spaceBetween: 60,
      navigation: {
        nextEl: ".home-main-projects-next",
        prevEl: ".home-main-projects-prev",
      },
      breakpoints: {
        0: {
          slidesPerView: 1,
          spaceBetween: 10,
        },
        576: {
          slidesPerView: 1.2,
          spaceBetween: 15,
        },
        768: {
          slidesPerView: 2,
          spaceBetween: 60,
        },
      },
    });
  };

  const initFancybox = () => {
    if (!window.Fancybox || typeof window.Fancybox.bind !== "function") {
      return;
    }

    window.Fancybox.bind("[data-fancybox='gallery-1'], [data-fancybox='gallery-2']", {
      Toolbar: true,
      Thumbs: false,
      closeButton: true,
      dragToClose: true,
    });
  };

  const boot = () => {
    initProjectsSlider();
    initFancybox();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
