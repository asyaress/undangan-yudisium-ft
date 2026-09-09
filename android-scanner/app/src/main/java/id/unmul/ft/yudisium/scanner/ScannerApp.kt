package id.unmul.ft.yudisium.scanner

import android.app.Application

class ScannerApp : Application() {
    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)
    }
}
