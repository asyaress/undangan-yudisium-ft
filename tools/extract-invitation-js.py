from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
src = (ROOT / "resources/views/pages/partials/invitation-scripts.blade.php").read_text(encoding="utf-8")
match = re.search(r"<script>(.*)</script>", src, re.S)
if not match:
    raise SystemExit("script block not found")

body = match.group(1).strip()
body = body.replace(
    "const rsvpTutorialSteps = @json($rsvpTutorialSteps);",
    "const rsvpTutorialSteps = boot.rsvpTutorialSteps || [];",
)
body = body.replace(
    "const showRsvpTutorialOnOpen = @json($showRsvpGuide);",
    "const showRsvpTutorialOnOpen = Boolean(boot.showRsvpTutorialOnOpen);",
)
body = body.replace(
    "const shouldAutoOpen = @json($autoOpenInvitation ?? false);",
    "const shouldAutoOpen = Boolean(boot.shouldAutoOpen);",
)
body = re.sub(
    r"const logoCandidates = \[[\s\S]*?\];",
    "const logoCandidates = boot.logoCandidates || [];",
    body,
    count=1,
)

header = """(() => {
  const bootEl = document.getElementById("invitation-boot");
  const boot = bootEl ? JSON.parse(bootEl.textContent || "{}") : {};
"""
footer = "\n})();\n"

out = ROOT / "public/js/invitation.js"
out.parent.mkdir(parents=True, exist_ok=True)
out.write_text(header + body + footer, encoding="utf-8")
print(f"wrote {out} ({out.stat().st_size} bytes)")
