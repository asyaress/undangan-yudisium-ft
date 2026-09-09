package id.unmul.ft.yudisium.scanner.scan

object QrParser {
    fun lookupCode(raw: String): String {
        val value = raw.trim()
        if (value.startsWith("YFT|")) {
            val parts = value.split("|")
            if (parts.size == 4) {
                return value
            }
        }
        return value
    }

    fun isLikelyQr(raw: String): Boolean {
        val value = raw.trim()
        return value.startsWith("YFT|") || value.matches(Regex("^[0-9]{6,20}$"))
    }
}
