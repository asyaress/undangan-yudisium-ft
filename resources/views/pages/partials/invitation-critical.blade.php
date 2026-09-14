<style id="invitation-critical">
  html,
  body {
    margin: 0;
    background: #ffffff;
    color: #1c1c1e;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
    -webkit-text-size-adjust: 100%;
  }

  .app-boot-screen {
    position: fixed;
    inset: 0;
    z-index: 10050;
    display: none;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    padding: 24px;
    box-sizing: border-box;
  }

  html.app-booting .app-boot-screen {
    display: flex;
  }

  html.app-booting body > main,
  html.app-booting body > .transition-layer {
    visibility: hidden;
  }

  .app-boot-screen__inner {
    display: grid;
    justify-items: center;
    gap: 12px;
    text-align: center;
  }

  .app-boot-spinner {
    width: 28px;
    height: 28px;
    border: 2px solid #e5e7eb;
    border-top-color: #f5530d;
    border-radius: 50%;
    animation: invitation-spin 0.75s linear infinite;
  }

  .app-boot-screen__text {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #636366;
  }

  .bg-video-layer,
  .bg-video,
  .bg-video-overlay,
  body:not(.opened) .bg-video-layer,
  body:not(.opened) .bg-video,
  body:not(.opened) .bg-video-overlay {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
  }

  body.opened .bg-video-layer,
  body.opened .bg-video,
  body.opened .bg-video-overlay {
    display: block;
    visibility: visible;
  }

  body.opened .bg-video-layer,
  body.opened .bg-video-overlay {
    position: fixed;
    inset: 0;
    pointer-events: none;
  }

  body:not(.opened) #cover {
    position: fixed;
    inset: 0;
    z-index: 25;
    margin: 0;
    width: 100%;
    height: 100%;
    min-height: 100dvh;
  }

  body:not(.opened) .cover {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 100%;
    padding: max(16px, env(safe-area-inset-top, 0px)) 20px max(20px, env(safe-area-inset-bottom, 0px));
    background: #ffffff;
    text-align: center;
    box-sizing: border-box;
  }

  body:not(.opened) .cover > * {
    width: min(100%, 22rem);
    margin-inline: auto;
  }

  .cover .label {
    margin: 0 0 8px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #f5530d;
  }

  .cover h1 {
    margin: 0 0 16px;
    font-size: clamp(1.5rem, 5vw, 2rem);
    line-height: 1.2;
    font-weight: 800;
    color: #1c1c1e;
  }

  .cover .meta,
  .cover .guest-label {
    margin: 0 0 8px;
    color: #636366;
    font-size: 0.95rem;
  }

  .cover .guest {
    margin: 0 0 20px;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1c1c1e;
  }

  .ui-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #e5e7eb;
    border-top-color: #f5530d;
    border-radius: 50%;
    animation: invitation-spin 0.75s linear infinite;
  }

  @keyframes invitation-spin {
    to {
      transform: rotate(360deg);
    }
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .btn .ui-btn-spinner {
    display: none;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: invitation-spin 0.75s linear infinite;
    flex-shrink: 0;
  }

  .btn.is-loading {
    pointer-events: none;
    opacity: 0.94;
  }

  .btn.is-loading .ui-btn-spinner {
    display: block;
  }

  body.is-ui-busy {
    cursor: progress;
  }

  .cover .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0 28px;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(135deg, #f5530d, #d9450b);
    color: #fff;
    font: inherit;
    font-weight: 700;
    font-size: 1rem;
  }

  .cover .cover-logo-wrap {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 14px auto 18px;
    padding: 0;
    border: 0;
    background: transparent;
  }

  .cover .cover-logo {
    width: clamp(88px, 22vw, 132px);
    height: clamp(88px, 22vw, 132px);
    margin: 0 auto;
    object-fit: contain;
    object-position: center center;
    display: block;
    border: 0;
    box-shadow: none;
  }
</style>
