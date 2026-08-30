<style>
    :root {
      --bg: #ffffff;
      --bg-2: #fafafa;
      --surface: #ffffff;
      --surface-strong: #ffffff;
      --text: #111827;
      --muted: #6b7280;
      --line: #e5e7eb;
      --line-soft: #f3f4f6;
      --primary: #F5530D;
      --primary-deep: #D9450B;
      --primary-soft: #FFF3EE;
      --good: #047857;
      --warn: #F5530D;
      --bad: #b91c1c;
      --shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 8px 24px rgba(15, 23, 42, 0.04);
      --shadow-soft: 0 1px 2px rgba(15, 23, 42, 0.04);
      --radius: 16px;
      --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
      --ease-smooth: cubic-bezier(0.16, 1, 0.3, 1);
      --spring: cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      height: 100%;
      -webkit-text-size-adjust: 100%;
    }

    body {
      width: 100%;
      min-height: 100%;
      min-height: 100vh;
      min-height: 100dvh;
      font-family: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--text);
      background-color: #f3f4f6;
      padding: 18px 14px 36px;
      display: grid;
      place-items: start center;
      scroll-behavior: smooth;
      overflow-x: hidden;
    }

    body:not(.opening):not(.opened) {
      overflow: hidden;
      display: block;
      height: 100%;
      min-height: 100dvh;
      min-height: 100svh;
      padding: 0;
    }

    body:not(.opened) {
      background: #ffffff;
    }

    body:not(.opening):not(.opened) main {
      gap: 0;
      width: 100%;
      max-width: none;
      min-height: 0;
      height: auto;
      margin: 0;
      padding: 0;
      display: block;
      place-items: stretch;
      align-content: flex-start;
      justify-content: flex-start;
    }

    body:not(.opened) #cover {
      position: fixed;
      inset: 0;
      z-index: 25;
      width: 100%;
      max-width: none;
      height: 100%;
      min-height: 100dvh;
      min-height: 100svh;
      min-height: -webkit-fill-available;
      margin: 0;
    }

    body:not(.opened) .cover {
      width: 100%;
      height: 100%;
      min-height: 100%;
      background: #ffffff;
      border-radius: 0;
      border: none;
      box-shadow: none;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding:
        max(16px, env(safe-area-inset-top, 0px))
        max(20px, env(safe-area-inset-right, 0px))
        max(20px, env(safe-area-inset-bottom, 0px))
        max(20px, env(safe-area-inset-left, 0px));
    }

    body.opened #cover {
      display: none !important;
      height: 0 !important;
      min-height: 0 !important;
      overflow: hidden;
      position: absolute;
      pointer-events: none;
    }

    #cover {
      position: relative;
      z-index: 10;
    }

    .cover {
      text-align: center;
      padding: 34px 28px 30px;
      background: rgba(255, 255, 255, 0.96);
      border: 1px solid rgba(255, 255, 255, 0.9);
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
      border-radius: 24px;
      transition:
        opacity 740ms var(--spring),
        transform 740ms var(--spring),
        filter 740ms var(--spring);
      will-change: transform, opacity, filter;
    }

    .bg-video-layer {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
      background: #f3f4f6;
    }

    .bg-video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center center;
      opacity: 0;
      transform: scale(1.03);
      filter: brightness(1) saturate(0.96) contrast(1.01);
      transition: opacity 420ms var(--ease-out), transform 800ms var(--ease-out);
    }

    .bg-video.is-ready {
      opacity: 0.48;
      transform: scale(1);
    }

    .bg-video-overlay {
      position: fixed;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      background: rgba(255, 255, 255, 0.48);
      transition: background 360ms var(--ease-out);
    }

    body.opened .bg-video {
      filter: brightness(0.95) saturate(0.95) contrast(1);
    }

    body.opened .bg-video-overlay {
      background: rgba(255, 255, 255, 0.55);
    }

    main {
      width: min(100%, 780px);
      display: grid;
      gap: 14px;
      position: relative;
      z-index: 3;
      margin-top: 8px;
      margin-inline: auto;
    }

    .invitation-layout {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }

    .transition-layer {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 30;
      opacity: 0;
      transform: scale(1.04);
      filter: blur(6px);
      background: rgba(255, 255, 255, 0.06);
      transition:
        opacity 620ms var(--ease-out),
        transform 620ms var(--ease-out),
        filter 620ms var(--ease-out);
    }

    .panel {
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .panel>* {
      position: relative;
      z-index: 1;
    }

    .cover>* {
      position: relative;
      z-index: 1;
      opacity: 1;
      transform: none;
    }

    .cover .label {
      font-size: 0.78rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--primary);
      margin-bottom: 10px;
      font-weight: 800;
    }

    .cover h1 {
      font-size: clamp(1.85rem, 7vw, 2.5rem);
      line-height: 1.08;
      margin-bottom: 12px;
      font-weight: 800;
      letter-spacing: -0.03em;
      color: var(--text);
    }

    .logo-frame {
      width: min(42vw, 140px);
      aspect-ratio: 1;
      border-radius: 50%;
      margin: 12px auto 18px;
      display: grid;
      place-items: center;
      padding: 14px;
      border: 1px solid var(--line);
      background: #fff;
      box-shadow: var(--shadow-soft);
    }

    .logo {
      width: 100%;
      display: block;
      object-fit: contain;
    }

    .logo-fallback {
      display: none;
      color: var(--primary);
      font-size: 0.8rem;
      font-weight: 600;
      line-height: 1.4;
      text-align: center;
      padding: 0 10px;
    }

    .meta {
      color: var(--muted);
      line-height: 1.65;
      margin-bottom: 20px;
      font-size: 0.94rem;
    }

    .guest-label {
      font-size: 0.78rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 6px;
      font-weight: 700;
    }

    .guest {
      font-weight: 800;
      margin-bottom: 18px;
      font-size: 1.08rem;
      color: var(--text);
      line-height: 1.5;
    }

    .cover .btn {
      min-width: 220px;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--primary), var(--primary-deep));
      color: #fff;
      box-shadow: 0 12px 28px rgba(245, 83, 13, 0.28);
    }

    .cover .btn:hover {
      background: linear-gradient(135deg, #ff6318, var(--primary-deep));
      color: #fff;
    }

    .rsvp-tutorial {
      position: fixed;
      inset: 0;
      z-index: 10000;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      justify-content: flex-start;
      padding: 12px 16px 0;
      pointer-events: none;
    }

    .rsvp-tutorial.is-visible {
      animation: tutorialFadeIn 300ms var(--ease-out) forwards;
    }

    @keyframes tutorialFadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .rsvp-tutorial[hidden] {
      display: none !important;
    }

    .rsvp-spotlight {
      position: fixed;
      z-index: 9998;
      border-radius: 18px;
      border: 3px solid rgba(245, 83, 13, 0.95);
      box-shadow:
        0 0 0 9999px rgba(15, 23, 42, 0.82),
        0 0 0 6px rgba(245, 83, 13, 0.25),
        0 12px 40px rgba(245, 83, 13, 0.35);
      pointer-events: none;
      opacity: 0;
      transition: opacity 280ms var(--ease-out);
    }

    .rsvp-spotlight.is-active {
      opacity: 1;
    }

    .rsvp-spotlight[hidden] {
      display: none !important;
    }

    .rsvp-tutorial-backdrop {
      display: none;
    }

    .rsvp-tutorial-card {
      position: relative;
      z-index: 10001;
      pointer-events: auto;
      display: grid;
      grid-template-columns: minmax(0, 1fr) 128px;
      align-items: center;
      gap: 16px;
      width: min(620px, 100%);
      margin: 0 auto;
      padding: 20px 20px 18px;
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16);
      border: 2px solid rgba(245, 83, 13, 0.28);
    }

    .rsvp-tutorial-copy {
      min-width: 0;
    }

    .rsvp-tutorial-visual {
      align-self: stretch;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      min-height: 158px;
      overflow: hidden;
      border-radius: 14px;
    }

    .rsvp-tutorial-visual img {
      display: block;
      width: min(142px, 100%);
      max-height: 178px;
      object-fit: contain;
      object-position: bottom center;
    }

    .rsvp-tutorial-kicker {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 999px;
      background: var(--primary);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .rsvp-tutorial-card h3 {
      font-size: 1.05rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 12px;
      text-align: left;
    }

    .rsvp-tutorial-typing {
      min-height: 72px;
      margin-bottom: 12px;
    }

    .rsvp-tutorial-typing p {
      margin: 0;
      color: var(--text);
      font-size: 0.88rem;
      line-height: 1.65;
      text-align: left;
    }

    .rsvp-tutorial-cursor {
      display: inline;
      color: var(--primary);
      font-weight: 700;
      animation: tutorialBlink 900ms step-end infinite;
    }

    @keyframes tutorialBlink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0; }
    }

    .rsvp-tutorial-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }

    .rsvp-tutorial-progress {
      font-size: 0.76rem;
      font-weight: 700;
      color: var(--muted);
      letter-spacing: 0.04em;
    }

    .rsvp-tutorial-actions {
      display: flex;
      gap: 8px;
      margin-left: auto;
    }

    .rsvp-tutorial-actions .btn[hidden],
    .rsvp-tutorial-actions #rsvpTutorialNext[hidden] {
      display: none !important;
    }

    .rsvp-tutorial-skip {
      border: 0;
      background: transparent;
      color: var(--muted);
      font: inherit;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      padding: 8px 6px;
    }

    .rsvp-tutorial-skip:hover {
      color: var(--text);
    }

    .rsvp-card.is-tutorial-target,
    .invitation-details.is-tutorial-target,
    .invitation-details .panel.is-tutorial-target {
      position: relative;
      z-index: 9999;
      scroll-margin-top: 12px;
      scroll-margin-bottom: 16px;
      background: #ffffff;
    }

    #invitationDetails.is-tutorial-target {
      position: relative;
      z-index: 9999;
    }

    body.rsvp-tutorial-open #rsvpSection.is-tutorial-target,
    body.rsvp-tutorial-open #invitationContentDetails.is-tutorial-target {
      z-index: 9999;
    }

    body.rsvp-tutorial-open {
      overflow: auto;
    }

    @media (max-width: 768px) {
      .rsvp-tutorial {
        justify-content: flex-end;
        padding: 0 10px max(12px, env(safe-area-inset-bottom, 0px));
      }

      .rsvp-tutorial-card {
        grid-template-columns: 92px minmax(0, 1fr);
        gap: 12px;
        padding: 14px 14px 12px;
        margin-top: auto;
      }

      .rsvp-tutorial-copy {
        grid-column: 2;
      }

      .rsvp-tutorial-visual {
        grid-column: 1;
        grid-row: 1;
        min-height: 132px;
      }

      .rsvp-tutorial-visual img {
        width: 118px;
        max-width: none;
        max-height: 148px;
      }

      .rsvp-tutorial-typing {
        min-height: 58px;
      }

      .rsvp-tutorial-typing p {
        font-size: 0.84rem;
        line-height: 1.6;
      }

      .rsvp-tutorial-footer {
        align-items: center;
      }

      .rsvp-tutorial-actions {
        margin-left: 0;
      }

      .rsvp-card.is-tutorial-target,
      .invitation-details.is-tutorial-target,
      .invitation-details .panel.is-tutorial-target,
      #invitationDetails.is-tutorial-target {
        scroll-margin-top: 0;
        scroll-margin-bottom: 12px;
      }
    }

    .rsvp-card {
      padding: 22px 20px;
      border: 2px solid rgba(245, 83, 13, 0.24);
      background: #fff;
      box-shadow: 0 10px 30px rgba(245, 83, 13, 0.1);
      scroll-margin-top: 16px;
    }

    .rsvp-card.is-pulse {
      animation: rsvpPulse 1.8s ease-out 2;
    }

    @keyframes rsvpPulse {
      0%, 100% { box-shadow: 0 10px 30px rgba(245, 83, 13, 0.1); }
      50% { box-shadow: 0 0 0 6px rgba(245, 83, 13, 0.12), 0 10px 30px rgba(245, 83, 13, 0.14); }
    }

    .rsvp-card-head {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 14px;
    }

    .rsvp-badge {
      flex-shrink: 0;
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: var(--primary-soft);
      color: var(--primary);
      font-size: 1.1rem;
      font-weight: 800;
    }

    .rsvp-card-head h3 {
      font-size: 1.15rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 4px;
      text-align: left;
    }

    .rsvp-card-head p {
      color: var(--muted);
      font-size: 0.88rem;
      line-height: 1.6;
      text-align: left;
    }

    .rsvp-steps {
      display: grid;
      gap: 8px;
      margin-bottom: 14px;
    }

    .rsvp-step {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 10px;
      background: #fff;
      border: 1px solid var(--line);
      font-size: 0.84rem;
      color: var(--muted);
    }

    .rsvp-step.is-active {
      border-color: rgba(245, 83, 13, 0.3);
      background: var(--primary-soft);
      color: var(--text);
      font-weight: 700;
    }

    .rsvp-step-num {
      width: 22px;
      height: 22px;
      border-radius: 999px;
      display: grid;
      place-items: center;
      background: var(--line-soft);
      font-size: 0.72rem;
      font-weight: 800;
      flex-shrink: 0;
    }

    .rsvp-step.is-active .rsvp-step-num {
      background: var(--primary);
      color: #fff;
    }

    .invitation-layout .rsvp-card,
    .invitation-layout .invitation-details-section {
      grid-column: 1 / -1;
    }

    .invitation-details-section {
      display: grid;
      gap: 6px;
    }

    .invitation-details {
      display: grid;
      gap: 12px;
    }

    .details-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      color: var(--muted);
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 0 2px;
      margin: 0;
    }

    .details-divider::before,
    .details-divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--line);
    }

    .btn,
    .link-btn {
      border: none;
      border-radius: 12px;
      padding: 12px 18px;
      font: inherit;
      font-weight: 700;
      cursor: pointer;
      background: linear-gradient(135deg, var(--primary), var(--primary-deep));
      color: #fff;
      transition: transform 200ms var(--spring), background 200ms var(--ease-out), box-shadow 200ms var(--spring);
      min-width: 0;
      box-shadow: 0 4px 14px rgba(245, 83, 13, 0.22);
      text-decoration: none;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
    }

    .btn:hover,
    .link-btn:hover {
      background: linear-gradient(135deg, #ff6318, var(--primary-deep));
      transform: translateY(-1px);
      color: #fff;
    }

    .btn:active,
    .link-btn:active {
      transform: translateY(0);
    }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .ghost-btn {
      background: linear-gradient(135deg, var(--primary-deep), #c73d0a);
      color: #fff;
      border: none;
      box-shadow: 0 4px 14px rgba(217, 69, 11, 0.22);
    }

    .ghost-btn:hover {
      background: linear-gradient(135deg, #c73d0a, #b03409);
      color: #fff;
    }

    .invitation {
      opacity: 0;
      transform: translateY(36px) scale(0.975);
      filter: blur(12px);
      max-height: 0;
      overflow: hidden;
      pointer-events: none;
      transition:
        opacity 860ms var(--spring),
        transform 860ms var(--spring),
        filter 860ms var(--spring);
      display: none;
      gap: 12px;
    }

    body.opening .cover {
      opacity: 0;
      transform: translateY(-34px) scale(0.93);
      filter: blur(10px);
      z-index: 40;
    }

    body.opening .transition-layer {
      opacity: 0.36;
      transform: scale(1);
      filter: blur(0.5px);
    }

    body.opening .invitation,
    body.opened .invitation {
      display: grid;
      opacity: 1;
      transform: translateY(0);
      filter: blur(0);
      max-height: none;
      overflow: visible;
      pointer-events: auto;
    }

    body.opened .reveal-item {
      opacity: 1;
      transform: none;
      filter: none;
    }

    body.opened .invitation-details-section:first-child {
      margin-top: 0;
      padding-top: 0;
    }

    body.opened .transition-layer {
      opacity: 0;
      transform: scale(0.98);
      filter: blur(4px);
      transition-duration: 500ms;
    }

    body.animating .cover {
      pointer-events: none;
    }

    body.archive-mode .invitation {
      display: grid;
      opacity: 1;
      transform: none;
      filter: none;
      max-height: none;
      overflow: visible;
      pointer-events: auto;
    }

    .reveal-item {
      opacity: 0;
      transform: translateY(24px) scale(0.985);
      filter: blur(7px);
      transition:
        opacity 860ms var(--spring),
        transform 860ms var(--spring),
        filter 860ms var(--spring);
    }

    .reveal-item.in-view {
      opacity: 1;
      transform: translateY(0) scale(1);
      filter: blur(0);
    }

    .card {
      padding: 22px;
      z-index: 1;
    }

    .card--white {
      background: #ffffff;
    }

    .watermark-card {
      position: relative;
      overflow: hidden;
      isolation: isolate;
    }

    .watermark-card > * {
      position: relative;
      z-index: 1;
    }

    .watermark-card::after {
      content: "";
      position: absolute;
      right: -22px;
      bottom: -36px;
      z-index: 0;
      width: clamp(150px, 28%, 250px);
      aspect-ratio: 1;
      background: url("{{ asset('Unmul.png') }}") center / contain no-repeat;
      opacity: 0.055;
      transform: rotate(-18deg);
      transform-origin: center;
      pointer-events: none;
    }

    .card--full {
      grid-column: 1 / -1;
    }

    .title {
      font-size: 1.5rem;
      color: rgba(28, 28, 30, 0.92);
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 10px;
      text-align: center;
    }

    .line {
      text-align: center;
      color: rgba(28, 28, 30, 0.72);
      font-size: 0.94rem;
      margin-bottom: 16px;
      line-height: 1.75;
    }

    .mini-brand {
      width: 66px;
      margin: 0 auto 14px;
      opacity: 0.95;
      filter: drop-shadow(0 5px 10px rgba(161, 108, 27, 0.16));
    }

    .mini-brand img {
      width: 100%;
      display: block;
    }

    .details {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .detail-item {
      min-height: 72px;
      padding: 12px 14px;
      border-radius: 12px;
      border: 1px solid #d8dde6;
      background: #fbfbfc;
      display: grid;
      align-content: start;
      gap: 6px;
      line-height: 1.55;
    }

    .detail-label {
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--primary);
    }

    .detail-value {
      color: #6b7280;
      font-size: 0.9rem;
    }

    .event-strip {
      background: transparent;
      color: var(--text);
      border-radius: 0;
      padding: 0;
      border: 0;
    }

    .event-strip h3 {
      font-size: 1.1rem;
      font-weight: 800;
      text-align: left;
      margin-bottom: 10px;
      letter-spacing: -0.02em;
      color: var(--text);
    }

    .event-strip ol {
      margin: 0 0 0 1.15rem;
      padding: 0;
      line-height: 1.58;
      font-size: 0.9rem;
      color: var(--muted);
    }

    .event-strip ol ol {
      margin-top: 4px;
      margin-left: 1rem;
      list-style-type: lower-alpha;
      color: rgba(28, 28, 30, 0.68);
    }

    .event-strip li {
      padding-left: 0;
      margin-bottom: 4px;
    }

    .event-strip li::marker {
      color: var(--primary);
      font-weight: 700;
    }

    .event-strip ol ol li::marker {
      color: rgba(245, 83, 13, 0.78);
    }

    .event-strip ol ol li {
      margin-bottom: 2px;
      line-height: 1.45;
    }

    .map-wrap {
      margin-top: 12px;
      border: 1px solid #d8dde6;
      border-radius: 14px;
      overflow: hidden;
      background: #ffffff;
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.45);
    }

    .map-wrap iframe {
      width: 100%;
      height: 240px;
      border: 0;
      display: block;
    }

    .location-text {
      text-align: center;
      color: #6b7280;
      line-height: 1.7;
      margin-top: 12px;
      font-size: 0.93rem;
    }

    .map-btn {
      display: block;
      width: fit-content;
      max-width: 100%;
      margin: 12px auto 0;
      padding: 10px 18px;
      border-radius: 999px;
      text-decoration: none;
      background: linear-gradient(135deg, var(--primary), var(--primary-deep));
      color: #fff;
      font-weight: 700;
      font-size: 0.92rem;
      border: none;
      box-shadow: 0 4px 12px rgba(245, 83, 13, 0.2);
      text-align: center;
    }

    .map-btn:hover {
      background: linear-gradient(135deg, #ff6318, var(--primary-deep));
      color: #fff;
    }

    .auth-stack {
      border: 1px solid #d8dde6;
      border-radius: 16px;
      padding: 18px 18px 14px;
      background: #ffffff;
      margin-top: 4px;
    }

    .auth-head {
      text-align: left;
      margin-bottom: 8px;
      color: rgba(28, 28, 30, 0.78);
      line-height: 1.5;
    }

    .auth-date {
      font-size: 0.9rem;
      font-weight: 700;
    }

    .auth-salutation {
      font-size: 0.92rem;
      margin-top: 2px;
    }

    .signature-layer {
      position: relative;
      min-height: 184px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .signature-box {
      min-height: 184px;
      width: 100%;
      border-radius: 0;
      background: transparent;
      display: grid;
      place-items: center;
      padding: 0 16px;
    }

    .stamp-overlay {
      position: absolute;
      left: 28%;
      top: 50%;
      width: 122px;
      height: 122px;
      transform: translate(-50%, -50%) rotate(-3deg);
      background: transparent;
      display: grid;
      place-items: center;
      pointer-events: none;
    }

    .asset-image {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      display: none;
    }

    #ttdImage.asset-image {
      width: min(76%, 500px);
      max-height: 170px;
      filter: contrast(1.08) saturate(1.02);
    }

    #stampImage.asset-image {
      width: 100%;
      height: 100%;
      opacity: 0.94;
    }

    .asset-placeholder {
      text-align: center;
      color: #7b8794;
      font-size: 0.78rem;
      line-height: 1.4;
      padding: 0 6px;
    }

    .auth-name {
      margin-top: 10px;
      text-align: center;
      color: #374151;
      font-weight: 800;
      line-height: 1.6;
      font-size: 0.9rem;
    }

    .auth-role {
      text-align: center;
      color: #6b7280;
      font-size: 0.82rem;
      margin-top: 4px;
    }

    .section-kicker {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.72);
      color: var(--muted);
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }

    .archive-grid,
    .category-grid {
      display: grid;
      gap: 12px;
      margin-top: 14px;
    }

    .archive-item,
    .category-item {
      padding: 16px;
      border-radius: 12px;
      background: #fff;
      border: 1px solid var(--line);
    }

    .archive-grid {
      grid-template-columns: minmax(0, 760px);
      justify-content: center;
      gap: 14px;
    }

    .archive-card {
      padding: 18px;
      border-radius: 14px;
      background: #fff;
      border: 1px solid var(--line);
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
      display: grid;
      gap: 12px;
    }

    .archive-card-top {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: flex-start;
    }

    .archive-card-top h3 {
      font-size: 1.08rem;
      line-height: 1.38;
      letter-spacing: -0.02em;
      color: rgba(17, 24, 39, 0.96);
      margin-bottom: 4px;
    }

    .archive-card-top p {
      display: none;
    }

    .archive-card-date {
      min-width: 118px;
      text-align: right;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--primary);
    }

    .archive-metrics {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .archive-metric {
      padding: 14px;
      border-radius: 12px;
      background: #fff;
      border: 1px solid var(--line);
      display: grid;
      gap: 4px;
    }

    .archive-metric span {
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .archive-metric strong {
      font-size: 1.8rem;
      line-height: 1;
      letter-spacing: -0.04em;
      color: var(--text);
      font-variant-numeric: tabular-nums;
    }

    .archive-status-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .archive-countdown {
      padding: 14px;
      border-radius: 12px;
      background: #fff;
      border: 1px solid var(--line);
      display: grid;
      gap: 10px;
    }

    .archive-countdown-label {
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .archive-countdown-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 8px;
    }

    .archive-countdown-unit {
      padding: 10px 8px;
      border-radius: 10px;
      background: #fff;
      border: 1px solid var(--line);
      text-align: center;
      display: grid;
      gap: 2px;
    }

    .archive-countdown-unit strong {
      font-size: 1.02rem;
      font-weight: 900;
      color: var(--text);
      line-height: 1;
      font-variant-numeric: tabular-nums;
    }

    .archive-countdown-unit span {
      font-size: 0.72rem;
      color: var(--muted);
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .archive-card-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .archive-card-actions .link-btn {
      min-width: 140px;
      justify-content: center;
    }

    .archive-item h3,
    .category-item h3 {
      font-size: 1.02rem;
      line-height: 1.45;
      letter-spacing: -0.01em;
      margin-bottom: 6px;
      color: rgba(28, 28, 30, 0.92);
    }

    .archive-item p,
    .category-item p {
      color: rgba(28, 28, 30, 0.72);
      line-height: 1.65;
      font-size: 0.9rem;
    }

    .category-picker {
      display: grid;
      gap: 14px;
      margin-top: 16px;
      text-align: left;
    }

    .category-picker-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 12px;
      align-items: end;
    }

    .category-select-control label {
      display: block;
      margin-bottom: 7px;
      color: var(--muted);
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .category-select-control select {
      width: 100%;
      min-height: 48px;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 0 42px 0 14px;
      color: var(--text);
      background: #fff;
      font: inherit;
      font-size: 0.92rem;
      font-weight: 700;
      outline: none;
      box-shadow: var(--shadow-soft);
    }

    .category-select-control select:focus {
      border-color: rgba(245, 83, 13, 0.42);
      box-shadow: 0 0 0 4px rgba(245, 83, 13, 0.08);
    }

    .category-preview {
      display: grid;
      gap: 10px;
      padding: 16px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: #fff;
    }

    .category-preview h3 {
      margin: 0;
      color: var(--text);
      font-size: 1.02rem;
      line-height: 1.35;
      letter-spacing: 0;
    }

    .category-preview p {
      color: rgba(28, 28, 30, 0.72);
      line-height: 1.6;
      font-size: 0.9rem;
    }

    .category-preview-note {
      padding: 11px 12px;
      border-radius: 10px;
      border: 1px solid rgba(245, 83, 13, 0.16);
      background: rgba(255, 243, 238, 0.72);
      color: rgba(28, 28, 30, 0.76);
      line-height: 1.55;
      font-size: 0.85rem;
    }

    .category-link-box {
      display: grid;
      gap: 7px;
    }

    .category-link-box label {
      color: var(--muted);
      font-size: 0.74rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .category-link-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto auto;
      gap: 8px;
      align-items: center;
    }

    .category-link-row input {
      width: 100%;
      min-height: 44px;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 0 12px;
      background: #fff;
      color: rgba(28, 28, 30, 0.78);
      font: inherit;
      font-size: 0.82rem;
      box-shadow: var(--shadow-soft);
    }

    .category-link-row .btn {
      min-height: 44px;
      padding: 0 14px;
      white-space: nowrap;
    }

    .category-submit-row {
      display: flex;
      gap: 10px;
      align-items: center;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    .pill-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 12px;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 10px;
      border-radius: 999px;
      font-size: 0.74rem;
      font-weight: 700;
      border: 1px solid var(--line);
      background: var(--line-soft);
      color: var(--muted);
    }

    .pill.good {
      color: var(--good);
      background: rgba(15, 118, 110, 0.08);
      border-color: rgba(15, 118, 110, 0.12);
    }

    .pill.warn {
      color: var(--warn);
      background: rgba(180, 83, 9, 0.08);
      border-color: rgba(180, 83, 9, 0.12);
    }

    .pill.bad {
      color: var(--bad);
      background: rgba(185, 28, 28, 0.08);
      border-color: rgba(185, 28, 28, 0.12);
    }

    .action-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }

    .invite-form .action-row,
    .rsvp-card .action-row {
      flex-wrap: nowrap;
      align-items: stretch;
    }

    .invite-form .action-row .btn,
    .rsvp-card .action-row .btn {
      flex: 1 1 0;
      min-width: 0;
      padding-inline: 12px;
      font-size: 0.88rem;
    }

    .inline-code {
      display: block;
      margin-top: 12px;
      padding: 12px 14px;
      border-radius: 14px;
      background: rgba(28, 28, 30, 0.9);
      color: #f8fafc;
      line-height: 1.7;
      font-size: 0.76rem;
      overflow-wrap: anywhere;
    }

    .invite-form {
      display: grid;
      gap: 12px;
      margin-top: 14px;
    }

    [data-delegate-fields],
    [data-conditional-note-field],
    [data-signature-field] {
      overflow: hidden;
      opacity: 0;
      max-height: 0;
      transform: translateY(-6px);
      transition: opacity 220ms var(--ease-out), max-height 260ms var(--ease-out), transform 260ms var(--ease-out);
    }

    [data-conditional-note-field].is-open {
      opacity: 1;
      max-height: 190px;
      transform: translateY(0);
    }

    [data-delegate-fields].is-open {
      opacity: 1;
      max-height: 170px;
      transform: translateY(0);
    }

    .invite-form .field[hidden],
    #participantNoteField[hidden],
    #recipientNoteField[hidden],
    [data-delegate-fields][hidden],
    [data-conditional-note-field][hidden],
    [data-signature-field][hidden] {
      display: none !important;
    }

    .identity-panel,
    .rsvp-success-panel {
      margin-top: 14px;
      padding: 14px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: #fff;
    }

    .identity-panel p,
    .rsvp-success-panel p {
      color: var(--muted);
      line-height: 1.65;
      font-size: 0.9rem;
    }

    .identity-panel h4,
    .rsvp-success-panel h4 {
      font-size: 0.98rem;
      font-weight: 800;
      margin-bottom: 10px;
      color: var(--text);
    }

    .identity-list {
      display: grid;
      gap: 8px;
      margin: 10px 0 12px;
    }

    .identity-row {
      display: grid;
      grid-template-columns: 86px minmax(0, 1fr);
      gap: 10px;
      color: var(--muted);
      font-size: 0.9rem;
      line-height: 1.55;
    }

    .identity-row strong {
      color: var(--text);
      overflow-wrap: anywhere;
    }

    .radio-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
    }

    .radio-grid.two-options {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .radio-option {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      position: relative;
      min-height: 48px;
      padding: 12px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: #fff;
      color: var(--text);
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      transition: border-color 180ms var(--ease-out), background 180ms var(--ease-out), color 180ms var(--ease-out);
    }

    .radio-option:has(input:checked) {
      border-color: rgba(245, 83, 13, 0.45);
      background: var(--primary-soft);
      color: var(--primary-deep);
    }

    .radio-option input {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      margin: 0;
      cursor: pointer;
    }

    .radio-mark {
      width: 18px;
      height: 18px;
      border-radius: 999px;
      border: 2px solid #9ca3af;
      background: #fff;
      display: inline-grid;
      place-items: center;
      flex: 0 0 auto;
    }

    .radio-mark::after {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: var(--primary);
      opacity: 0;
      transform: scale(0.6);
      transition: opacity 160ms var(--ease-out), transform 160ms var(--ease-out);
    }

    .radio-option:has(input:checked) .radio-mark {
      border-color: var(--primary);
    }

    .radio-option:has(input:checked) .radio-mark::after {
      opacity: 1;
      transform: scale(1);
    }

    .form-grid {
      display: grid;
      grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
      gap: 12px;
    }

    .rsvp-success-panel {
      display: grid;
      grid-template-columns: 34px minmax(0, 1fr);
      align-items: start;
      gap: 12px;
      padding: 0;
      border: 0;
      background: transparent;
      text-align: left;
    }

    .rsvp-success-icon {
      display: inline-grid;
      place-items: center;
      width: 30px;
      height: 30px;
      border: 1px solid var(--primary);
      border-radius: 999px;
      color: var(--primary-deep);
      animation: successPop 360ms var(--ease-out) both;
    }

    .rsvp-success-icon svg {
      width: 17px;
      height: 17px;
      fill: none;
      stroke: currentColor;
      stroke-linecap: round;
      stroke-linejoin: round;
      stroke-width: 2.2;
    }

    .rsvp-success-copy {
      display: grid;
      gap: 2px;
    }

    .rsvp-success-label {
      color: var(--text);
      font-size: 0.9rem;
      font-weight: 800;
    }

    .rsvp-success-panel p {
      margin: 0;
      color: var(--muted);
      font-size: 0.9rem;
      line-height: 1.6;
    }

    .success-check {
      display: none;
    }

    @keyframes successPop {
      0% { transform: scale(0.72); opacity: 0; }
      70% { transform: scale(1.06); opacity: 1; }
      100% { transform: scale(1); opacity: 1; }
    }

    @keyframes successDraw {
      to { stroke-dashoffset: 0; }
    }

    .identity-panel .action-row form {
      flex: 1 1 0;
      display: flex;
      min-width: 0;
    }

    .identity-panel .action-row .btn {
      width: 100%;
    }

    .field {
      display: grid;
      gap: 7px;
    }

    .field label {
      font-size: 0.82rem;
      font-weight: 700;
      color: rgba(28, 28, 30, 0.82);
    }

    .field input,
    .field textarea {
      width: 100%;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 13px 14px;
      background: #fff;
      color: var(--text);
      font: inherit;
    }

    .field textarea {
      min-height: 110px;
      resize: vertical;
    }

    .signature-field.is-open {
      opacity: 1;
      max-height: 360px;
      transform: translateY(0);
    }

    .signature-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .signature-clear {
      appearance: none;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: #fff;
      color: var(--primary-deep);
      cursor: pointer;
      font: inherit;
      font-size: 0.78rem;
      font-weight: 800;
      padding: 6px 12px;
      transition: border-color 180ms var(--ease-out), color 180ms var(--ease-out);
    }

    .signature-clear:hover,
    .signature-clear:focus-visible {
      border-color: rgba(245, 83, 13, 0.42);
      color: var(--primary);
      outline: 0;
    }

    .signature-pad {
      position: relative;
      min-height: 176px;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      overflow: hidden;
    }

    .signature-pad canvas {
      display: block;
      width: 100%;
      height: 176px;
      cursor: crosshair;
      touch-action: none;
    }

    .signature-placeholder {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      color: rgba(102, 112, 133, 0.52);
      font-size: 0.9rem;
      pointer-events: none;
      transition: opacity 180ms var(--ease-out);
    }

    .signature-field.is-drawn .signature-placeholder {
      opacity: 0;
    }

    .signature-help,
    .signature-error {
      margin: 0;
      font-size: 0.82rem;
      line-height: 1.55;
    }

    .signature-help {
      color: var(--muted);
    }

    .signature-error {
      color: var(--bad);
      font-weight: 800;
    }

    .field input:focus,
    .field textarea:focus {
      outline: none;
      border-color: rgba(245, 83, 13, 0.34);
      box-shadow: 0 0 0 4px rgba(245, 83, 13, 0.1);
    }

    .field input.is-invalid,
    .field input[aria-invalid="true"] {
      border-color: rgba(185, 28, 28, 0.48);
      box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.08);
    }

    .form-hint,
    .field-warning,
    .field-error {
      margin: 0;
      font-size: 0.78rem;
      line-height: 1.55;
      text-align: left;
    }

    .form-hint {
      color: var(--muted);
    }

    .field-warning {
      color: var(--warn);
      font-weight: 700;
    }

    .field-error {
      color: var(--bad);
      font-weight: 700;
    }

    @media (max-width: 640px) {
      .form-grid {
        grid-template-columns: 1fr;
      }

      .radio-grid {
        grid-template-columns: 1fr;
        gap: 8px;
      }

      .radio-option {
        min-height: 54px;
        padding: 10px 8px;
      }

      .signature-pad,
      .signature-pad canvas {
        height: 154px;
        min-height: 154px;
      }

      .archive-grid,
      .archive-metrics,
      .archive-countdown-grid {
        grid-template-columns: 1fr;
      }

      .archive-card-top,
      .archive-card-actions {
        flex-direction: column;
        align-items: stretch;
      }

      .archive-card-date {
        text-align: left;
        min-width: 0;
      }

      .category-picker-grid {
        grid-template-columns: 1fr;
      }

      .category-submit-row .btn,
      .category-picker-grid .btn {
        width: 100%;
      }

      .category-link-row {
        grid-template-columns: 1fr;
      }

      .category-link-row .btn {
        width: 100%;
      }

    }

    .empty-note,
    .flash-note {
      padding: 12px 14px;
      border-radius: 12px;
      background: var(--line-soft);
      color: var(--muted);
      line-height: 1.65;
      font-size: 0.9rem;
      border: 1px solid var(--line);
    }

    .flash-note.error {
      background: rgba(185, 28, 28, 0.08);
      color: var(--bad);
      border-color: rgba(185, 28, 28, 0.14);
    }

    .flash-note.good {
      background: rgba(15, 118, 110, 0.08);
      color: var(--good);
      border-color: rgba(15, 118, 110, 0.14);
    }

    .note-card .title {
      margin-bottom: 10px;
    }

    .notes-list {
      margin-left: 18px;
      line-height: 1.8;
      color: rgba(28, 28, 30, 0.78);
      font-size: 0.94rem;
    }

    .closing {
      text-align: center;
      color: #6b7280;
      font-size: 0.92rem;
      margin-top: 0;
      line-height: 1.7;
    }

    .closing-card {
      padding-block: 16px;
    }

    @media (max-width: 390px) {
      .cover,
      .card {
        padding: 18px 16px;
      }

      .guest {
        font-size: 0.95rem;
      }

      .details {
        grid-template-columns: 1fr;
      }

      .stamp-overlay {
        width: 96px;
        height: 96px;
        left: 25%;
      }
    }

    @media (max-width: 640px) {
      html,
      body {
        margin: 0;
      }

      body:not(.opened) {
        padding: 0;
      }

      .cover .label {
        margin-bottom: 8px;
      }

      .cover h1 {
        margin-bottom: 10px;
        font-size: clamp(1.65rem, 8vw, 2.15rem);
      }

      .cover .logo-frame {
        width: min(34vw, 120px);
        margin: 8px auto 12px;
        padding: 12px;
      }

      .cover .meta {
        margin-bottom: 14px;
        font-size: 0.9rem;
        max-width: 28ch;
      }

      .cover .guest-label {
        margin-bottom: 4px;
      }

      .cover .guest {
        margin-bottom: 16px;
        font-size: 1rem;
        max-width: 32ch;
        line-height: 1.45;
      }

      .cover .btn {
        width: min(100%, 280px);
        margin-inline: auto;
        align-self: center;
      }

      body.opened main {
        display: block;
        margin: 0;
        padding: 0;
        gap: 0;
        min-height: 0;
      }

      body.opened #cover {
        display: none !important;
        height: 0 !important;
        min-height: 0 !important;
        overflow: hidden;
        position: absolute;
        pointer-events: none;
      }

      body.opened #invitationContent {
        margin: 0;
        padding: 0 12px 12px;
        gap: 8px;
      }

      body.opened .invitation-details-section {
        gap: 4px;
        margin: 0;
        padding: 0;
      }

      body.opened .details-divider {
        padding: 0 2px;
        margin: 0;
      }

      body.opened .invitation-details {
        gap: 10px;
        margin: 0;
        padding: 0;
      }

      body.opened .invitation-details > article:first-child {
        margin-top: 0;
      }

      body.opened .reveal-item {
        opacity: 1;
        transform: none;
        filter: none;
      }

      .invitation {
        padding: 0 12px 12px;
        gap: 10px;
      }

      .card {
        padding: 18px 16px;
      }

      .details {
        grid-template-columns: 1fr;
      }

      .invite-form .action-row,
      .rsvp-card .action-row,
      .identity-panel .action-row {
        gap: 8px;
      }

      .invite-form .action-row .btn,
      .rsvp-card .action-row .btn,
      .identity-panel .action-row .btn {
        font-size: 0.82rem;
        padding: 11px 10px;
      }
    }

    @media (min-width: 641px) and (max-width: 900px) {
      body.opened {
        padding: 10px 16px 28px;
      }

      body:not(.opened) {
        padding: 0;
      }

      main {
        width: min(94vw, 680px);
        margin-top: 0;
      }

      body.opened #invitationContent {
        margin-top: 0;
        padding-top: 0;
      }

      body.opened .invitation-details-section {
        margin-top: -96px;
        padding-top: 0;
      }
    }

    @media (min-width: 641px) and (max-width: 1023px) {
      body.opened {
        padding: 10px 18px 34px;
      }

      body:not(.opened) {
        padding: 0;
      }

      main {
        width: min(96vw, 760px);
        gap: 10px;
        margin-top: 0;
      }

      body.opened #invitationContent {
        margin-top: 0;
        padding-top: 0;
      }

      body.opened .invitation-details-section {
        margin-top: clamp(-150px, -18vh, -96px);
        padding-top: 0;
      }
    }

    @media (min-width: 1024px) {
      body.opened {
        padding: 12px 24px 44px;
      }

      body:not(.opened) {
        padding: 0;
      }

      main {
        width: min(92vw, 780px);
        gap: 10px;
        margin-top: 0;
      }

      body.opened #invitationContent {
        margin-top: 0;
        padding-top: 0;
      }

      body.opened .invitation-details-section {
        margin-top: clamp(-220px, -24vh, -140px);
        padding-top: 0;
      }

      .map-wrap iframe {
        height: 300px;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      * {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important;
      }

      .cover > * {
        opacity: 1 !important;
        transform: none !important;
      }

      .bg-video {
        display: none;
      }
    }

</style>
