package id.unmul.ft.yudisium.scanner.data.remote

import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import id.unmul.ft.yudisium.scanner.data.SessionStore
import kotlinx.coroutines.runBlocking
import kotlinx.serialization.json.Json
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import java.util.concurrent.TimeUnit

class ApiFactory(private val sessionStore: SessionStore) {
    private val json = Json {
        ignoreUnknownKeys = true
        explicitNulls = false
    }

    fun create(): MobileApi {
        val snapshot = runBlocking { sessionStore.snapshot() }
        val baseUrl = snapshot.baseUrl.trim().trimEnd('/') + "/"
        val client = OkHttpClient.Builder()
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(45, TimeUnit.SECONDS)
            .addInterceptor(Interceptor { chain ->
                val token = runBlocking { sessionStore.snapshot().token }
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
            .addInterceptor(HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BASIC
            })
            .build()

        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(client)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()
            .create(MobileApi::class.java)
    }
}
