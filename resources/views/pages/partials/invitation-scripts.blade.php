<script>
    const openButton = document.getElementById("openInvitation");
    const content = document.getElementById("invitationContent");
    const cover = document.getElementById("cover");
    const logoFallback = document.getElementById("logoFallback");
    const rsvpSection = document.getElementById("rsvpSection");
    const invitationDetailsHeader = document.getElementById("invitationDetails");
    const invitationDetailsBlock = document.getElementById("invitationContentDetails");
    const invitationIntroCard = document.querySelector("#invitationContentDetails > article.panel");
    const studentVerifyForm = document.getElementById("studentVerifyForm");
    const rsvpTutorial = document.getElementById("rsvpTutorial");
    const rsvpSpotlight = document.getElementById("rsvpSpotlight");
    const rsvpTutorialSteps = @json($rsvpTutorialSteps);
    const showRsvpTutorialOnOpen = @json($showRsvpGuide);
    const backgroundVideo = document.getElementById("backgroundVideo");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const INVITATION_OPEN_MS = 860;
    const revealItems = content ? Array.from(content.children) : [];
    const previewMode = new URLSearchParams(window.location.search).get("preview");
    const shouldAutoOpen = @json($autoOpenInvitation ?? false);
    const RSVP_RETURN_KEY = "undangan:return-to-rsvp";
    const shouldReturnToRsvp = shouldAutoOpen
      || window.location.hash === "#rsvpSection"
      || window.sessionStorage.getItem(RSVP_RETURN_KEY) === "1";
    const logoCandidates = [
      "{{ asset('Unmul.png') }}",
      "{{ asset('unmul.png') }}",
      "{{ asset('UNMUL.png') }}"
    ];

    const isMobileView = window.matchMedia("(max-width: 768px)").matches;

    document.querySelectorAll("[data-category-picker]").forEach((picker) => {
      const select = picker.querySelector("[data-category-select]");
      const title = picker.querySelector("[data-category-title]");
      const recipient = picker.querySelector("[data-category-recipient]");
      const access = picker.querySelector("[data-category-access]");
      const rsvp = picker.querySelector("[data-category-rsvp]");
      const note = picker.querySelector("[data-category-note]");
      const urlInput = picker.querySelector("[data-category-url-input]");
      const openLink = picker.querySelector("[data-category-open-link]");
      const copyButton = picker.querySelector("[data-category-copy]");
      const linkLabel = picker.querySelector("[data-category-link-label]");

      const syncCategoryPreview = () => {
        const option = select?.selectedOptions?.[0];
        if (!option) return;

        if (title) title.textContent = option.dataset.title || "";
        if (recipient) recipient.textContent = option.dataset.recipient || "";
        if (note) note.textContent = option.dataset.note || "";
        if (linkLabel) linkLabel.textContent = option.dataset.linkLabel || "Link undangan";
        if (urlInput) urlInput.value = option.dataset.displayUrl || option.dataset.url || "";
        if (openLink) openLink.href = option.dataset.url || "#";

        if (access) {
          access.textContent = option.dataset.access || "";
          access.classList.toggle("warn", option.dataset.accessKind === "private");
          access.classList.toggle("good", option.dataset.accessKind !== "private");
        }

        if (rsvp) {
          rsvp.textContent = option.dataset.rsvp || "";
          rsvp.classList.toggle("warn", option.dataset.rsvpEnabled === "1");
        }
      };

      select?.addEventListener("change", syncCategoryPreview);
      copyButton?.addEventListener("click", async () => {
        const value = urlInput?.value || "";
        if (!value) return;

        try {
          await navigator.clipboard.writeText(value);
          copyButton.textContent = "Tersalin";
          window.setTimeout(() => {
            copyButton.textContent = "Salin Link";
          }, 1400);
        } catch (error) {
          urlInput?.select();
          document.execCommand("copy");
        }
      });
      syncCategoryPreview();
    });

    const archiveCountUpNodes = Array.from(document.querySelectorAll("[data-count-up]"));
    const archiveCountdownNodes = Array.from(document.querySelectorAll("[data-countdown-target]"));
    const numberFormatter = new Intl.NumberFormat("id-ID");
    const activeCountUp = new WeakSet();

    const animateCountUp = (node) => {
      if (!node || activeCountUp.has(node)) return;
      activeCountUp.add(node);

      const target = Number.parseInt(node.dataset.target || "0", 10) || 0;
      if (reduceMotion || target <= 0) {
        node.textContent = numberFormatter.format(Math.max(0, target));
        return;
      }

      const duration = 900;
      const start = performance.now();

      const step = (timestamp) => {
        const progress = Math.min((timestamp - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        node.textContent = numberFormatter.format(Math.round(target * eased));

        if (progress < 1) {
          window.requestAnimationFrame(step);
        }
      };

      window.requestAnimationFrame(step);
    };

    if (archiveCountUpNodes.length) {
      if (reduceMotion) {
        archiveCountUpNodes.forEach(animateCountUp);
      } else if ("IntersectionObserver" in window) {
        const archiveObserver = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              animateCountUp(entry.target);
              archiveObserver.unobserve(entry.target);
            }
          });
        }, { threshold: 0.35 });

        archiveCountUpNodes.forEach((node) => archiveObserver.observe(node));
      } else {
        archiveCountUpNodes.forEach(animateCountUp);
      }
    }

    const countdownTargets = archiveCountdownNodes.map((node) => {
      const target = new Date(node.dataset.countdownTarget || "");
      return {
        node,
        target: Number.isNaN(target.getTime()) ? null : target.getTime(),
        days: node.querySelector("[data-countdown-days]"),
        hours: node.querySelector("[data-countdown-hours]"),
        minutes: node.querySelector("[data-countdown-minutes]"),
        seconds: node.querySelector("[data-countdown-seconds]"),
      };
    }).filter((item) => item.target !== null);

    const updateCountdown = () => {
      const now = Date.now();

      countdownTargets.forEach((item) => {
        const remaining = Math.max(0, item.target - now);
        const totalSeconds = Math.floor(remaining / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (item.days) item.days.textContent = String(days).padStart(2, "0");
        if (item.hours) item.hours.textContent = String(hours).padStart(2, "0");
        if (item.minutes) item.minutes.textContent = String(minutes).padStart(2, "0");
        if (item.seconds) item.seconds.textContent = String(seconds).padStart(2, "0");
      });
    };

    if (countdownTargets.length) {
      updateCountdown();
      if (!reduceMotion) {
        window.setInterval(updateCountdown, 1000);
      }
    }

    const forceScrollTop = () => {
      window.scrollTo({ top: 0, left: 0, behavior: "auto" });
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    };

    const scrollToTarget = (target, block = "start") => {
      if (!target) return;
      target.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block });
    };

    const scrollToRsvp = () => {
      if (!rsvpSection) return;
      scrollToTarget(rsvpSection, isMobileView ? "center" : "start");
      window.sessionStorage.removeItem(RSVP_RETURN_KEY);
      window.setTimeout(() => {
        const preferredInput = rsvpSection.querySelector("[aria-invalid='true'], .is-invalid, #nim, #birth_date, input:not([type='hidden']), textarea");
        preferredInput?.focus?.({ preventScroll: true });
      }, reduceMotion ? 0 : 350);
    };

    document.querySelectorAll("#rsvpSection form").forEach((form) => {
      form.addEventListener("submit", (event) => {
        window.sessionStorage.setItem(RSVP_RETURN_KEY, "1");

        const submitter = event.submitter;
        if (submitter?.name && !form.querySelector(`input[type="hidden"][name="${submitter.name}"][data-submit-mirror="1"]`)) {
          const mirrorInput = document.createElement("input");
          mirrorInput.type = "hidden";
          mirrorInput.name = submitter.name;
          mirrorInput.value = submitter.value;
          mirrorInput.dataset.submitMirror = "1";
          form.appendChild(mirrorInput);
        }

        window.setTimeout(() => {
          const button = submitter || form.querySelector("button[type='submit']");
          if (!button) return;

          button.dataset.originalText = button.textContent;
          button.textContent = "Memproses...";
          button.disabled = true;
          button.classList.add("is-loading");
        }, 0);
      });
    });

    const setupDeclineNote = ({ formId, fieldId, noteId, labelId, delegateFieldId }) => {
      const form = document.getElementById(formId);
      const noteField = document.getElementById(fieldId);
      const note = document.getElementById(noteId);
      const noteLabel = document.getElementById(labelId);
      const delegateField = document.getElementById(delegateFieldId);
      const delegateInputs = delegateField ? Array.from(delegateField.querySelectorAll("input")) : [];
      if (!form || !noteField || !note || !noteLabel) return;

      const attendanceInputs = Array.from(form.querySelectorAll("input[name='attendance']"));
      const syncNoteHint = () => {
        const selectedAttendance = attendanceInputs.find((input) => input.checked)?.value;
        const isDeclined = selectedAttendance === "declined";
        const isRepresented = selectedAttendance === "represented";
        noteField.hidden = !isDeclined;
        noteField.style.display = isDeclined ? "" : "none";
        note.required = isDeclined;
        noteLabel.textContent = "Catatan berhalangan";
        note.placeholder = note.dataset.declinedPlaceholder || "Tuliskan alasan berhalangan hadir secara singkat.";

        if (!isDeclined) {
          note.value = "";
        }

        if (delegateField) {
          delegateField.hidden = !isRepresented;
          delegateField.style.display = isRepresented ? "" : "none";
          delegateInputs.forEach((input) => {
            input.required = isRepresented;

            if (!isRepresented) {
              input.value = "";
            }
          });
        }
      };

      attendanceInputs.forEach((input) => {
        input.addEventListener("change", syncNoteHint);
      });
      syncNoteHint();
    };

    setupDeclineNote({
      formId: "participantRsvpForm",
      fieldId: "participantNoteField",
      noteId: "note",
      labelId: "participantNoteLabel",
      delegateFieldId: "participantDelegateField",
    });
    setupDeclineNote({
      formId: "recipientRsvpForm",
      fieldId: "recipientNoteField",
      noteId: "recipient-note",
      labelId: "recipientNoteLabel",
      delegateFieldId: "recipientDelegateField",
    });

    let spotlightFrame = null;

    const isLastTutorialStep = (stepIndex) => stepIndex >= rsvpTutorialSteps.length - 1;

    const isInvitationTutorialStep = (stepIndex) => !isLastTutorialStep(stepIndex);

    const getTutorialSpotlightTarget = (stepIndex) => {
      if (isLastTutorialStep(stepIndex)) {
        return rsvpSection;
      }

      return invitationIntroCard || invitationDetailsBlock;
    };

    const clearTutorialTargets = () => {
      rsvpSection?.classList.remove("is-tutorial-target");
      invitationDetailsBlock?.classList.remove("is-tutorial-target");
      invitationDetailsHeader?.classList.remove("is-tutorial-target");
      invitationIntroCard?.classList.remove("is-tutorial-target");
    };

    const updateTutorialSpotlight = (stepIndex) => {
      if (!rsvpSpotlight || rsvpTutorial?.hidden) return;

      const pad = 10;
      let top;
      let left;
      let width;
      let height;

      if (isInvitationTutorialStep(stepIndex) && invitationDetailsHeader && (invitationIntroCard || invitationDetailsBlock)) {
        const headerRect = invitationDetailsHeader.getBoundingClientRect();
        const contentRect = (invitationIntroCard || invitationDetailsBlock).getBoundingClientRect();
        top = Math.min(headerRect.top, contentRect.top) - pad;
        left = Math.min(headerRect.left, contentRect.left) - pad;
        width = Math.max(headerRect.right, contentRect.right) - Math.min(headerRect.left, contentRect.left) + pad * 2;
        height = Math.max(headerRect.bottom, contentRect.bottom) - Math.min(headerRect.top, contentRect.top) + pad * 2;
      } else {
        const targetEl = getTutorialSpotlightTarget(stepIndex);
        if (!targetEl) return;
        const rect = targetEl.getBoundingClientRect();
        top = rect.top - pad;
        left = rect.left - pad;
        width = rect.width + pad * 2;
        height = rect.height + pad * 2;
      }

      rsvpSpotlight.style.top = `${Math.max(top, 0)}px`;
      rsvpSpotlight.style.left = `${Math.max(left, 0)}px`;
      rsvpSpotlight.style.width = `${width}px`;
      rsvpSpotlight.style.height = `${height}px`;
      rsvpSpotlight.removeAttribute("hidden");
      rsvpSpotlight.hidden = false;
      rsvpSpotlight.classList.add("is-active");
    };

    const applyTutorialSpotlight = (stepIndex, options = {}) => {
      const target = getTutorialSpotlightTarget(stepIndex);
      if (!target) return;

      clearTutorialTargets();
      target.classList.add("is-tutorial-target");

      if (isInvitationTutorialStep(stepIndex) && invitationDetailsHeader) {
        invitationDetailsHeader.classList.add("is-tutorial-target");
      }

      if (isInvitationTutorialStep(stepIndex) && invitationIntroCard) {
        invitationIntroCard.classList.add("is-tutorial-target");
      }

      const shouldScroll = options.scroll === true;
      if (shouldScroll) {
        if (isInvitationTutorialStep(stepIndex)) {
          forceScrollTop();
          window.setTimeout(() => updateTutorialSpotlight(stepIndex), 80);
        } else {
          const scrollTarget = rsvpSection || target;
          scrollToTarget(
            scrollTarget,
            isMobileView ? "center" : "start"
          );
          window.setTimeout(() => updateTutorialSpotlight(stepIndex), reduceMotion ? 0 : 120);
        }
      } else {
        updateTutorialSpotlight(stepIndex);
      }
    };

    const refreshTutorialSpotlight = () => {
      applyTutorialSpotlight(rsvpTutorialStepIndex, { scroll: false });
    };

    const hideRsvpSpotlight = () => {
      if (!rsvpSpotlight) return;
      rsvpSpotlight.classList.remove("is-active");
      rsvpSpotlight.hidden = true;
    };

    const bindSpotlightTracking = () => {
      const scheduleUpdate = () => {
        if (spotlightFrame) window.cancelAnimationFrame(spotlightFrame);
        spotlightFrame = window.requestAnimationFrame(refreshTutorialSpotlight);
      };

      window.addEventListener("scroll", scheduleUpdate, { passive: true });
      window.addEventListener("resize", scheduleUpdate);
    };

    const finishRsvpTutorial = () => {
      if (!rsvpTutorial) return;

      rsvpTutorial.hidden = true;
      rsvpTutorial.classList.remove("is-visible");
      document.body.classList.remove("rsvp-tutorial-open");
      clearTutorialTargets();
      hideRsvpSpotlight();

      window.scrollTo({ top: 0, behavior: reduceMotion ? "auto" : "smooth" });
      forceScrollTop();
    };

    let rsvpTutorialBound = false;
    let rsvpTutorialStepIndex = 0;
    let rsvpTutorialTypingTimer = null;
    let rsvpTutorialStarted = false;
    let runCurrentRsvpTutorialStep = null;

    const initRsvpTutorial = () => {
      if (!rsvpTutorial || rsvpTutorialBound) return;
      rsvpTutorialBound = true;

      const textEl = document.getElementById("rsvpTutorialText");
      const progressEl = document.getElementById("rsvpTutorialProgress");
      const nextBtn = document.getElementById("rsvpTutorialNext");
      const skipBtn = document.getElementById("rsvpTutorialSkip");

      const renderTutorialText = (partial, withCursor = false) => {
        if (!textEl) return;
        textEl.textContent = "";
        textEl.append(document.createTextNode(partial));
        if (withCursor) {
          const cursor = document.createElement("span");
          cursor.className = "rsvp-tutorial-cursor";
          cursor.setAttribute("aria-hidden", "true");
          cursor.textContent = "|";
          textEl.append(cursor);
        }
      };

      const updateNextButtonVisibility = () => {
        if (!nextBtn) return;

        const isLastStep = rsvpTutorialStepIndex >= rsvpTutorialSteps.length - 1;
        nextBtn.hidden = isLastStep;
        if (isLastStep) {
          nextBtn.setAttribute("hidden", "");
        } else {
          nextBtn.removeAttribute("hidden");
        }
      };

      const showControls = () => {
        const safeIndex = Math.min(rsvpTutorialStepIndex, rsvpTutorialSteps.length - 1);
        renderTutorialText(rsvpTutorialSteps[safeIndex] || "", false);
        updateNextButtonVisibility();
      };

      const typeStep = (onComplete) => {
        const safeIndex = Math.min(rsvpTutorialStepIndex, rsvpTutorialSteps.length - 1);
        const text = rsvpTutorialSteps[safeIndex] || "";
        if (progressEl) {
          progressEl.textContent = `${safeIndex + 1} / ${rsvpTutorialSteps.length}`;
        }

        const shouldScrollSpotlight = isLastTutorialStep(safeIndex);
        applyTutorialSpotlight(safeIndex, { scroll: shouldScrollSpotlight });
        updateNextButtonVisibility();

        if (!textEl) {
          onComplete();
          return;
        }

        if (reduceMotion) {
          renderTutorialText(text, false);
          onComplete();
          return;
        }

        let index = 0;
        const tick = () => {
          renderTutorialText(text.slice(0, index + 1), true);
          index += 1;
          if (index < text.length) {
            rsvpTutorialTypingTimer = window.setTimeout(tick, 26);
          } else {
            onComplete();
          }
        };
        tick();
      };

      runCurrentRsvpTutorialStep = () => {
        typeStep(() => {
          window.setTimeout(showControls, reduceMotion ? 0 : 350);
        });
      };

      nextBtn?.addEventListener("click", () => {
        if (rsvpTutorialStepIndex >= rsvpTutorialSteps.length - 1) {
          return;
        }

        if (rsvpTutorialTypingTimer) {
          window.clearTimeout(rsvpTutorialTypingTimer);
          rsvpTutorialTypingTimer = null;
        }

        rsvpTutorialStepIndex += 1;
        runCurrentRsvpTutorialStep?.();
      });

      skipBtn?.addEventListener("click", finishRsvpTutorial);
      bindSpotlightTracking();
    };

    const revealRsvpTutorial = () => {
      if (!rsvpTutorial) return;
      rsvpTutorial.removeAttribute("hidden");
      rsvpTutorial.hidden = false;
      rsvpTutorial.classList.add("is-visible");
      document.body.classList.add("rsvp-tutorial-open");
    };

    const scrollToRsvpTutorial = () => {
      applyTutorialSpotlight(rsvpTutorialStepIndex, { scroll: true });
    };

    const startRsvpTutorial = (options = {}) => {
      const shouldScroll = options.scroll !== false;

      if (rsvpTutorialStarted) {
        if (shouldScroll) {
          scrollToRsvpTutorial();
        } else {
          refreshTutorialSpotlight();
        }
        return;
      }

      if (!showRsvpTutorialOnOpen) return;
      if (!rsvpTutorial || !rsvpSection || !rsvpTutorialSteps.length) return;

      rsvpTutorialStarted = true;
      initRsvpTutorial();
      rsvpTutorialStepIndex = 0;

      revealRsvpTutorial();
      forceScrollTop();
      runCurrentRsvpTutorialStep?.();

      if (!shouldScroll) {
        refreshTutorialSpotlight();
      }
    };

    const prepareInvitationRevealForTutorial = () => {
      revealItems.slice(0, 1).forEach((item) => item.classList.add("in-view"));
    };

    const handleInvitationOpenActions = (options = {}) => {
      if (shouldReturnToRsvp) {
        scrollToRsvp();
        return;
      }

      if (!showRsvpTutorialOnOpen) return;

      prepareInvitationRevealForTutorial();
      startRsvpTutorial(options);
    };

    const openInvitation = () => {
      if (document.body.classList.contains("opening") || document.body.classList.contains("opened")) {
        return;
      }

      if (reduceMotion) {
        if (cover) cover.style.display = "none";
        document.body.classList.add("opened");
        handleInvitationOpenActions();
        return;
      }

      if (cover) {
        cover.style.position = "fixed";
        cover.style.inset = "0";
        cover.style.top = "0";
        cover.style.left = "0";
        cover.style.width = "100%";
        cover.style.height = "100%";
        cover.style.margin = "0";
        cover.style.zIndex = "40";
      }

      window.scrollTo({ top: 0, behavior: "auto" });
      document.body.classList.add("opening");
      document.body.classList.add("animating");
      handleInvitationOpenActions({ scroll: true });
      if (openButton) {
        openButton.disabled = true;
        openButton.classList.add("is-pressed");
      }

      window.setTimeout(() => {
        if (openButton) openButton.classList.remove("is-pressed");
      }, 280);

      window.setTimeout(() => {
        if (cover) cover.style.display = "none";
        document.body.classList.remove("opening");
        document.body.classList.add("opened");
        document.body.classList.remove("animating");
        forceScrollTop();
        window.requestAnimationFrame(forceScrollTop);

        if (shouldReturnToRsvp) {
          scrollToRsvp();
          return;
        }

        if (showRsvpTutorialOnOpen && rsvpTutorialStarted) {
          forceScrollTop();
          refreshTutorialSpotlight();
          return;
        }

        if (!showRsvpTutorialOnOpen) {
          forceScrollTop();
        }
      }, INVITATION_OPEN_MS);
    };

    openButton?.addEventListener("click", openInvitation);

    if (previewMode === "open" || shouldReturnToRsvp) {
      window.requestAnimationFrame(() => {
        if (document.body.classList.contains("opened")) {
          if (shouldReturnToRsvp) {
            window.setTimeout(scrollToRsvp, reduceMotion ? 0 : 120);
          }
          return;
        }

        openInvitation();
      });
    }

    const setupBackgroundVideo = () => {
      if (!backgroundVideo) return;

      const revealVideo = () => backgroundVideo.classList.add("is-ready");
      backgroundVideo.addEventListener("loadeddata", revealVideo, { once: true });
      backgroundVideo.addEventListener("canplay", revealVideo, { once: true });

      backgroundVideo.play().then(revealVideo).catch(() => {
        // Keep static gradient fallback when autoplay is blocked.
      });
    };

    const setupLogoFallback = () => {
      const logoImages = Array.from(document.querySelectorAll("img[data-logo='unmul']"));
      logoImages.forEach((img) => {
        let currentIndex = logoCandidates.findIndex((name) => name === img.getAttribute("src"));
        if (currentIndex < 0) currentIndex = 0;
        img.dataset.logoIndex = String(currentIndex);

        const trySource = (index) => {
          if (index >= logoCandidates.length) {
            img.style.display = "none";
            if (img.id === "logoImage" && logoFallback) {
              logoFallback.style.display = "block";
            }
            return;
          }
          img.dataset.logoIndex = String(index);
          img.src = logoCandidates[index];
        };

        img.addEventListener("load", () => {
          img.style.display = "block";
          if (img.id === "logoImage" && logoFallback) {
            logoFallback.style.display = "none";
          }
        });

        img.addEventListener("error", () => {
          const next = Number(img.dataset.logoIndex || "0") + 1;
          trySource(next);
        });

        if (img.complete && img.naturalWidth === 0) {
          trySource(currentIndex + 1);
        }
      });
    };

    revealItems.forEach((item, index) => {
      item.classList.add("reveal-item");
      item.style.transitionDelay = `${Math.min(index * 120, 420)}ms`;
    });

    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("in-view");
            revealObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.18 }
    );

    revealItems.forEach((item) => revealObserver.observe(item));

    const setupAssetPlaceholder = (imageId, placeholderId) => {
      const image = document.getElementById(imageId);
      const placeholder = document.getElementById(placeholderId);

      if (!image || !placeholder) return;

      const showPlaceholder = () => {
        image.style.display = "none";
        placeholder.style.display = "block";
      };

      const showImage = () => {
        image.style.display = "block";
        placeholder.style.display = "none";
      };

      image.addEventListener("load", () => {
        if (image.naturalWidth > 0) {
          showImage();
        } else {
          showPlaceholder();
        }
      });

      image.addEventListener("error", showPlaceholder);

      if (image.complete && image.naturalWidth > 0) {
        showImage();
      } else {
        showPlaceholder();
      }
    };

    setupBackgroundVideo();
    setupLogoFallback();
    setupAssetPlaceholder("ttdImage", "ttdPlaceholder");
    setupAssetPlaceholder("stampImage", "stampPlaceholder");

</script>
