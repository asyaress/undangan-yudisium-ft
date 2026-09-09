package id.unmul.ft.yudisium.scanner

import android.content.Context
import androidx.room.Room
import id.unmul.ft.yudisium.scanner.data.CheckinRepository
import id.unmul.ft.yudisium.scanner.data.SessionStore
import id.unmul.ft.yudisium.scanner.data.local.AppDatabase
import id.unmul.ft.yudisium.scanner.data.remote.ApiFactory

class AppContainer(context: Context) {
    private val appContext = context.applicationContext
    val sessionStore = SessionStore(appContext)
    private val database = Room.databaseBuilder(appContext, AppDatabase::class.java, "yudisium-scan.db")
        .fallbackToDestructiveMigration()
        .build()
    val repository = CheckinRepository(
        sessionStore = sessionStore,
        database = database,
        apiFactory = ApiFactory(sessionStore),
    )
}
