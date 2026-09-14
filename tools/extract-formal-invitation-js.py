from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
blade = (ROOT / "resources/views/admin/invitation-playground.blade.php").read_text(encoding="utf-8")
match = re.search(r"@push\('scripts'\).*?<script>\s*(.*?)\s*</script>\s*@endpush", blade, re.S)
if not match:
    raise SystemExit("formal script block not found")

body = match.group(1).strip()
if body.startswith("(function () {") and body.endswith("})();"):
    body = body[len("(function () {") : -len("})();")].strip()

replacements = [
    ("var tutorialAudience = @js($recipientSalutation);", "var tutorialAudience = boot.tutorialAudience || '';"),
    (
        "var shouldShowStudentQrGuide = @json($showStudentQrCard && session('success'));",
        "var shouldShowStudentQrGuide = Boolean(boot.shouldShowStudentQrGuide);",
    ),
    ("var studentQrGuideSteps = @json($studentQrGuideSteps);", "var studentQrGuideSteps = boot.studentQrGuideSteps || [];"),
]

for old, new in replacements:
    body = body.replace(old, new)

body = re.sub(
    r"var defaultTutorialSteps = \[[\s\S]*?\];\s*@if \(\$canonicalCategory->requiresRsvp\(\)\)[\s\S]*?@endif\s*var tutorialSteps = defaultTutorialSteps;",
    "var tutorialSteps = (boot.tutorialSteps || []).map(function (step) {\n"
    "                if (step.text === '__OPENING__') {\n"
    "                    return { text: openingTutorialText(), target: step.target };\n"
    "                }\n"
    "                return step;\n"
    "            });",
    body,
    count=1,
)

header = """(() => {
  const bootEl = document.getElementById("formal-invitation-boot");
  const boot = bootEl ? JSON.parse(bootEl.textContent || "{}") : {};
"""
footer = "\n})();\n"

out = ROOT / "public/js/formal-invitation.js"
out.write_text(header + body + footer, encoding="utf-8")
print(f"wrote {out} ({out.stat().st_size} bytes)")
