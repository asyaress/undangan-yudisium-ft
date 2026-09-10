package id.unmul.ft.yudisium.scanner.data

import android.content.Context
import android.os.Build
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.onEach

const val DEFAULT_SERVER_URL = "https://undangan-yudisium.ft.unmul.ac.id"

private val Context.dataStore by preferencesDataStore("yudisium_scan_session")

data class Session(
    val baseUrl: String = DEFAULT_SERVER_URL,
    val token: String = "",
    val name: String = "",
    val email: String = "",
    val periodId: Int = 0,
)

class SessionStore(private val context: Context) {
    @Volatile
    private var cached = Session()
    private val baseUrlKey = stringPreferencesKey("base_url")
    private val tokenKey = stringPreferencesKey("token")
    private val nameKey = stringPreferencesKey("name")
    private val emailKey = stringPreferencesKey("email")
    private val periodKey = intPreferencesKey("period_id")

    val session: Flow<Session> = context.dataStore.data.map { prefs ->
        Session(
            baseUrl = prefs[baseUrlKey] ?: DEFAULT_SERVER_URL,
            token = prefs[tokenKey].orEmpty(),
            name = prefs[nameKey].orEmpty(),
            email = prefs[emailKey].orEmpty(),
            periodId = prefs[periodKey] ?: 0,
        )
    }.onEach { cached = it }

    fun peek(): Session = cached

    suspend fun snapshot(): Session = session.first()

    suspend fun saveLogin(baseUrl: String, token: String, name: String, email: String) {
        cached = cached.copy(
            baseUrl = baseUrl.trim().trimEnd('/'),
            token = token,
            name = name,
            email = email,
        )
        context.dataStore.edit { prefs ->
            prefs[baseUrlKey] = baseUrl.trim().trimEnd('/')
            prefs[tokenKey] = token
            prefs[nameKey] = name
            prefs[emailKey] = email
        }
    }

    suspend fun savePeriod(periodId: Int) {
        cached = cached.copy(periodId = periodId)
        context.dataStore.edit { prefs -> prefs[periodKey] = periodId }
    }

    suspend fun clearAuth() {
        cached = cached.copy(token = "", name = "", email = "", periodId = 0)
        context.dataStore.edit { prefs ->
            prefs.remove(tokenKey)
            prefs.remove(nameKey)
            prefs.remove(emailKey)
            prefs.remove(periodKey)
        }
    }

    fun deviceName(): String {
        val model = listOf(Build.MANUFACTURER, Build.MODEL).filter { it.isNotBlank() }.joinToString(" ")
        return model.ifBlank { "HP panitia" }
    }
}
