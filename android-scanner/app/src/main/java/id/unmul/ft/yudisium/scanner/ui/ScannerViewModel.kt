package id.unmul.ft.yudisium.scanner.ui

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import id.unmul.ft.yudisium.scanner.ScannerApp
import id.unmul.ft.yudisium.scanner.data.DEFAULT_SERVER_URL
import id.unmul.ft.yudisium.scanner.data.LocalScanResult
import id.unmul.ft.yudisium.scanner.data.Session
import id.unmul.ft.yudisium.scanner.data.local.EventEntity
import id.unmul.ft.yudisium.scanner.data.local.PendingScanEntity
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

data class ScanUiState(
    val session: Session = Session(),
    val events: List<EventEntity> = emptyList(),
    val recent: List<PendingScanEntity> = emptyList(),
    val pendingCount: Int = 0,
    val total: Int = 0,
    val checkedIn: Int = 0,
    val eventName: String = "",
    val loading: Boolean = false,
    val notice: String? = null,
    val result: LocalScanResult? = null,
)

class ScannerViewModel(application: Application) : AndroidViewModel(application) {
    private val repository = (application as ScannerApp).container.repository
    private val _uiState = MutableStateFlow(ScanUiState())
    val uiState: StateFlow<ScanUiState> = _uiState
    private var lastCode = ""
    private var lastAt = 0L
    private var periodJobs: Job? = null
    private var syncJob: Job? = null

    init {
        viewModelScope.launch {
            repository.session.collectLatest { session ->
                _uiState.update {
                    it.copy(
                        session = session,
                        eventName = it.events.firstOrNull { event -> event.id == session.periodId }?.name.orEmpty(),
                    )
                }
                bindPeriod(session.periodId)
                startSyncLoop(session)
            }
        }
        viewModelScope.launch {
            repository.events().collect { events ->
                _uiState.update { state ->
                    state.copy(
                        events = events,
                        eventName = events.firstOrNull { it.id == state.session.periodId }?.name.orEmpty(),
                    )
                }
            }
        }
    }

    fun login(email: String, password: String) = runAction {
        repository.login(DEFAULT_SERVER_URL, email, password)
    }

    fun refreshEvents() = runAction { repository.refreshEvents() }

    fun openEvent(periodId: Int) = runAction {
        repository.downloadRoster(periodId)
    }

    fun syncNow() = runAction {
        val periodId = _uiState.value.session.periodId
        if (periodId != 0) repository.sync(periodId)
    }

    fun leaveEvent() = runAction { repository.leaveEvent() }

    fun logout() = runAction { repository.logout() }

    fun consumeNotice() {
        _uiState.update { it.copy(notice = null) }
    }

    fun dismissResult() {
        _uiState.update { it.copy(result = null) }
    }

    fun onScanned(code: String) {
        val now = System.currentTimeMillis()
        if (code == lastCode && now - lastAt < 1200L) return
        lastCode = code
        lastAt = now
        val periodId = _uiState.value.session.periodId
        if (periodId == 0) return
        viewModelScope.launch {
            runCatching { repository.recordScan(periodId, code) }
                .onSuccess { scan ->
                    _uiState.update { it.copy(result = scan) }
                    launch { runCatching { repository.sync(periodId) } }
                }
                .onFailure { error -> _uiState.update { it.copy(notice = repository.apiError(error)) } }
        }
    }

    private fun bindPeriod(periodId: Int) {
        periodJobs?.cancel()
        if (periodId == 0) {
            _uiState.update { it.copy(recent = emptyList(), pendingCount = 0, total = 0, checkedIn = 0) }
            return
        }
        periodJobs = viewModelScope.launch {
            launch {
                repository.recentScans(periodId).collect { rows ->
                    _uiState.update { state ->
                        val result = state.result?.let { current ->
                            val row = rows.firstOrNull { it.clientScanId == current.clientScanId }
                            if (row == null) {
                                current
                            } else {
                                current.copy(
                                    status = row.localStatus,
                                    message = row.message,
                                    name = row.participantName ?: current.name,
                                    nim = row.participantNim ?: current.nim,
                                    pending = !row.synced && row.localStatus != "not_found",
                                )
                            }
                        }
                        state.copy(recent = rows, result = result)
                    }
                }
            }
            launch {
                repository.unsyncedCount(periodId).collect { count ->
                    _uiState.update { it.copy(pendingCount = count) }
                }
            }
            launch {
                repository.totalCount(periodId).collect { count ->
                    _uiState.update { it.copy(total = count) }
                }
            }
            launch {
                repository.checkedInCount(periodId).collect { count ->
                    _uiState.update { it.copy(checkedIn = count) }
                }
            }
        }
    }

    private fun startSyncLoop(session: Session) {
        syncJob?.cancel()
        if (session.token.isBlank() || session.periodId == 0) return
        syncJob = viewModelScope.launch {
            while (isActive) {
                runCatching { repository.sync(session.periodId) }
                delay(15_000)
            }
        }
    }

    private fun runAction(block: suspend () -> Unit) {
        viewModelScope.launch {
            _uiState.update { it.copy(loading = true, notice = null) }
            runCatching { block() }
                .onFailure { error -> _uiState.update { it.copy(notice = repository.apiError(error)) } }
            _uiState.update { it.copy(loading = false) }
        }
    }
}
