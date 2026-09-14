(() => {
  const bootEl = document.getElementById("formal-invitation-boot");
  const boot = bootEl ? JSON.parse(bootEl.textContent || "{}") : {};
var stage = document.getElementById('formalPreviewStage');
            var cover = document.getElementById('formalCover');
            var openButton = document.getElementById('formalOpenButton');
            var revealItems = Array.prototype.slice.call(document.querySelectorAll('.letter-reveal'));
            var tutorial = document.getElementById('formalTutorial');
            var tutorialText = document.getElementById('formalTutorialText');
            var tutorialProgress = document.getElementById('formalTutorialProgress');
            var tutorialNext = document.getElementById('formalTutorialNext');
            var tutorialSkip = document.getElementById('formalTutorialSkip');
            var spotlight = document.getElementById('formalSpotlight');
            var tutorialCard = document.getElementById('formalTutorialCard');
            var tutorialIndex = 0;
            var tutorialFinalLabel = 'Mulai baca';
            var tutorialCloseTarget = null;
            var highlightedTarget = null;
            var tutorialTransitionTimer = null;
            var spotlightFrame = null;
            var initialHashTarget = window.location.hash
                ? document.getElementById(window.location.hash.slice(1))
                : null;
            var tutorialAudience = boot.tutorialAudience || '';
            var shouldShowStudentQrGuide = Boolean(boot.shouldShowStudentQrGuide);

            function openingGreeting() {
                var hour = new Date().getHours();

                if (hour >= 4 && hour < 10) return 'Selamat pagi';
                if (hour >= 10 && hour < 15) return 'Selamat siang';
                if (hour >= 15 && hour < 18) return 'Selamat sore';

                return 'Selamat malam';
            }

            function openingTutorialText() {
                var audience = tutorialAudience ? ', ' + tutorialAudience : '';

                return openingGreeting() + audience + '. Terima kasih sudah membuka undangan ini. Saya bantu arahkan sebentar supaya bagian pentingnya mudah diikuti.';
            }

            var defaultTutorialSteps = (boot.tutorialSteps || []).map(function (step) {
                if (step.text === '__OPENING__') {
                    return { text: openingTutorialText(), target: step.target };
                }
                return step;
            });
            var tutorialSteps = defaultTutorialSteps;
            var studentQrGuideSteps = boot.studentQrGuideSteps || [];

            function lockCoverScroll() {
                if (cover && !stage.classList.contains('is-open')) {
                    document.documentElement.classList.add('formal-cover-locked');
                }
            }

            function unlockCoverScroll() {
                document.documentElement.classList.remove('formal-cover-locked');
            }

            function startFormalBackgroundVideo() {
                var video = stage ? stage.querySelector('.formal-bg-video') : null;
                var overlay = stage ? stage.querySelector('.formal-bg-overlay') : null;
                if (!video || video.dataset.started === '1') {
                    return;
                }
                video.dataset.started = '1';
                video.hidden = false;
                if (overlay) {
                    overlay.hidden = false;
                }
                var source = video.querySelector('source[data-src]');
                if (source && !source.getAttribute('src')) {
                    source.setAttribute('src', source.dataset.src || '');
                    video.load();
                }
                video.play().catch(function () {});
            }

            function scheduleFormalBackgroundVideo() {
                var start = function () {
                    startFormalBackgroundVideo();
                };
                if (window.requestIdleCallback) {
                    window.requestIdleCallback(start, { timeout: 2200 });
                } else {
                    window.setTimeout(start, 800);
                }
            }

            function openInvitation(options) {
                options = options || {};
                scheduleFormalBackgroundVideo();
                stage.classList.add('is-open');
                cover.classList.add('is-hidden');
                unlockCoverScroll();

                window.setTimeout(function () {
                    revealItems.slice(0, 3).forEach(function (item) {
                        item.classList.add('is-visible');
                    });

                    if (options.target) {
                        options.target.classList.add('is-visible');
                        options.target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }

                    if (!options.skipTutorial) {
                        openTutorial();
                    }
                }, 180);
            }

            function clearHighlight() {
                if (highlightedTarget) {
                    highlightedTarget.classList.remove('is-playground-highlight');
                    highlightedTarget = null;
                }

                spotlight.classList.remove('is-visible');
            }

            function moveSpotlight(target) {
                window.cancelAnimationFrame(spotlightFrame);
                spotlightFrame = window.requestAnimationFrame(function () {
                    applySpotlight(target);
                });
            }

            function applySpotlight(target) {
                if (!target) {
                    clearHighlight();
                    return;
                }

                if (highlightedTarget && highlightedTarget !== target) {
                    highlightedTarget.classList.remove('is-playground-highlight');
                }

                highlightedTarget = target;
                highlightedTarget.classList.add('is-playground-highlight');

                var rect = target.getBoundingClientRect();
                var pad = 12;
                var compactViewport = window.innerWidth <= 900 || window.innerHeight <= 640;
                var cardRect = tutorialCard.getBoundingClientRect();
                var availableBottom = compactViewport
                    ? Math.max(120, cardRect.top - 14)
                    : window.innerHeight - 10;
                var maxSpotlightHeight = compactViewport
                    ? Math.max(220, availableBottom - Math.max(rect.top - pad, 10))
                    : Math.max(180, window.innerHeight * 0.56);
                var spotlightHeight = Math.min(rect.height + pad * 2, maxSpotlightHeight, window.innerHeight - 20);
                spotlight.style.top = Math.max(rect.top - pad, 10) + 'px';
                spotlight.style.left = Math.max(rect.left - pad, 10) + 'px';
                spotlight.style.width = Math.min(rect.width + pad * 2, window.innerWidth - 20) + 'px';
                spotlight.style.height = spotlightHeight + 'px';
                spotlight.classList.add('is-visible');
                positionTutorialCard(rect);
            }

            function positionTutorialCard(targetRect) {
                var gap = 18;
                var margin = 14;

                tutorialCard.style.top = '';
                tutorialCard.style.left = '';
                tutorialCard.style.right = '';
                tutorialCard.style.bottom = '';

                if (window.innerWidth <= 900 || window.innerHeight <= 640) {
                    return;
                }

                var cardRect = tutorialCard.getBoundingClientRect();
                var cardWidth = Math.min(cardRect.width || 700, window.innerWidth - margin * 2);
                var cardHeight = Math.min(cardRect.height || 280, window.innerHeight - margin * 2);
                var spaceRight = window.innerWidth - targetRect.right;
                var spaceLeft = targetRect.left;
                var placeRight = spaceRight >= cardWidth + gap || spaceRight >= spaceLeft;
                var left = placeRight
                    ? Math.min(targetRect.right + gap, window.innerWidth - cardWidth - margin)
                    : Math.max(margin, targetRect.left - cardWidth - gap);

                var targetCenter = targetRect.top + (Math.min(targetRect.height, window.innerHeight) / 2);
                var top = Math.max(margin, Math.min(targetCenter - cardHeight / 2, window.innerHeight - cardHeight - margin));

                tutorialCard.style.left = left + 'px';
                tutorialCard.style.top = top + 'px';
            }

            function scrollTargetForTutorial(target) {
                var rect = target.getBoundingClientRect();
                var absoluteTop = rect.top + window.scrollY;
                var visualOffset;

                if (window.innerWidth <= 900 || window.innerHeight <= 640) {
                    visualOffset = target.id === 'letterAgenda'
                        ? Math.max(26, window.innerHeight * 0.07)
                        : Math.max(76, window.innerHeight * 0.18);
                } else if (window.innerWidth <= 1024) {
                    visualOffset = Math.max(96, window.innerHeight * 0.18);
                } else {
                    visualOffset = Math.max(110, window.innerHeight * 0.16);
                }

                window.scrollTo({
                    top: Math.max(0, absoluteTop - visualOffset),
                    behavior: 'smooth'
                });
            }

            function showTutorialStep() {
                var step = tutorialSteps[tutorialIndex] || tutorialSteps[0];
                var target = step.target ? document.getElementById(step.target) : null;

                window.clearTimeout(tutorialTransitionTimer);
                tutorialCard.classList.add('is-changing');

                if (target) {
                    target.classList.add('is-visible');
                    scrollTargetForTutorial(target);
                } else {
                    clearHighlight();
                }

                tutorialTransitionTimer = window.setTimeout(function () {
                    tutorialText.textContent = step.text;
                    tutorialProgress.textContent = (tutorialIndex + 1) + ' / ' + tutorialSteps.length;
                    tutorialNext.textContent = tutorialIndex >= tutorialSteps.length - 1 ? tutorialFinalLabel : 'Lanjut';

                    if (target) {
                        moveSpotlight(target);
                        window.setTimeout(function () {
                            moveSpotlight(target);
                        }, 260);
                    }

                    tutorialCard.classList.remove('is-changing');
                }, 130);
            }

            function openTutorial(customSteps, options) {
                if (!tutorial || !tutorialCard) return;

                options = options || {};
                tutorialSteps = customSteps || defaultTutorialSteps;
                tutorialFinalLabel = options.finalLabel || 'Mulai baca';
                tutorialCloseTarget = options.closeTarget || null;
                tutorialIndex = 0;
                tutorial.classList.add('is-visible');
                showTutorialStep();
            }

            function closeTutorial() {
                window.clearTimeout(tutorialTransitionTimer);
                tutorial.classList.remove('is-visible');
                tutorialCard.classList.remove('is-changing');
                clearHighlight();
                var closeTarget = tutorialCloseTarget ? document.getElementById(tutorialCloseTarget) : null;
                if (closeTarget) {
                    closeTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                tutorialCloseTarget = null;
            }

            if (openButton) {
                openButton.addEventListener('click', function () {
                    openInvitation();
                });
            }

            lockCoverScroll();

            if (initialHashTarget) {
                openInvitation({
                    skipTutorial: true,
                    target: initialHashTarget
                });
            }

            if ('IntersectionObserver' in window) {
                var revealObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                        }
                    });
                }, {
                    rootMargin: '0px 0px -12% 0px',
                    threshold: 0.12
                });

                revealItems.forEach(function (item) {
                    revealObserver.observe(item);
                });
            } else {
                revealItems.forEach(function (item) {
                    item.classList.add('is-visible');
                });
            }

            if (tutorialNext) {
                tutorialNext.addEventListener('click', function () {
                    if (tutorialIndex >= tutorialSteps.length - 1) {
                        closeTutorial();
                        return;
                    }

                    tutorialIndex += 1;
                    showTutorialStep();
                });
            }

            if (tutorialSkip) {
                tutorialSkip.addEventListener('click', closeTutorial);
            }

            window.addEventListener('resize', function () {
                if (tutorial.classList.contains('is-visible') && highlightedTarget) {
                    moveSpotlight(highlightedTarget);
                }
            });

            var scrollFrame;
            window.addEventListener('scroll', function () {
                if (tutorial.classList.contains('is-visible') && highlightedTarget) {
                    window.cancelAnimationFrame(scrollFrame);
                    scrollFrame = window.requestAnimationFrame(function () {
                        applySpotlight(highlightedTarget);
                    });
                }
            }, { passive: true });

            function loadCardImage(src) {
                return new Promise(function (resolve) {
                    if (!src) {
                        resolve(null);
                        return;
                    }

                    var image = new Image();
                    image.crossOrigin = 'anonymous';
                    image.onload = function () { resolve(image); };
                    image.onerror = function () { resolve(null); };
                    image.src = src;
                });
            }

            function roundedRect(context, x, y, width, height, radius) {
                context.beginPath();
                context.moveTo(x + radius, y);
                context.arcTo(x + width, y, x + width, y + height, radius);
                context.arcTo(x + width, y + height, x, y + height, radius);
                context.arcTo(x, y + height, x, y, radius);
                context.arcTo(x, y, x + width, y, radius);
                context.closePath();
            }

            function fitText(context, text, x, y, maxWidth, lineHeight, maxLines) {
                var words = String(text || '').split(/\s+/).filter(Boolean);
                var line = '';
                var lines = [];

                words.forEach(function (word) {
                    var testLine = line ? line + ' ' + word : word;
                    if (context.measureText(testLine).width > maxWidth && line) {
                        lines.push(line);
                        line = word;
                    } else {
                        line = testLine;
                    }
                });

                if (line) lines.push(line);
                lines.slice(0, maxLines).forEach(function (item, index) {
                    var output = item;
                    if (index === maxLines - 1 && lines.length > maxLines) {
                        while (context.measureText(output + '...').width > maxWidth && output.length > 0) {
                            output = output.slice(0, -1);
                        }
                        output += '...';
                    }
                    context.fillText(output, x, y + (index * lineHeight));
                });
            }

            function downloadCanvas(canvas, fileName) {
                var link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = fileName || 'qr-buku-tamu.png';
                document.body.appendChild(link);
                link.click();
                link.remove();
            }

            function openStudentQrGuide() {
                var target = document.getElementById('letterStudentQr');
                if (!target || !tutorial) return;

                target.classList.add('is-visible');

                if (!stage.classList.contains('is-open')) {
                    openInvitation({
                        skipTutorial: true,
                        target: target
                    });
                }

                window.setTimeout(function () {
                    openTutorial(studentQrGuideSteps, {
                        finalLabel: 'Selesai',
                        closeTarget: 'letterStudentQr'
                    });
                }, 520);
            }

            async function drawQrCard(card, qrCanvas, fileName) {
                var canvas = document.createElement('canvas');
                canvas.width = 900;
                canvas.height = 1280;
                var context = canvas.getContext('2d');
                var logo = await loadCardImage(card.dataset.unmulLogo);
                var qrImage = await loadCardImage(qrCanvas.toDataURL('image/png'));

                context.fillStyle = '#f8fafc';
                context.fillRect(0, 0, canvas.width, canvas.height);

                context.save();
                roundedRect(context, 54, 54, 792, 1172, 34);
                context.fillStyle = '#ffffff';
                context.fill();
                context.strokeStyle = '#e5e7eb';
                context.lineWidth = 2;
                context.stroke();
                context.restore();

                if (logo) {
                    context.save();
                    context.globalAlpha = 0.045;
                    context.translate(688, 1080);
                    context.rotate(-18 * Math.PI / 180);
                    context.drawImage(logo, -250, -250, 500, 500);
                    context.restore();
                }

                context.fillStyle = '#475467';
                context.font = '600 30px Manrope, Arial, sans-serif';
                fitText(context, card.dataset.eventTitle || '', 96, 142, 700, 42, 2);

                context.fillStyle = '#111827';
                context.font = '800 46px Manrope, Arial, sans-serif';
                fitText(context, card.dataset.studentName || '', 96, 290, 700, 54, 2);

                context.fillStyle = '#667085';
                context.font = '600 28px Manrope, Arial, sans-serif';
                fitText(context, (card.dataset.studentNim || '-') + ' - ' + (card.dataset.studentProgram || '-'), 96, 400, 700, 38, 2);

                context.fillStyle = '#9a3412';
                context.font = '700 25px Manrope, Arial, sans-serif';
                fitText(context, card.dataset.eventDate || '', 96, 484, 700, 32, 1);

                context.save();
                roundedRect(context, 106, 554, 688, 688, 30);
                context.fillStyle = '#ffffff';
                context.fill();
                context.strokeStyle = '#e5e7eb';
                context.lineWidth = 2;
                context.stroke();
                context.restore();

                if (qrImage) {
                    context.drawImage(qrImage, 146, 594, 608, 608);
                }

                downloadCanvas(canvas, fileName);
            }

            function setQrFrameState(frame, state) {
                if (!frame) return;
                frame.dataset.qrState = state;
            }

            function whenQrLibraryReady(onReady, onTimeout) {
                if (window.QRCode) {
                    onReady();
                    return;
                }

                var attempts = 0;
                var timer = window.setInterval(function () {
                    attempts += 1;
                    if (window.QRCode) {
                        window.clearInterval(timer);
                        onReady();
                    } else if (attempts >= 80) {
                        window.clearInterval(timer);
                        if (onTimeout) onTimeout();
                    }
                }, 100);
            }

            function renderStudentQrCard(card) {
                var frame = card.querySelector('[data-qr-frame]');
                var qrCanvas = card.querySelector('[data-qr-canvas]');
                var retryButton = card.querySelector('[data-qr-retry]');

                if (!frame || !qrCanvas) return;

                var drawQr = function () {
                    setQrFrameState(frame, 'loading');
                    qrCanvas.hidden = true;

                    whenQrLibraryReady(function () {
                        var payload = card.dataset.qrPayload || '';
                        var options = {
                            errorCorrectionLevel: 'M',
                            margin: 2,
                            scale: 6,
                            width: 220,
                            color: {
                                dark: '#111827',
                                light: '#ffffff'
                            }
                        };

                        try {
                            var result = window.QRCode.toCanvas(qrCanvas, payload, options);
                            Promise.resolve(result).then(function () {
                                qrCanvas.hidden = false;
                                setQrFrameState(frame, 'ready');
                            }).catch(function () {
                                setQrFrameState(frame, 'error');
                            });
                        } catch (error) {
                            setQrFrameState(frame, 'error');
                        }
                    }, function () {
                        setQrFrameState(frame, 'error');
                    });
                };

                drawQr();

                if (retryButton) {
                    retryButton.addEventListener('click', drawQr);
                }

                var button = card.querySelector('[data-download-qr-card]');
                if (button) {
                    button.addEventListener('click', function () {
                        if (!qrCanvas.width) return;
                        button.classList.add('is-loading');
                        drawQrCard(card, qrCanvas, button.dataset.fileName || 'qr-buku-tamu.png')
                            .finally(function () {
                                button.classList.remove('is-loading');
                            });
                    });
                }
            }

            document.querySelectorAll('[data-student-qr-card]').forEach(renderStudentQrCard);

            if (shouldShowStudentQrGuide) {
                window.setTimeout(openStudentQrGuide, 720);
            }

            function ensureSavingOverlay() {
                var overlay = document.getElementById('uiSavingOverlay');
                if (overlay) {
                    return overlay;
                }

                overlay = document.createElement('div');
                overlay.id = 'uiSavingOverlay';
                overlay.className = 'ui-saving-overlay';
                overlay.hidden = true;
                overlay.setAttribute('role', 'status');
                overlay.setAttribute('aria-live', 'polite');
                overlay.innerHTML = '<div class="ui-saving-overlay__card"><div class="ui-spinner" aria-hidden="true"></div><p>Menyimpan konfirmasi…</p></div>';
                document.body.appendChild(overlay);

                return overlay;
            }

            function showSavingOverlay() {
                ensureSavingOverlay().hidden = false;
                document.body.classList.add('is-ui-busy');
            }

            function setSubmitButtonLoading(button, isLoading) {
                if (!button) {
                    return;
                }

                var label = button.querySelector('[data-submit-label]');

                if (isLoading) {
                    button.disabled = true;
                    button.classList.add('is-loading');
                    button.setAttribute('aria-busy', 'true');

                    if (label && !button.dataset.originalLabel) {
                        button.dataset.originalLabel = label.textContent || '';
                    }

                    if (label) {
                        label.textContent = 'Menyimpan…';
                    }
                } else {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                    button.removeAttribute('aria-busy');

                    if (label && button.dataset.originalLabel) {
                        label.textContent = button.dataset.originalLabel;
                    }
                }
            }

            function encodeSignatureCanvas(sourceCanvas) {
                if (!sourceCanvas || !sourceCanvas.width) {
                    return '';
                }

                var maxEdge = 520;
                var w = sourceCanvas.width;
                var h = sourceCanvas.height;
                var scale = Math.min(1, maxEdge / Math.max(w, h));
                var target = sourceCanvas;

                if (scale < 1) {
                    var tmp = document.createElement('canvas');
                    tmp.width = Math.max(1, Math.round(w * scale));
                    tmp.height = Math.max(1, Math.round(h * scale));
                    var ctx = tmp.getContext('2d');

                    if (!ctx) {
                        return '';
                    }

                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, tmp.width, tmp.height);
                    ctx.drawImage(sourceCanvas, 0, 0, tmp.width, tmp.height);
                    target = tmp;
                }

                try {
                    return target.toDataURL('image/jpeg', 0.72);
                } catch (error) {
                    try {
                        return target.toDataURL('image/png');
                    } catch (fallbackError) {
                        return '';
                    }
                }
            }

            function beginFormSubmit(form, submitButton) {
                if (form.dataset.submitting === '1') {
                    return false;
                }

                form.dataset.submitting = '1';
                showSavingOverlay();
                setSubmitButtonLoading(submitButton, true);

                return true;
            }

            document.querySelectorAll('.playground-rsvp-form').forEach(function (form) {
                var noteField = form.querySelector('[data-playground-note-field]');
                var noteLabel = form.querySelector('[data-playground-note-label]');
                var noteInput = form.querySelector('textarea[name="note"]');
                var delegateFields = form.querySelector('[data-playground-delegate-fields]');
                var delegateInputs = delegateFields
                    ? Array.prototype.slice.call(delegateFields.querySelectorAll('input'))
                    : [];
                var signatureField = form.querySelector('[data-playground-signature-field]');
                var signatureCanvas = signatureField ? signatureField.querySelector('[data-playground-signature-canvas]') : null;
                var signatureInput = signatureField ? signatureField.querySelector('[data-playground-signature-input]') : null;
                var signatureDrawnInput = signatureField ? signatureField.querySelector('[data-playground-signature-drawn]') : null;
                var signatureLabel = signatureField ? signatureField.querySelector('[data-playground-signature-label]') : null;
                var signatureHelp = signatureField ? signatureField.querySelector('[data-playground-signature-help]') : null;
                var signatureError = signatureField ? signatureField.querySelector('[data-playground-signature-error]') : null;
                var signatureClear = signatureField ? signatureField.querySelector('[data-playground-signature-clear]') : null;
                var signatureContext = null;
                var signatureMode = '';
                var hasSignature = false;
                var isDrawing = false;
                var lastPoint = null;

                function showConditionalField(field) {
                    if (!field) return;

                    window.clearTimeout(field._playgroundHideTimer);
                    field.hidden = false;
                    field.style.removeProperty('display');
                    field.setAttribute('aria-hidden', 'false');
                    field.offsetHeight;

                    window.requestAnimationFrame(function () {
                        field.classList.add('is-open');
                    });
                }

                function hideConditionalField(field) {
                    if (!field) return;

                    window.clearTimeout(field._playgroundHideTimer);
                    field.classList.remove('is-open');
                    field.setAttribute('aria-hidden', 'true');
                    field._playgroundHideTimer = window.setTimeout(function () {
                        if (!field.classList.contains('is-open')) {
                            field.hidden = true;
                        }
                    }, 420);
                }

                function clearSignature() {
                    if (!signatureCanvas || !signatureContext || !signatureInput || !signatureDrawnInput || !signatureField) return;

                    signatureContext.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                    signatureInput.value = '';
                    signatureDrawnInput.value = '0';
                    hasSignature = false;
                    lastPoint = null;
                    signatureField.classList.remove('is-drawn');

                    if (signatureError) {
                        signatureError.hidden = true;
                    }
                }

                function resizeSignatureCanvas() {
                    if (!signatureCanvas) return;

                    var rect = signatureCanvas.getBoundingClientRect();
                    var ratio = window.devicePixelRatio || 1;
                    var width = Math.max(Math.round(rect.width), 320);
                    var height = Math.max(Math.round(rect.height), 140);
                    var previousSignature = hasSignature && signatureInput && signatureInput.value ? signatureInput.value : '';

                    signatureCanvas.width = Math.round(width * ratio);
                    signatureCanvas.height = Math.round(height * ratio);
                    signatureContext = signatureCanvas.getContext('2d');
                    signatureContext.setTransform(ratio, 0, 0, ratio, 0, 0);
                    signatureContext.lineCap = 'round';
                    signatureContext.lineJoin = 'round';
                    signatureContext.lineWidth = 2.4;
                    signatureContext.strokeStyle = '#111827';

                    if (previousSignature) {
                        var image = new Image();
                        image.onload = function () {
                            signatureContext.drawImage(image, 0, 0, width, height);
                        };
                        image.src = previousSignature;
                    }
                }

                function showSignatureField(mode) {
                    if (!signatureField) return;

                    if (mode !== signatureMode) {
                        clearSignature();
                        signatureMode = mode;
                    }

                    if (signatureLabel) {
                        signatureLabel.textContent = mode === 'represented' ? 'Paraf perwakilan' : 'Tanda tangan';
                    }

                    if (signatureHelp) {
                        signatureHelp.textContent = mode === 'represented'
                            ? 'Perwakilan membubuhkan paraf sebagai bukti konfirmasi.'
                            : 'Bubuhkan tanda tangan sebagai konfirmasi kehadiran.';
                    }

                    if (signatureError) {
                        signatureError.textContent = mode === 'represented'
                            ? 'Mohon isi paraf perwakilan terlebih dahulu.'
                            : 'Mohon isi tanda tangan terlebih dahulu.';
                        signatureError.hidden = true;
                    }

                    showConditionalField(signatureField);
                    window.requestAnimationFrame(resizeSignatureCanvas);
                }

                function hideSignatureField() {
                    if (!signatureField) return;

                    clearSignature();
                    signatureMode = '';
                    hideConditionalField(signatureField);
                }

                function getSignaturePoint(event) {
                    if (!signatureCanvas) return null;

                    var source = (event.touches && event.touches[0]) || (event.changedTouches && event.changedTouches[0]) || event;
                    var rect = signatureCanvas.getBoundingClientRect();

                    return {
                        x: source.clientX - rect.left,
                        y: source.clientY - rect.top
                    };
                }

                function markSignatureDrawn() {
                    if (!signatureDrawnInput || !signatureField) return;

                    signatureDrawnInput.value = '1';
                    signatureField.classList.add('is-drawn');
                    hasSignature = true;

                    if (signatureError) {
                        signatureError.hidden = true;
                    }
                }

                function flushSignatureToInput() {
                    if (!signatureCanvas || !hasSignature) {
                        return '';
                    }

                    return encodeSignatureCanvas(signatureCanvas);
                }

                function startSignature(event) {
                    if (!signatureCanvas || !signatureContext || (signatureField && signatureField.hidden)) return;

                    event.preventDefault();
                    var point = getSignaturePoint(event);
                    if (!point) return;

                    isDrawing = true;
                    lastPoint = point;
                    signatureContext.beginPath();
                    signatureContext.moveTo(point.x, point.y);

                    if (event.pointerId !== undefined && signatureCanvas.setPointerCapture) {
                        signatureCanvas.setPointerCapture(event.pointerId);
                    }
                }

                function moveSignature(event) {
                    if (!isDrawing || !signatureContext) return;

                    event.preventDefault();
                    var point = getSignaturePoint(event);
                    if (!point) return;

                    signatureContext.lineTo(point.x, point.y);
                    signatureContext.stroke();
                    lastPoint = point;
                    markSignatureDrawn();
                }

                function endSignature(event) {
                    if (!isDrawing || !signatureContext) return;

                    event.preventDefault();

                    if (!hasSignature && lastPoint) {
                        signatureContext.beginPath();
                        signatureContext.arc(lastPoint.x, lastPoint.y, 1.7, 0, Math.PI * 2);
                        signatureContext.fillStyle = '#111827';
                        signatureContext.fill();
                        markSignatureDrawn();
                    }

                    isDrawing = false;
                    signatureContext.closePath();
                }

                if (signatureCanvas) {
                    resizeSignatureCanvas();
                    if (signatureClear) {
                        signatureClear.addEventListener('click', clearSignature);
                    }
                    window.addEventListener('resize', resizeSignatureCanvas);

                    if (window.PointerEvent) {
                        signatureCanvas.addEventListener('pointerdown', startSignature);
                        signatureCanvas.addEventListener('pointermove', moveSignature);
                        window.addEventListener('pointerup', endSignature);
                        window.addEventListener('pointercancel', endSignature);
                    } else {
                        signatureCanvas.addEventListener('mousedown', startSignature);
                        signatureCanvas.addEventListener('mousemove', moveSignature);
                        window.addEventListener('mouseup', endSignature);
                        signatureCanvas.addEventListener('touchstart', startSignature, { passive: false });
                        signatureCanvas.addEventListener('touchmove', moveSignature, { passive: false });
                        window.addEventListener('touchend', endSignature, { passive: false });
                        window.addEventListener('touchcancel', endSignature, { passive: false });
                    }
                }

                function syncNoteField() {
                    var checked = form.querySelector('input[name="attendance"]:checked');
                    var mode = checked ? checked.value : '';

                    if (!checked) {
                        hideConditionalField(noteField);
                        hideConditionalField(delegateFields);
                        hideSignatureField();
                        return;
                    }

                    if (noteField && noteInput) {
                        if (mode === 'declined') {
                            showConditionalField(noteField);
                        } else {
                            hideConditionalField(noteField);
                        }

                        noteInput.required = mode === 'declined';

                        if (mode === 'declined') {
                            if (noteLabel) noteLabel.textContent = 'Catatan berhalangan';
                            noteInput.placeholder = noteInput.dataset.declinedPlaceholder || 'Tuliskan alasan berhalangan hadir secara singkat.';
                        } else {
                            noteInput.value = '';
                        }
                    }

                    if (delegateFields) {
                        if (mode === 'represented') {
                            showConditionalField(delegateFields);
                        } else {
                            hideConditionalField(delegateFields);
                        }

                        delegateInputs.forEach(function (input) {
                            input.required = mode === 'represented';

                            if (mode !== 'represented') {
                                input.value = '';
                            }
                        });
                    }

                    if (mode === 'attending' || mode === 'represented') {
                        showSignatureField(mode);
                    } else {
                        hideSignatureField();
                    }
                }

                form.querySelectorAll('input[name="attendance"]').forEach(function (input) {
                    input.addEventListener('change', syncNoteField);
                });

                var submitButton = form.querySelector('.playground-submit, button[type="submit"]');

                form.addEventListener('submit', function (event) {
                    var checked = form.querySelector('input[name="attendance"]:checked');
                    var mode = checked ? checked.value : '';
                    var needsSignature = mode === 'attending' || mode === 'represented';

                    if (needsSignature && signatureField && signatureInput) {
                        if (!hasSignature) {
                            event.preventDefault();
                            showSignatureField(mode);

                            if (signatureError) {
                                signatureError.hidden = false;
                            }

                            signatureField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }

                        event.preventDefault();

                        if (!beginFormSubmit(form, submitButton)) {
                            return;
                        }

                        window.requestAnimationFrame(function () {
                            signatureInput.value = flushSignatureToInput();

                            if (!signatureInput.value) {
                                form.dataset.submitting = '0';
                                setSubmitButtonLoading(submitButton, false);
                                document.body.classList.remove('is-ui-busy');
                                var overlay = document.getElementById('uiSavingOverlay');
                                if (overlay) {
                                    overlay.hidden = true;
                                }

                                if (signatureError) {
                                    signatureError.hidden = false;
                                }

                                return;
                            }

                            form.submit();
                        });

                        return;
                    }

                    if (!beginFormSubmit(form, submitButton)) {
                        event.preventDefault();
                    }
                });

                syncNoteField();
            });
})();
