<style id="invitation-critical">
  html,
  body {
    margin: 0;
    background: #ffffff;
    color: #1c1c1e;
    -webkit-text-size-adjust: 100%;
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

  .cover .logo {
    width: 120px;
    height: 120px;
    margin: 0 0 16px;
    object-fit: contain;
  }
</style>
