package id.unmul.ft.yudisium.scanner.data.remote

import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import id.unmul.ft.yudisium.scanner.data.DEFAULT_SERVER_URL
import id.unmul.ft.yudisium.scanner.data.SessionStore
import kotlinx.serialization.json.Json
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import retrofit2.Retrofit
import java.util.concurrent.TimeUnit

class ApiFactory(private val sessionStore: SessionStore) {
    private val json = Json {
        ignoreUnknownKeys = true
        explicitNulls = false
    }
    private val lock = Any()
    private val httpClient: OkHttpClient = OkHttpClient.Builder()
        .connectTimeout(8, TimeUnit.SECONDS)
        .readTimeout(12, TimeUnit.SECONDS)
        .writeTimeout(12, TimeUnit.SECONDS)
        .callTimeout(15, TimeUnit.SECONDS)
        .retryOnConnectionFailure(true)
        .addInterceptor(Interceptor { chain ->
            val token = sessionStore.peek().token
            val request = chain.request().newBuilder()
                .header("Accept", "application/json")
                .apply {
                    if (token.isNotBlank()) {
                        header("Authorization", "Bearer $token")
                    }
                }
                .build()
            chain.proceed(request)
        })
        .build()

    @Volatile
    private var cachedUrl: String? = null

    @Volatile
    private var cachedApi: MobileApi? = null

    fun create(): MobileApi {
        val baseUrl = sessionStore.peek().baseUrl.ifBlank { DEFAULT_SERVER_URL }.trim().trimEnd('/') + "/"
        cachedApi?.let { current ->
            if (cachedUrl == baseUrl) return current
        }
        synchronized(lock) {
            cachedApi?.let { current ->
                if (cachedUrl == baseUrl) return current
            }
            val api = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(httpClient)
                .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
                .build()
                .create(MobileApi::class.java)
            cachedUrl = baseUrl
            cachedApi = api
            return api
        }
    }
}
