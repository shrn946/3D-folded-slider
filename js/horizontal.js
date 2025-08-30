
(function() {
  const wrapper = document.getElementById("fold-effect");
  const folds = Array.from(document.getElementsByClassName("fold"));
  const baseContent = document.getElementById("base-content");

  if (!wrapper || !folds.length || !baseContent) {
    console.warn("Fold slider: Missing required DOM elements.");
    return;
  }

  let state = {
    disposed: false,
    targetScroll: 0,
    scroll: 0
  };

  function lerp(current, target, speed = 0.1, limit = 0.001) {
    let change = (target - current) * speed;
    if (Math.abs(change) < limit) {
      change = target - current;
    }
    return change;
  }

  class FoldedDom {
    constructor(wrapper, folds) {
      this.wrapper = wrapper;
      this.folds = folds;
      this.scrollers = [];
    }
    setContent(baseContent, createScrollers = true) {
      let scrollers = [];
      for (let i = 0; i < this.folds.length; i++) {
        const fold = this.folds[i];
        const copyContent = baseContent.cloneNode(true);
        copyContent.id = "";

        let scroller;
        if (createScrollers) {
          let sizeFixEle = document.createElement("div");
          sizeFixEle.classList.add("fold-size-fix");

          scroller = document.createElement("div");
          scroller.classList.add("fold-scroller");

          sizeFixEle.append(scroller);
          fold.append(sizeFixEle);
        } else {
          scroller = this.scrollers[i];
        }

        scroller.append(copyContent);
        scrollers[i] = scroller;
      }
      this.scrollers = scrollers;
    }
    updateStyles(scroll) {
      for (let i = 0; i < this.scrollers.length; i++) {
        const scroller = this.scrollers[i];
        if (scroller && scroller.children[0]) {
          scroller.children[0].style.transform = `translateX(${scroll}px)`;
        }
      }
    }
  }

  let insideFold;
  const mainFold = folds[folds.length - 1];

  let tick = () => {
    if (state.disposed) return;

    const maxScroll =
      -insideFold.scrollers[0].children[0].clientWidth + mainFold.clientWidth;

    state.targetScroll = Math.max(Math.min(0, state.targetScroll), maxScroll);
    state.scroll += lerp(state.scroll, state.targetScroll, 0.1, 0.0001);

    insideFold.updateStyles(state.scroll);

    requestAnimationFrame(tick);
  };

  /** EVENTS **/
  let lastClientX = null;
  let isDown = false;

  const onDown = () => { isDown = true; };
  const onUp = () => { isDown = false; lastClientX = null; };

  window.addEventListener("mousedown", onDown);
  window.addEventListener("mouseup", onUp);
  window.addEventListener("mouseleave", onUp);

  window.addEventListener("mousemove", ev => {
    if (lastClientX && isDown) {
      state.targetScroll += ev.clientX - lastClientX;
    }
    lastClientX = ev.clientX;
  });

  window.addEventListener("touchstart", () => { isDown = true; });
  window.addEventListener("touchend", onUp);
  window.addEventListener("touchcancel", onUp);

  window.addEventListener("touchmove", ev => {
    let touch = ev.touches[0];
    if (lastClientX && isDown) {
      state.targetScroll += touch.clientX - lastClientX;
    }
    lastClientX = touch.clientX;
  });

  window.addEventListener("wheel", ev => {
    state.targetScroll += -Math.sign(ev.deltaY) * 30;
  });

  /********** Preload Images **********/
  const preloadImages = () => {
    return new Promise(resolve => {
      if (typeof imagesLoaded !== "undefined") {
        imagesLoaded(document.querySelectorAll(".content__img"), resolve);
      } else {
        // fallback: resolve instantly
        resolve();
      }
    });
  };

  preloadImages().then(() => {
    document.body.classList.remove("loading");
    insideFold = new FoldedDom(wrapper, folds);
    insideFold.setContent(baseContent);
    tick();
  });
})();

