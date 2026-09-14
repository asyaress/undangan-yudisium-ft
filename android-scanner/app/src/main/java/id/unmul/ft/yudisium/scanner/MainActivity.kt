package id.unmul.ft.yudisium.scanner

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.compose.runtime.getValue
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import id.unmul.ft.yudisium.scanner.ui.AppRoot
import id.unmul.ft.yudisium.scanner.ui.ScannerViewModel
import id.unmul.ft.yudisium.scanner.ui.theme.ScannerTheme

class MainActivity : ComponentActivity() {
    private val viewModel: ScannerViewModel by viewModels()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            ScannerTheme {
                val state by viewModel.uiState.collectAsStateWithLifecycle()
                AppRoot(
                    state = state,
                    onRefreshEvents = viewModel::refreshEvents,
                    onOpenEvent = viewModel::openEvent,
                    onScan = viewModel::onScanned,
                    onSync = viewModel::syncNow,
                    onLeaveEvent = viewModel::leaveEvent,
                    onClearLocalData = viewModel::clearLocalData,
                    onDismissResult = viewModel::dismissResult,
                    onDismissNotice = viewModel::consumeNotice,
                )
            }
        }
    }
}
