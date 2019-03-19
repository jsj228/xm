// require('./index.styl')
import './index.scss';

/* eslint-disable */
class Progressive {
  constructor(option) {
    this.el = option.el;
    this.lazyClass = option.lazyClass || 'lazy';
    this.removePreview = option.removePreview || false;
    this.scale = option.scale || false;

    this.EVENTS = ['scroll', 'wheel', 'mousewheel', 'resize'];
    this.Util = {
      throttle(action, delay) {
        let timeout = null;
        let lastRun = 0;
        return function() {
          if (timeout) {
            return;
          }
          const elapsed = Date.now() - lastRun;
          const context = this;
          /* eslint-disable */
          const args = arguments;
          /* eslint-enable */
          const runCallback = function() {
            lastRun = Date.now();
            timeout = false;
            action.apply(context, args);
          };

          if (elapsed >= delay) {
            runCallback();
          } else {
            timeout = setTimeout(runCallback, delay);
          }
        };
      },
      on(el, ev, fn) {
        el.addEventListener(ev, fn);
      },
      off(el, ev, fn) {
        el.removeEventListener(ev, fn);
      }
    };

    this.windowHasBind = false;

    this.lazy = this.Util.throttle(_ => {
      this.fire();
    }, 300);

    this.animationEvent = this.getAnimationEvent();
  }

  fire() {
    if (!this.windowHasBind) {
      this.windowHasBind = true;
      this.events(window, true);
    }

    const lazys = document.querySelectorAll(`${this.el} img.${this.lazyClass}`);
    const l = lazys.length;
    if (l > 0) {
      for (let i = 0; i < l; i++) {
        const rect = lazys[i].getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0 && rect.right > 0) {
          this.loadImage(lazys[i]);
        }
      }
    } else {
      this.windowHasBind = false;
      this.events(window, false);
    }
  }

  events(el, bind) {
    if (bind) {
      this.EVENTS.forEach(evt => {
        this.Util.on(el, evt, this.lazy);
      });
    } else {
      this.EVENTS.forEach(evt => {
        this.Util.off(el, evt, this.lazy);
      });
    }
  }

  loadImage(item) {
    const img = new Image();

    if (navigator.userAgent.match(/iPhone/i)) {
      img.src = item.dataset.srcset.split(',')[0].replace('980w', '');
    } else {
      if (item.dataset) {
        /* eslint-disable */
        item.dataset.srcset && (img.srcset = item.dataset.srcset);
        item.dataset.sizes && (img.sizes = item.dataset.sizes);
        /* eslint-enable */
      }
      img.src = item.dataset.src;
    }

    img.className = 'origin';
    if (this.scale) {
      img.className = 'origin-scale';
    }
    item.classList.remove('lazy');
    img.onload = _ => {
      this.mountImage(item, img);
    };
    img.onerror = _ => {
      item.classList.add('lazy');
    };
  }

  getAnimationEvent() {
    const el = document.createElement('fake');

    /* eslint-disable */
    const animations = {
      "animation": "animationend",
      "OAnimation": "oAnimationEnd",
      "MozAnimation": "animationend",
      "WebkitAnimation": "webkitAnimationEnd"
    };

    /* eslint-enable */
    for (let a in animations) {
      if (el.style[a] !== undefined) {
        return animations[a];
      }
    }
  }

  mountImage(preview, img) {
    const parent = preview.parentNode;
    if (parent && parent.appendChild) {
      parent.appendChild(img).addEventListener(this.animationEvent, e => {
        e.target.alt = preview.alt || '';
        preview.classList.add('hide');
        if (this.removePreview) {
          parent.removeChild(preview);
          e.target.classList.remove('origin');
          e.target.classList.remove('origin-scale');
        }
      });
    }
  }
}

export default Progressive;
