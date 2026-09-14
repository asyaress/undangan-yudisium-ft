<style id="invitation-critical">
  html,
  body {
    margin: 0;
    background: #ffffff;
    color: #1c1c1e;
  }

  .bg-video-layer,
  .bg-video,
  .bg-video-overlay {
    visibility: hidden;
    pointer-events: none;
  }

  body.opened .bg-video-layer,
  body.opened .bg-video,
  body.opened .bg-video-overlay {
    visibility: visible;
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

  .cover .btn {
    font: inherit;
  }
</style>
