<style>
$text-color: #7d84ab;
$secondary-text-color: #dee2ec;

$bg-color: #0c1e35;
$secondary-bg-color: #0b1a2c;

$border-color: rgba(#535d7d, 0.3);

$sidebar-header-height: 100px;
$sidebar-footer-height: 230px;


.layout {
  z-index: 1;
  .header {
    display: flex;
    align-items: center;
    padding: 20px;
  }
  .content {
    padding: 12px 50px;
    display: flex;
    flex-direction: column;
  }

  .footer {
    text-align: center;
    margin-top: auto;
    margin-bottom: 20px;
    padding: 20px;
  }
}


.sidebar {
  color: $text-color;
  overflow-x: hidden !important;
  position: relative;

  &::-webkit-scrollbar-thumb {
    border-radius: 4px;
  }
  &:hover {
    &::-webkit-scrollbar-thumb {
      background-color: lighten($bg-color, 15);
    }
  }
  &::-webkit-scrollbar {
    width: 6px;
    background-color: $bg-color;
  }

  .image-wrapper {
    overflow: hidden;
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1;
    display: none;
    > img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
    }
  }
  &.has-bg-image .image-wrapper {
    display: block;
  }

  .sidebar-layout {
    height: auto;
    min-height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
    background-color: $bg-color;
    z-index: 2;

    .sidebar-header {
      height: $sidebar-header-height;
      min-height: $sidebar-header-height;
      display: flex;
      align-items: center;
      padding: 0 20px;
      > span {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
      }
    }
    .sidebar-content {
      flex-grow: 1;
      padding: 10px 0;
    }
    .sidebar-footer {
      height: $sidebar-footer-height;
      min-height: $sidebar-footer-height;
      display: flex;
      align-items: center;
      padding: 0 20px;
      > span {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
      }
    }
  }
}

@keyframes swing {
  0%,
  30%,
  50%,
  70%,
  100% {
    transform: rotate(0deg);
  }

  10% {
    transform: rotate(10deg);
  }

  40% {
    transform: rotate(-10deg);
  }

  60% {
    transform: rotate(5deg);
  }

  80% {
    transform: rotate(-5deg);
  }
}

.layout {
  .sidebar {
    .menu {
      ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
      }
      .menu-header {
        font-weight: 600;
        padding: 10px 25px;
        font-size: 0.8em;
        letter-spacing: 2px;
        transition: opacity 0.3s;
        opacity: 0.5;
      }
      .menu-item {
        a {
          display: flex;
          align-items: center;
          height: 50px;
          padding: 0 20px;
          color: $text-color;

          .menu-icon {
            font-size: 1.2rem;
            width: 35px;
            min-width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            display: inline-block;
            margin-right: 10px;
            border-radius: 2px;
            transition: color 0.3s;
            i {
              display: inline-block;
            }
          }

          .menu-title {
            font-size: 0.9em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex-grow: 1;
            transition: color 0.3s;
          }
          .menu-prefix,
          .menu-suffix {
            display: inline-block;
            padding: 5px;
            opacity: 1;
            transition: opacity 0.3s;
          }
          &:hover {
            .menu-title {
              color: $secondary-text-color;
            }
            .menu-icon {
              color: $secondary-text-color;
              i {
                animation: swing ease-in-out 0.5s 1 alternate;
              }
            }
            &::after {
              border-color: $secondary-text-color !important;
            }
          }
        }

        &.sub-menu {
          position: relative;
          > a {
            &::after {
              content: '';
              transition: transform 0.3s;
              border-right: 2px solid currentcolor;
              border-bottom: 2px solid currentcolor;
              width: 5px;
              height: 5px;
              transform: rotate(-45deg);
            }
          }

          > .sub-menu-list {
            padding-left: 20px;
            display: none;
            overflow: hidden;
            z-index: 999;
          }
          &.open {
            > a {
              color: $secondary-text-color;
              &::after {
                transform: rotate(45deg);
              }
            }
          }
        }

        &.active {
          > a {
            .menu-title {
              color: $secondary-text-color;
            }
            &::after {
              border-color: $secondary-text-color;
            }
            .menu-icon {
              color: $secondary-text-color;
            }
          }
        }
      }
      > ul > .sub-menu > .sub-menu-list {
        background-color: $secondary-bg-color;
      }

      &.icon-shape-circle,
      &.icon-shape-rounded,
      &.icon-shape-square {
        .menu-item a .menu-icon {
          background-color: $secondary-bg-color;
        }
      }

      &.icon-shape-circle .menu-item a .menu-icon {
        border-radius: 50%;
      }
      &.icon-shape-rounded .menu-item a .menu-icon {
        border-radius: 4px;
      }
      &.icon-shape-square .menu-item a .menu-icon {
        border-radius: 0;
      }
    }

    &:not(.collapsed) {
      .menu > ul {
        > .menu-item {
          &.sub-menu {
            > .sub-menu-list {
              visibility: visible !important;
              position: static !important;
              transform: translate(0, 0) !important;
            }
          }
        }
      }
    }

    &.collapsed {
      .menu > ul {
        > .menu-header {
          opacity: 0;
        }
        > .menu-item {
          > a {
            .menu-prefix,
            .menu-suffix {
              opacity: 0;
            }
          }
          &.sub-menu {
            > a {
              &::after {
                content: '';
                width: 5px;
                height: 5px;
                background-color: currentcolor;
                border-radius: 50%;
                display: inline-block;
                position: absolute;
                right: 10px;
                top: 50%;
                border: none;
                transform: translateY(-50%);
              }
              &:hover {
                &::after {
                  background-color: $secondary-text-color;
                }
              }
            }
            > .sub-menu-list {
              transition: none !important;
              width: 200px;
              margin-left: 3px !important;
              border-radius: 4px;
              display: block !important;
            }
          }
          &.active {
            > a {
              &::after {
                background-color: $secondary-text-color;
              }
            }
          }
        }
      }
    }
    &.has-bg-image {
      .menu {
        &.icon-shape-circle,
        &.icon-shape-rounded,
        &.icon-shape-square {
          .menu-item a .menu-icon {
            background-color: rgba($secondary-bg-color, 0.6);
          }
        }
      }
      &:not(.collapsed) {
        .menu {
          > ul > .sub-menu > .sub-menu-list {
            background-color: rgba($secondary-bg-color, 0.6);
          }
        }
      }
    }
  }

  &.rtl {
    .sidebar {
      .menu {
        .menu-item {
          a {
            .menu-icon {
              margin-left: 10px;
              margin-right: 0;
            }
          }

          &.sub-menu {
            > a {
              &::after {
                transform: rotate(135deg);
              }
            }

            > .sub-menu-list {
              padding-left: 0;
              padding-right: 20px;
            }
            &.open {
              > a {
                &::after {
                  transform: rotate(45deg);
                }
              }
            }
          }
        }
      }

      &.collapsed {
        .menu > ul {
          > .menu-item {
            &.sub-menu {
              a::after {
                right: auto;
                left: 10px;
              }

              > .sub-menu-list {
                margin-left: -3px !important;
              }
            }
          }
        }
      }
    }
  }
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  height: 100vh;
  font-family: 'Poppins', sans-serif;
  color: #3f4750;
  font-size: 0.9rem;
}

a {
  text-decoration: none;
}

@media (max-width: 576px) {
  #btn-collapse {
    display: none;
  }
}


.layout {
  .sidebar {
    .pro-sidebar-logo {
      display: flex;
      align-items: center;

      > div {
        width: 35px;
        min-width: 35px;
        height: 35px;
        min-height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: white;
        font-size: 24px;
        font-weight: 700;
        background-color: #ff8100;
        margin-right: 10px;
      }

      > h5 {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 20px;
        line-height: 30px;
        transition: opacity 0.3s;
        opacity: 1;
      }
    }

    .footer-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 0.8em;
      padding: 20px 0;
      border-radius: 8px;
      width: 180px;
      min-width: 190px;
      margin: 0 auto;
      background-color: #162d4a;
      img.react-logo {
        width: 40px;
        height: 40px;
        margin-bottom: 10px;
      }
      a {
        color: #fff;
        font-weight: 600;
        margin-bottom: 10px;
      }
    }

    .sidebar-collapser {
      transition: left, right, 0.3s;
      position: fixed;
      left: 260px;
      top: 40px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background-color: #00829f;
      display: -webkit-box;
      display: -ms-flexbox;
      display: flex;
      -ms-flex-align: center;
      align-items: center;
      justify-content: center;
      font-size: 1.2em;
      transform: translateX(50%);
      z-index: 111;
      cursor: pointer;
      color: white;
      box-shadow: 1px 1px 4px $bg-color;
    }

    &.collapsed {
      .pro-sidebar-logo {
        > h5 {
          opacity: 0;
        }
      }
      .footer-box {
        display: none;
      }
      .sidebar-collapser {
        left: 60px;
        i {
          transform: rotate(180deg);
        }
      }
    }
  }
}

.badge {
  display: inline-block;
  padding: 0.25em 0.4em;
  font-size: 75%;
  font-weight: 700;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.25rem;
  color: #fff;
  background-color: #6c757d;

  &.primary {
    background-color: #ab2dff;
  }

  &.secondary {
    background-color: #079b0b;
  }
}

.sidebar-toggler {
  position: fixed;
  right: 20px;
  top: 20px;
}

.social-links {
  a {
    margin: 0 10px;
    color: #3f4750;
  }
}

</style>
<div class="layout has-sidebar fixed-sidebar fixed-header">
      <aside id="sidebar" class="sidebar break-point-sm has-bg-image">
        <a id="btn-collapse" class="sidebar-collapser"><i class="ri-arrow-left-s-line"></i></a>
        <div class="image-wrapper">
          <img src="assets/images/sidebar-bg.jpg" alt="sidebar background" />
        </div>
        <div class="sidebar-layout">
          <div class="sidebar-header">
            <div class="pro-sidebar-logo">
              <div>P</div>
              <h5>Pro Sidebar</h5>
            </div>
          </div>
          <div class="sidebar-content">
            <nav class="menu open-current-submenu">
              <ul>
                <li class="menu-header"><span> GENERAL </span></li>
                <li class="menu-item sub-menu">
                  <a href="#">
                    <span class="menu-icon">
                      <i class="ri-vip-diamond-fill"></i>
                    </span>
                    <span class="menu-title">Components</span>
                    <span class="menu-suffix">
                      <span class="badge primary">Hot</span>
                    </span>
                  </a>
                  <div class="sub-menu-list">
                    <ul>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Grid</span>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Layout</span>
                        </a>
                      </li>
                      <li class="menu-item sub-menu">
                        <a href="#">
                          <span class="menu-title">Forms</span>
                        </a>
                        <div class="sub-menu-list">
                          <ul>
                            <li class="menu-item">
                              <a href="#">
                                <span class="menu-title">Input</span>
                              </a>
                            </li>
                            <li class="menu-item">
                              <a href="#">
                                <span class="menu-title">Select</span>
                              </a>
                            </li>
                            <li class="menu-item sub-menu">
                              <a href="#">
                                <span class="menu-title">More</span>
                              </a>
                              <div class="sub-menu-list">
                                <ul>
                                  <li class="menu-item">
                                    <a href="#">
                                      <span class="menu-title">CheckBox</span>
                                    </a>
                                  </li>
                                  <li class="menu-item">
                                    <a href="#">
                                      <span class="menu-title">Radio</span>
                                    </a>
                                  </li>
                                  <li class="menu-item sub-menu">
                                    <a href="#">
                                      <span class="menu-title">Want more ?</span>
                                      <span class="menu-suffix">&#x1F914;</span>
                                    </a>
                                    <div class="sub-menu-list">
                                      <ul>
                                        <li class="menu-item">
                                          <a href="#">
                                            <span class="menu-prefix">&#127881;</span>
                                            <span class="menu-title">You made it </span>
                                          </a>
                                        </li>
                                      </ul>
                                    </div>
                                  </li>
                                </ul>
                              </div>
                            </li>
                          </ul>
                        </div>
                      </li>
                    </ul>
                  </div>
                </li>
                <li class="menu-item sub-menu">
                  <a href="#">
                    <span class="menu-icon">
                      <i class="ri-bar-chart-2-fill"></i>
                    </span>
                    <span class="menu-title">Charts</span>
                  </a>
                  <div class="sub-menu-list">
                    <ul>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Pie chart</span>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Line chart</span>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Bar chart</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </li>
                <li class="menu-item sub-menu">
                  <a href="#">
                    <span class="menu-icon">
                      <i class="ri-shopping-cart-fill"></i>
                    </span>
                    <span class="menu-title">E-commerce</span>
                  </a>
                  <div class="sub-menu-list">
                    <ul>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Products</span>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Orders</span>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">credit card</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </li>
                <li class="menu-item sub-menu">
                  <a href="#">
                    <span class="menu-icon">
                      <i class="ri-global-fill"></i>
                    </span>
                    <span class="menu-title">Maps</span>
                  </a>
                  <div class="sub-menu-list">
                    <ul>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Google maps</span>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Open street map</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </li>
                <li class="menu-item sub-menu">
                  <a href="#">
                    <span class="menu-icon">
                     <i class="ri-paint-brush-fill"></i>
                    </span>
                    <span class="menu-title">Theme</span>
                  </a>
                  <div class="sub-menu-list">
                    <ul>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Dark</span>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="#">
                          <span class="menu-title">Light</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </li>
                <li class="menu-header" style="padding-top: 20px"><span> EXTRA </span></li>
                <li class="menu-item">
                  <a href="#">
                    <span class="menu-icon">
                      <i class="ri-book-2-fill"></i>
                    </span>
                    <span class="menu-title">Documentation</span>
                    <span class="menu-suffix">
                      <span class="badge secondary">Beta</span>
                    </span>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="#">
                    <span class="menu-icon">
                      <i class="ri-calendar-fill"></i>
                    </span>
                    <span class="menu-title">Calendar</span>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="#">
                    <span class="menu-icon">
                      <i class="ri-service-fill"></i>
                    </span>
                    <span class="menu-title">Examples</span>
                  </a>
                </li>
              </ul>
            </nav>
          </div>
          <div class="sidebar-footer">
            <div class="footer-box">
              <div>
                <img
                  class="react-logo"
                  src="https://user-images.githubusercontent.com/25878302/213938106-ca8f0485-3f30-4861-9188-2920ed7ab284.png"
                  alt="react"
                />
              </div>
              <div style="padding: 0 10px">
                <span style="display: block; margin-bottom: 10px"
                  >Pro sidebar is also available as a react package
                </span>
                <div style="margin-bottom: 15px">
                  <img
                    alt="preview badge"
                    src="https://img.shields.io/github/stars/azouaoui-med/react-pro-sidebar?style=social"
                  />
                </div>
                <div>
                  <a href="https://github.com/azouaoui-med/react-pro-sidebar" target="_blank"
                    >Check it out!</a
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </aside>
      <div id="overlay" class="overlay"></div>
      <div class="layout">
        <main class="content">
          <div>
            <a id="btn-toggle" href="#" class="sidebar-toggler break-point-sm">
              <i class="ri-menu-line ri-xl"></i>
            </a>
            <h1 style="margin-bottom: 0">Pro Sidebar</h1>
            <span style="display: inline-block">
              Responsive layout with advanced sidebar menu built with SCSS and vanilla Javascript
            </span>
            <br />
            <span>
              Full Code and documentation available on
              <a href="https://github.com/azouaoui-med/pro-sidebar-template" target="_blank"
                >Github</a
              >
            </span>
            <div style="margin-top: 10px">
              <a href="https://github.com/azouaoui-med/pro-sidebar-template" target="_blank">
                <img
                  alt="GitHub stars"
                  src="https://img.shields.io/github/stars/azouaoui-med/pro-sidebar-template?style=social"
                />
              </a>
              <a href="https://github.com/azouaoui-med/pro-sidebar-template" target="_blank">
                <img
                  alt="GitHub forks"
                  src="https://img.shields.io/github/forks/azouaoui-med/pro-sidebar-template?style=social"
                />
              </a>
            </div>
          </div>
          <div>
            <h2>Features</h2>
            <ul>
              <li>Fully responsive</li>
              <li>Collapsable sidebar</li>
              <li>Multi level menu</li>
              <li>RTL support</li>
              <li>Customizable</li>
            </ul>
          </div>
          <div>
            <h2>Resources</h2>
            <ul>
              <li>
                <a target="_blank" href="https://github.com/azouaoui-med/css-pro-layout">
                  Css Pro Layout</a
                >
              </li>
              <li>
                <a target="_blank" href="https://github.com/popperjs/popper-core"> Popper Core</a>
              </li>
              <li>
                <a target="_blank" href="https://remixicon.com/"> Remix Icons</a>
              </li>
            </ul>
          </div>
          <footer class="footer">
            <small style="margin-bottom: 20px; display: inline-block">
              © 2023 made with
              <span style="color: red; font-size: 18px">&#10084;</span> by -
              <a target="_blank" href="https://azouaoui.netlify.com"> Mohamed Azouaoui </a>
            </small>
            <br />
            <div class="social-links">
              <a href="https://github.com/azouaoui-med" target="_blank">
                <i class="ri-github-fill ri-xl"></i>
              </a>
              <a href="https://twitter.com/azouaoui_med" target="_blank">
                <i class="ri-twitter-fill ri-xl"></i>
              </a>
              <a href="https://codepen.io/azouaoui-med" target="_blank">
                <i class="ri-codepen-fill ri-xl"></i>
              </a>
              <a href="https://www.linkedin.com/in/mohamed-azouaoui/" target="_blank">
                <i class="ri-linkedin-box-fill ri-xl"></i>
              </a>
            </div>
          </footer>
        </main>
        <div class="overlay"></div>
      </div>
    </div>

<script>
const ANIMATION_DURATION = 300;

const SIDEBAR_EL = document.getElementById("sidebar");

const SUB_MENU_ELS = document.querySelectorAll(
  ".menu > ul > .menu-item.sub-menu"
);

const FIRST_SUB_MENUS_BTN = document.querySelectorAll(
  ".menu > ul > .menu-item.sub-menu > a"
);

const INNER_SUB_MENUS_BTN = document.querySelectorAll(
  ".menu > ul > .menu-item.sub-menu .menu-item.sub-menu > a"
);

class PopperObject {
  instance = null;
  reference = null;
  popperTarget = null;

  constructor(reference, popperTarget) {
    this.init(reference, popperTarget);
  }

  init(reference, popperTarget) {
    this.reference = reference;
    this.popperTarget = popperTarget;
    this.instance = Popper.createPopper(this.reference, this.popperTarget, {
      placement: "right",
      strategy: "fixed",
      resize: true,
      modifiers: [
        {
          name: "computeStyles",
          options: {
            adaptive: false
          }
        },
        {
          name: "flip",
          options: {
            fallbackPlacements: ["left", "right"]
          }
        }
      ]
    });

    document.addEventListener(
      "click",
      (e) => this.clicker(e, this.popperTarget, this.reference),
      false
    );

    const ro = new ResizeObserver(() => {
      this.instance.update();
    });

    ro.observe(this.popperTarget);
    ro.observe(this.reference);
  }

  clicker(event, popperTarget, reference) {
    if (
      SIDEBAR_EL.classList.contains("collapsed") &&
      !popperTarget.contains(event.target) &&
      !reference.contains(event.target)
    ) {
      this.hide();
    }
  }

  hide() {
    this.instance.state.elements.popper.style.visibility = "hidden";
  }
}

class Poppers {
  subMenuPoppers = [];

  constructor() {
    this.init();
  }

  init() {
    SUB_MENU_ELS.forEach((element) => {
      this.subMenuPoppers.push(
        new PopperObject(element, element.lastElementChild)
      );
      this.closePoppers();
    });
  }

  togglePopper(target) {
    if (window.getComputedStyle(target).visibility === "hidden")
      target.style.visibility = "visible";
    else target.style.visibility = "hidden";
  }

  updatePoppers() {
    this.subMenuPoppers.forEach((element) => {
      element.instance.state.elements.popper.style.display = "none";
      element.instance.update();
    });
  }

  closePoppers() {
    this.subMenuPoppers.forEach((element) => {
      element.hide();
    });
  }
}

const slideUp = (target, duration = ANIMATION_DURATION) => {
  const { parentElement } = target;
  parentElement.classList.remove("open");
  target.style.transitionProperty = "height, margin, padding";
  target.style.transitionDuration = `${duration}ms`;
  target.style.boxSizing = "border-box";
  target.style.height = `${target.offsetHeight}px`;
  target.offsetHeight;
  target.style.overflow = "hidden";
  target.style.height = 0;
  target.style.paddingTop = 0;
  target.style.paddingBottom = 0;
  target.style.marginTop = 0;
  target.style.marginBottom = 0;
  window.setTimeout(() => {
    target.style.display = "none";
    target.style.removeProperty("height");
    target.style.removeProperty("padding-top");
    target.style.removeProperty("padding-bottom");
    target.style.removeProperty("margin-top");
    target.style.removeProperty("margin-bottom");
    target.style.removeProperty("overflow");
    target.style.removeProperty("transition-duration");
    target.style.removeProperty("transition-property");
  }, duration);
};
const slideDown = (target, duration = ANIMATION_DURATION) => {
  const { parentElement } = target;
  parentElement.classList.add("open");
  target.style.removeProperty("display");
  let { display } = window.getComputedStyle(target);
  if (display === "none") display = "block";
  target.style.display = display;
  const height = target.offsetHeight;
  target.style.overflow = "hidden";
  target.style.height = 0;
  target.style.paddingTop = 0;
  target.style.paddingBottom = 0;
  target.style.marginTop = 0;
  target.style.marginBottom = 0;
  target.offsetHeight;
  target.style.boxSizing = "border-box";
  target.style.transitionProperty = "height, margin, padding";
  target.style.transitionDuration = `${duration}ms`;
  target.style.height = `${height}px`;
  target.style.removeProperty("padding-top");
  target.style.removeProperty("padding-bottom");
  target.style.removeProperty("margin-top");
  target.style.removeProperty("margin-bottom");
  window.setTimeout(() => {
    target.style.removeProperty("height");
    target.style.removeProperty("overflow");
    target.style.removeProperty("transition-duration");
    target.style.removeProperty("transition-property");
  }, duration);
};

const slideToggle = (target, duration = ANIMATION_DURATION) => {
  if (window.getComputedStyle(target).display === "none")
    return slideDown(target, duration);
  return slideUp(target, duration);
};

const PoppersInstance = new Poppers();

/**
 * wait for the current animation to finish and update poppers position
 */
const updatePoppersTimeout = () => {
  setTimeout(() => {
    PoppersInstance.updatePoppers();
  }, ANIMATION_DURATION);
};

/**
 * sidebar collapse handler
 */
document.getElementById("btn-collapse").addEventListener("click", () => {
  SIDEBAR_EL.classList.toggle("collapsed");
  PoppersInstance.closePoppers();
  if (SIDEBAR_EL.classList.contains("collapsed"))
    FIRST_SUB_MENUS_BTN.forEach((element) => {
      element.parentElement.classList.remove("open");
    });

  updatePoppersTimeout();
});

/**
 * sidebar toggle handler (on break point )
 */
document.getElementById("btn-toggle").addEventListener("click", () => {
  SIDEBAR_EL.classList.toggle("toggled");

  updatePoppersTimeout();
});

/**
 * toggle sidebar on overlay click
 */
document.getElementById("overlay").addEventListener("click", () => {
  SIDEBAR_EL.classList.toggle("toggled");
});

const defaultOpenMenus = document.querySelectorAll(".menu-item.sub-menu.open");

defaultOpenMenus.forEach((element) => {
  element.lastElementChild.style.display = "block";
});

/**
 * handle top level submenu click
 */
FIRST_SUB_MENUS_BTN.forEach((element) => {
  element.addEventListener("click", () => {
    if (SIDEBAR_EL.classList.contains("collapsed"))
      PoppersInstance.togglePopper(element.nextElementSibling);
    else {
      const parentMenu = element.closest(".menu.open-current-submenu");
      if (parentMenu)
        parentMenu
          .querySelectorAll(":scope > ul > .menu-item.sub-menu > a")
          .forEach(
            (el) =>
              window.getComputedStyle(el.nextElementSibling).display !==
                "none" && slideUp(el.nextElementSibling)
          );
      slideToggle(element.nextElementSibling);
    }
  });
});

/**
 * handle inner submenu click
 */
INNER_SUB_MENUS_BTN.forEach((element) => {
  element.addEventListener("click", () => {
    slideToggle(element.nextElementSibling);
  });
});

</script>