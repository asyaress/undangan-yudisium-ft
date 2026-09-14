from pathlib import Path

path = Path(__file__).resolve().parents[1] / "resources/views/admin/invitation-playground.blade.php"
text = path.read_text(encoding="utf-8")
start = text.index("@push('scripts')")
end = text.index("@endpush", start) + len("@endpush")
replacement = """@push('scripts')
    @if ($showStudentQrCard)
        <script src=\"{{ asset('vendor/qrcode/qrcode.min.js') }}\" defer></script>
    @endif
    <script type=\"application/json\" id=\"formal-invitation-boot\">@json($formalInvitationBoot)</script>
    <script src=\"{{ asset('js/formal-invitation.js') }}?v=1\" defer></script>
@endpush"""
path.write_text(text[:start] + replacement + text[end:], encoding="utf-8")
print("removed bytes", len(text) - len(text[:start]) - (len(text) - end))
