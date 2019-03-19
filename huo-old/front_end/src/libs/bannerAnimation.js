// import particlesJS from 'particles.js';

const banners = function(id) {
  let option = {
      particles: {
        number: {
          value: 300,
          density: {
            enable: true,
            value_area: 3000
          }
        },
        //节点处
        color: {
          value: ["#e40045", "#A2ABB8"]
        },
        shape: {
          type: "polygon",
          stroke: {
            width: 0,
            color: "#000000"
          },
          polygon: {
            nb_sides: 4
          }
        },
        opacity: {
          value: 0.6,
          random: true,
          anim: {
            enable: false,
            speed: 1,
            opacity_min: 0.4,
            sync: false
          }
        },
        size: {
          value: 5,
          random: true,
          anim: {
            enable: false,
            speed: 4,
            size_min: 1,
            sync: false
          }
        },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#58636d",
          opacity: 0.6,
          width: 1
        },
        move: {
          enable: true,
          speed: 2,
          direction: "left",
          random: true,
          straight: true,
          out_mode: "out",
          bounce: false,
          attract: {
            enable: false,
            rotateX: 50,
            rotateY: 50
          }
        }
      },
      interactivity: {
        detect_on: "canvas",
        events: {
          onhover: {
            enable: false,
            mode: "grab"
          },
          onclick: {
            enable: true,
            mode: "repulse"
          },
          resize: true
        },
        modes: {
          grab: {
            distance: 200,
            line_linked: {
              opacity: 1
            }
          },
          bubble: {
            distance: 100,
            size: 40,
            duration: 2,
            opacity: 8,
            speed: 3
          },
          repulse: {
            distance: 200,
            duration: 0.4
          },
          push: {
            particles_nb: 4
          },
          remove: {
            particles_nb: 2
          }
        }
      },
      retina_detect: true
    };
  if (id) {
    return window.particlesJS(id, option);
  } else {
    return {};
  }
};
export default banners;
