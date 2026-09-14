from pathlib import Path

path = Path(__file__).resolve().parents[1] / "resources/views/admin/invitation-playground.blade.php"
lines = path.read_text(encoding="utf-8").splitlines()
head = [
    "@push('head')",
    "    <link rel=\"stylesheet\" href=\"{{ asset('css/formal-invitation.css') }}?v=1\">",
    "@endpush",
]
# Drop existing @push('head') line at index 71 when re-running.
start = 71
if start < len(lines) and lines[start].strip() == "@push('head')":
    start += 1
new = lines[:71] + head + lines[1800:]
path.write_text("\n".join(new) + "\n", encoding="utf-8")
print(len(new))
