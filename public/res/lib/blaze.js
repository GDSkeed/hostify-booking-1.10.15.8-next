var BlazeSlider = (function () {
  "use strict";
  const t = "start";
  class e {
    constructor(t, e) {
      (this.config = e),
        (this.totalSlides = t),
        (this.isTransitioning = !1),
        n(this, t, e);
    }
    next(t = 1) {
      if (this.isTransitioning || this.isStatic) return;
      const { stateIndex: e } = this;
      let n = 0,
        i = e;
      for (let e = 0; e < t; e++) {
        const t = this.states[i];
        (n += t.next.moveSlides), (i = t.next.stateIndex);
      }
      return i !== e ? ((this.stateIndex = i), [e, n]) : void 0;
    }
    prev(t = 1) {
      if (this.isTransitioning || this.isStatic) return;
      const { stateIndex: e } = this;
      let n = 0,
        i = e;
      for (let e = 0; e < t; e++) {
        const t = this.states[i];
        (n += t.prev.moveSlides), (i = t.prev.stateIndex);
      }
      return i !== e ? ((this.stateIndex = i), [e, n]) : void 0;
    }
  }
  function n(t, e, n) {
    (t.stateIndex = 0),
      (function (t) {
        const { slidesToScroll: e, slidesToShow: n } = t.config,
          { totalSlides: i, config: s } = t;
        if (
          (i < n && (s.slidesToShow = i),
          !(i <= n) && (e > n && (s.slidesToScroll = n), i < e + n))
        ) {
          const t = i - n;
          s.slidesToScroll = t;
        }
      })(t),
      (t.isStatic = e <= n.slidesToShow),
      (t.states = (function (t) {
        const { totalSlides: e } = t,
          { loop: n } = t.config,
          i = (function (t) {
            const { slidesToShow: e, slidesToScroll: n, loop: i } = t.config,
              { isStatic: s, totalSlides: o } = t,
              r = [],
              a = o - 1;
            for (let t = 0; t < o; t += n) {
              const n = t + e - 1;
              if (n > a) {
                if (!i) {
                  const t = a - e + 1,
                    n = r.length - 1;
                  (0 === r.length || (r.length > 0 && r[n][0] !== t)) &&
                    r.push([t, a]);
                  break;
                }
                {
                  const e = n - o;
                  r.push([t, e]);
                }
              } else r.push([t, n]);
              if (s) break;
            }
            return r;
          })(t),
          s = [],
          o = i.length - 1;
        for (let t = 0; t < i.length; t++) {
          let r, a;
          n
            ? ((r = t === o ? 0 : t + 1), (a = 0 === t ? o : t - 1))
            : ((r = t === o ? o : t + 1), (a = 0 === t ? 0 : t - 1));
          const l = i[t][0],
            c = i[r][0],
            d = i[a][0];
          let u = c - l;
          c < l && (u += e);
          let f = l - d;
          d > l && (f += e),
            s.push({
              page: i[t],
              next: { stateIndex: r, moveSlides: u },
              prev: { stateIndex: a, moveSlides: f },
            });
        }
        return s;
      })(t));
  }
  function i(t) {
    if (t.onSlideCbs) {
      const e = t.states[t.stateIndex],
        [n, i] = e.page;
      t.onSlideCbs.forEach((e) => e(t.stateIndex, n, i));
    }
  }
  function s(t) {
    (t.offset = -1 * t.states[t.stateIndex].page[0]), o(t), i(t);
  }
  function o(t) {
    const { track: e, offset: n, dragged: i } = t;
    e.style.transform =
      0 === n
        ? `translate3d(${i}px,0px,0px)`
        : `translate3d(  calc( ${i}px + ${n} * (var(--slide-width) + ${t.config.slideGap})),0px,0px)`;
  }
  function r(t) {
    t.track.style.transitionDuration = `${t.config.transitionDuration}ms`;
  }
  function a(t) {
    t.track.style.transitionDuration = "0ms";
  }
  const l = 10,
    c = () => "ontouchstart" in window;
  function d(t) {
    const e = this,
      n = e.slider;
    if (!n.isTransitioning) {
      if (
        ((n.dragged = 0),
        (e.isScrolled = !1),
        (e.startMouseClientX =
          "touches" in t ? t.touches[0].clientX : t.clientX),
        !("touches" in t))
      ) {
        (t.target || e).setPointerCapture(t.pointerId);
      }
      a(n), p(e, "addEventListener");
    }
  }
  function u(t) {
    const e = this,
      n = "touches" in t ? t.touches[0].clientX : t.clientX,
      i = (e.slider.dragged = n - e.startMouseClientX),
      s = Math.abs(i);
    s > 5 && (e.slider.isDragging = !0),
      s > 15 && t.preventDefault(),
      (e.slider.dragged = i),
      o(e.slider),
      !e.isScrolled &&
        e.slider.config.loop &&
        i > l &&
        ((e.isScrolled = !0), e.slider.prev());
  }
  function f() {
    const t = this,
      e = t.slider.dragged;
    (t.slider.isDragging = !1),
      p(t, "removeEventListener"),
      (t.slider.dragged = 0),
      o(t.slider),
      r(t.slider),
      t.isScrolled || (e < -1 * l ? t.slider.next() : e > l && t.slider.prev());
  }
  const h = (t) => t.preventDefault();
  function p(t, e) {
    t[e]("contextmenu", f),
      c()
        ? (t[e]("touchend", f), t[e]("touchmove", u))
        : (t[e]("pointerup", f), t[e]("pointermove", u));
  }
  const g = {
    slideGap: "20px",
    slidesToScroll: 1,
    slidesToShow: 1,
    loop: !0,
    enableAutoplay: !1,
    stopAutoplayOnInteraction: !0,
    autoplayInterval: 3e3,
    autoplayDirection: "to left",
    enablePagination: !0,
    transitionDuration: 300,
    transitionTimingFunction: "ease",
    draggable: !0,
  };
  function v(t) {
    const e = { ...g };
    for (const n in t)
      if (window.matchMedia(n).matches) {
        const i = t[n];
        for (const t in i) e[t] = i[t];
      }
    return e;
  }
  function S() {
    const t = this.index,
      e = this.slider,
      n = e.stateIndex,
      i = e.config.loop,
      s = Math.abs(t - n),
      o = e.states.length - s,
      r = s > e.states.length / 2 && i;
    t > n ? (r ? e.prev(o) : e.next(s)) : r ? e.next(o) : e.prev(s);
  }
  function m(t, e = t.config.transitionDuration) {
    (t.isTransitioning = !0),
      setTimeout(() => {
        t.isTransitioning = !1;
      }, e);
  }
  function x(e, n) {
    const i = e.el.classList,
      s = e.stateIndex,
      o = e.paginationButtons;
    e.config.loop ||
      (0 === s ? i.add(t) : i.remove(t),
      s === e.states.length - 1 ? i.add("end") : i.remove("end")),
      o &&
        e.config.enablePagination &&
        (o[n].classList.remove("active"), o[s].classList.add("active"));
  }
  function y(e, i) {
    const s = i.track;
    (i.slides = s.children),
      (i.offset = 0),
      (i.config = e),
      n(i, i.totalSlides, e),
      e.loop || i.el.classList.add(t),
      e.enableAutoplay && !e.loop && (e.enableAutoplay = !1),
      (s.style.transitionProperty = "transform"),
      (s.style.transitionTimingFunction = i.config.transitionTimingFunction),
      (s.style.transitionDuration = `${i.config.transitionDuration}ms`);
    const { slidesToShow: r, slideGap: a } = i.config;
    i.el.style.setProperty("--slides-to-show", r + ""),
      i.el.style.setProperty("--slide-gap", a),
      i.isStatic
        ? i.el.classList.add("static")
        : e.draggable &&
          (function (t) {
            const e = t.track;
            e.slider = t;
            const n = c() ? "touchstart" : "pointerdown";
            e.addEventListener(n, d),
              e.addEventListener(
                "click",
                (e) => {
                  (t.isTransitioning || t.isDragging) &&
                    (e.preventDefault(),
                    e.stopImmediatePropagation(),
                    e.stopPropagation());
                },
                { capture: !0 }
              ),
              e.addEventListener("dragstart", h);
          })(i),
      (function (t) {
        if (!t.config.enablePagination || t.isStatic) return;
        const e = t.el.querySelector(".blaze-pagination");
        if (!e) return;
        t.paginationButtons = [];
        const n = t.states.length;
        for (let i = 0; i < n; i++) {
          const s = document.createElement("button");
          t.paginationButtons.push(s),
            (s.textContent = 1 + i + ""),
            (s.ariaLabel = `${i + 1} of ${n}`),
            e.append(s),
            (s.slider = t),
            (s.index = i),
            (s.onclick = S);
        }
        t.paginationButtons[0].classList.add("active");
      })(i),
      (function (t) {
        const e = t.config;
        if (!e.enableAutoplay) return;
        const n = "to left" === e.autoplayDirection ? "next" : "prev";
        (t.autoplayTimer = setInterval(() => {
          t[n]();
        }, e.autoplayInterval)),
          e.stopAutoplayOnInteraction &&
            t.el.addEventListener(
              c() ? "touchstart" : "mousedown",
              () => {
                clearInterval(t.autoplayTimer);
              },
              { once: !0 }
            );
      })(i),
      (function (t) {
        const e = t.el.querySelector(".blaze-prev"),
          n = t.el.querySelector(".blaze-next");
        e &&
          (e.onclick = () => {
            t.prev();
          }),
          n &&
            (n.onclick = () => {
              t.next();
            });
      })(i),
      o(i);
  }
  return class extends e {
    constructor(t, e) {
      const n = t.querySelector(".blaze-track"),
        i = n.children,
        s = e ? v(e) : { ...g };
      super(i.length, s),
        (this.config = s),
        (this.el = t),
        (this.track = n),
        (this.slides = i),
        (this.offset = 0),
        (this.dragged = 0),
        (this.isDragging = !1),
        (this.el.blazeSlider = this),
        (this.passedConfig = e);
      const o = this;
      (n.slider = o), y(s, o);
      let r = !1,
        a = 0;
      window.addEventListener("resize", () => {
        if (0 === a) return void (a = window.innerWidth);
        const t = window.innerWidth;
        a !== t &&
          ((a = t),
          r ||
            ((r = !0),
            setTimeout(() => {
              o.refresh(), (r = !1);
            }, 200)));
      });
    }
    next(t) {
      if (this.isTransitioning) return;
      const e = super.next(t);
      if (!e) return void m(this);
      const [n, l] = e;
      x(this, n),
        m(this),
        (function (t, e) {
          const n = requestAnimationFrame;
          t.config.loop
            ? ((t.offset = -1 * e),
              o(t),
              setTimeout(() => {
                !(function (t, e) {
                  for (let n = 0; n < e; n++) t.track.append(t.slides[0]);
                })(t, e),
                  a(t),
                  (t.offset = 0),
                  o(t),
                  n(() => {
                    n(() => {
                      r(t), i(t);
                    });
                  });
              }, t.config.transitionDuration))
            : s(t);
        })(this, l);
    }
    prev(t) {
      if (this.isTransitioning) return;
      const e = super.prev(t);
      if (!e) return void m(this);
      const [n, l] = e;
      x(this, n),
        m(this),
        (function (t, e) {
          const n = requestAnimationFrame;
          if (t.config.loop) {
            a(t),
              (t.offset = -1 * e),
              o(t),
              (function (t, e) {
                const n = t.slides.length;
                for (let i = 0; i < e; i++) {
                  const e = t.slides[n - 1];
                  t.track.prepend(e);
                }
              })(t, e);
            const s = () => {
              n(() => {
                r(t),
                  n(() => {
                    (t.offset = 0), o(t), i(t);
                  });
              });
            };
            t.isDragging
              ? c()
                ? t.track.addEventListener("touchend", s, { once: !0 })
                : t.track.addEventListener("pointerup", s, { once: !0 })
              : n(s);
          } else s(t);
        })(this, l);
    }
    stopAutoplay() {
      clearInterval(this.autoplayTimer);
    }
    destroy() {
      this.track.removeEventListener(c() ? "touchstart" : "pointerdown", d),
        this.stopAutoplay(),
        this.paginationButtons?.forEach((t) => t.remove()),
        this.el.classList.remove("static"),
        this.el.classList.remove(t);
    }
    refresh() {
      const t = this.passedConfig ? v(this.passedConfig) : { ...g };
      this.destroy(), y(t, this);
    }
    onSlide(t) {
      return (
        this.onSlideCbs || (this.onSlideCbs = new Set()),
        this.onSlideCbs.add(t),
        () => this.onSlideCbs.delete(t)
      );
    }
  };
})();
