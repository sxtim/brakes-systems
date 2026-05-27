(function () {
  const SWIPER_CSS_URL = "https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css";
  const SWIPER_JS_URL = "https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js";
  const FANCYBOX_CSS_URL = "https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css";
  const FANCYBOX_JS_URL = "https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js";

  const loadedCss = new Set();
  const loadedScripts = new Map();

  const loadCss = (url) => {
    if (loadedCss.has(url) || document.querySelector(`link[href="${url}"]`)) {
      loadedCss.add(url);
      return;
    }

    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = url;
    document.head.appendChild(link);
    loadedCss.add(url);
  };

  const loadScript = (url, globalReady) => {
    if (typeof globalReady === "function" && globalReady()) {
      return Promise.resolve();
    }

    if (loadedScripts.has(url)) {
      return loadedScripts.get(url);
    }

    const promise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = url;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error(`Failed to load ${url}`));
      document.head.appendChild(script);
    });

    loadedScripts.set(url, promise);
    return promise;
  };

  const onNearViewport = (target, callback, rootMargin = "900px 0px") => {
    if (!target) {
      return;
    }

    if (!("IntersectionObserver" in window)) {
      callback();
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) {
          return;
        }

        observer.disconnect();
        callback();
      },
      { rootMargin }
    );

    observer.observe(target);
  };

  const initHeroVideo = () => {
    const video = document.querySelector("[data-home-hero-video]");
    if (!video) {
      return;
    }

    const getVideoSrc = () => {
      const isMobile = window.matchMedia("(max-width: 767.98px)").matches;
      return isMobile ? video.dataset.srcMobile : video.dataset.srcDesktop;
    };

    const loadVideo = () => {
      const src = getVideoSrc();
      if (!src || video.dataset.videoBootstrapped === "Y") {
        return;
      }

      video.dataset.videoBootstrapped = "Y";
      video.addEventListener(
        "loadeddata",
        () => {
          video.classList.add("is-ready");
        },
        { once: true }
      );

      video.src = src;
      video.load();
      video.play().catch(() => {
        // Keep the poster visible when autoplay is blocked.
      });
    };

    const scheduleVideoLoad = () => {
      const delay = window.matchMedia("(max-width: 767.98px)").matches ? 3500 : 0;
      window.setTimeout(loadVideo, delay);
    };

    if (document.readyState === "complete") {
      scheduleVideoLoad();
    } else {
      window.addEventListener("load", scheduleVideoLoad, { once: true });
    }
  };

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

  const loadProjectsSlider = () => {
    loadCss(SWIPER_CSS_URL);
    return loadScript(SWIPER_JS_URL, () => typeof window.Swiper === "function")
      .then(initProjectsSlider)
      .catch(() => {});
  };

  const loadFancybox = () => {
    loadCss(FANCYBOX_CSS_URL);
    return loadScript(FANCYBOX_JS_URL, () => Boolean(window.Fancybox && typeof window.Fancybox.bind === "function"))
      .then(initFancybox)
      .catch(() => {});
  };

  const boot = () => {
    initHeroVideo();
    onNearViewport(document.querySelector(".about_projects"), loadProjectsSlider, "1200px 0px");
    onNearViewport(document.querySelector(".sertificates_block"), loadFancybox, "1200px 0px");

    document.addEventListener("pointerover", (event) => {
      if (event.target.closest("[data-fancybox]")) {
        loadFancybox();
      }
    });
    document.addEventListener("focusin", (event) => {
      if (event.target.closest("[data-fancybox]")) {
        loadFancybox();
      }
    });
    document.addEventListener(
      "click",
      (event) => {
        const link = event.target.closest("a[data-fancybox]");
        if (!link || (window.Fancybox && typeof window.Fancybox.bind === "function")) {
          return;
        }

        if (link.dataset.fancyboxPendingClick === "Y") {
          delete link.dataset.fancyboxPendingClick;
          return;
        }

        event.preventDefault();
        link.dataset.fancyboxPendingClick = "Y";
        loadFancybox().then(() => {
          link.click();
        });
      },
      true
    );
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
